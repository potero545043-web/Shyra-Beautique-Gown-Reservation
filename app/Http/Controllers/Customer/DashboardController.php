<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Gown;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Services\ReservationCart;
use App\Services\VercelBlobStorage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class DashboardController extends Controller
{
    /** Longest rental window measured from the pickup date. */
    public const MAX_RENTAL_DAYS = 3;

    /** Gowns remain unavailable for two calendar days after the return date. */
    public const CLEANING_BUFFER_DAYS = 2;

    public static function gownBlockedThrough(Carbon $returnDate): Carbon
    {
        return $returnDate->copy()->startOfDay()->addDays(self::CLEANING_BUFFER_DAYS - 1);
    }

    /** Bump when the rental terms change so acceptances stay auditable. */
    public const AGREEMENT_VERSION = 'v1.1';

    private ReservationCart $cart;

    public function __construct()
    {
        $this->cart = ReservationCart::forRole('customer');
    }

    public function index()
    {
        $customer = Customer::where('user_id', auth()->id())->first();
        $rentals = $customer ? Reservation::with('items.gown.category')
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'awaiting_payment', 'confirmed', 'ready_for_pickup', 'released', 'overdue'])
            ->where(fn ($query) => $query->whereDate('return_date', '>=', today())
                ->orWhereIn('status', ['released', 'overdue']))
            ->orderBy('return_date')
            ->orderBy('pickup_date')
            ->take(5)
            ->get() : collect();
        $gownOptions = Gown::whereNull('archived_at')->where('status', '!=', 'retired')->get(['size', 'style', 'color']);

        return view('customer.dashboard', [
            'featured' => Gown::with('category')->whereNull('archived_at')->whereIn('status', ['available', 'reserved'])->latest()->take(4)->get(),
            'rentals' => $rentals,
            'filterOptions' => [
                'sizes' => $gownOptions->pluck('size')->filter()->unique()->sort()->values(),
                'styles' => $gownOptions->pluck('style')->filter()->unique()->sort()->values(),
                'colors' => $gownOptions->pluck('color')->filter()->unique()->sort()->values(),
            ],
        ]);
    }

    public function catalog()
    {
        $gowns = Gown::with('category')->whereNull('archived_at')->where('status', '!=', 'retired')->latest()->get();

        return view('customer.catalog', compact('gowns'));
    }

    public function details(Gown $gown)
    {
        abort_if($gown->status === 'retired', 404);
        $gown->load('category', 'accessories');

        return view('customer.gown-details', compact('gown'));
    }

    /** Starts a single-gown checkout without changing the customer's bulk cart. */
    public function reserveGown(Gown $gown)
    {
        abort_unless(in_array($gown->status, ['available', 'reserved'], true), 404);
        abort_if($gown->archived_at !== null, 404);

        return $this->renderReservationWizard(collect([$gown]), 'single');
    }

    /** Opens the multi-gown reservation wizard for whatever is already in the cart. */
    public function reserve()
    {
        return $this->renderReservationWizard();
    }

    /** Adds a gown to the in-progress reservation and opens the cart. */
    public function addToCart(Request $request, Gown $gown)
    {
        abort_unless(in_array($gown->status, ['available', 'reserved'], true), 422, 'This gown cannot be added to a reservation right now.');
        abort_if($gown->archived_at !== null, 422, 'This gown is no longer in the collection.');

        $this->cart->add($gown->id);

        return back()->with('success', $gown->name.' was added to your cart. Add more gowns, then open My Cart to reserve them together.');
    }

    /** Shows the My Cart page where every added gown waits for a bulk checkout. */
    public function cart()
    {
        return view('customer.cart', [
            'gowns' => $this->cart->gowns(),
            'rentalTotal' => $this->cart->total(),
            'lateFeePerDay' => (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value'),
            'maxRentalDays' => self::MAX_RENTAL_DAYS,
        ]);
    }

    /** Removes a gown from the in-progress reservation. */
    public function removeFromCart(Gown $gown)
    {
        $this->cart->remove($gown->id);

        return redirect()->route('customer.cart')
            ->with('success', $gown->name.' was removed from your cart.');
    }

    public function clearCart()
    {
        $this->cart->clear();

        return redirect()->route('customer.catalog')->with('success', 'Your cart was cleared.');
    }

    /** Builds the multi-gown reservation wizard with cart totals and availability. */
    private function renderReservationWizard(?Collection $gowns = null, string $reservationSource = 'cart')
    {
        $gowns ??= $this->loadCartGowns();
        if ($gowns->isEmpty()) {
            return redirect()->route('customer.catalog')
                ->with('success', 'Choose a gown to begin your reservation.');
        }

        return view('customer.reserve', [
            'gowns' => $gowns,
            'lateFeePerDay' => (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value'),
            'maxRentalDays' => self::MAX_RENTAL_DAYS,
            'agreementVersion' => self::AGREEMENT_VERSION,
            'reservationSource' => $reservationSource,
        ]);
    }

    /** @return array<int, string> Resolved-by-query availability rows keyed by gown id, ready for the client. */
    private function availabilityMap(Collection $gowns, Carbon $pickup, Carbon $return): array
    {
        return $this->conflictsFor($gowns, $pickup, $return)
            ->groupBy(fn ($row) => $row['gown']->id)
            ->map(fn ($rows) => [
                'from' => $rows->first()['from']->toDateString(),
                'through' => $rows->first()['through']->toDateString(),
                'status' => $rows->first()['status'],
            ])
            ->all();
    }

    /** Returns the gowns in the session cart, preserving the order they were added. */
    private function loadCartGowns(): Collection
    {
        return $this->cart->gowns();
    }

    /**
     * Live availability check backing step 1, so the customer cannot continue
     * on dates the gown is already spoken for (cleaning buffer included).
     */
    public function checkAvailability(Request $request, Gown $gown)
    {
        abort_unless(in_array($gown->status, ['available', 'reserved'], true), 404);

        $data = $request->validate([
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'gown_ids' => ['required_if:reservation_source,single', 'array', 'min:1'],
            'gown_ids.*' => ['integer', 'distinct', 'exists:gowns,id'],
        ]);

        $pickup = Carbon::parse($data['pickup_date'])->startOfDay();
        $return = Carbon::parse($data['return_date'])->startOfDay();
        $days = $pickup->diffInDays($return);

        $gowns = Gown::whereIn('id', array_unique(array_merge([$gown->id], $data['gown_ids'] ?? [])))
            ->whereNull('archived_at')
            ->whereIn('status', ['available', 'reserved'])
            ->get();
        if ($gowns->count() !== count(array_unique(array_merge([$gown->id], $data['gown_ids'] ?? [])))) {
            return response()->json([
                'available' => false,
                'reason' => 'One or more gowns in your reservation can no longer be booked.',
                'summary' => null,
                'rental_days' => $days + 1,
                'conflicts' => [],
            ]);
        }
        $maxDays = self::MAX_RENTAL_DAYS;
        if ($days + 1 > $maxDays) {
            return response()->json([
                'available' => false,
                'reason' => 'A gown may be rented for up to '.$maxDays.' days from its pickup date.',
                'summary' => null,
                'rental_days' => $days + 1,
                'conflicts' => [],
            ]);
        }

        $conflicts = $this->availabilityMap($gowns, $pickup, $return);
        $reason = $conflicts
            ? collect($conflicts)->map(function ($block, $gownId) use ($gowns) {
                $blockedGown = $gowns->firstWhere('id', $gownId);

                return ($blockedGown?->name ?? 'A selected gown').' is already booked '
                    .Carbon::parse($block['from'])->toFormattedDateString()
                    .' – '.Carbon::parse($block['through'])->toFormattedDateString();
            })->implode('; ').'. Please pick different dates.'
            : null;

        return response()->json([
            'available' => $reason === null,
            'reason' => $reason,
            'summary' => $reason === null ? 'Available for your dates · Pickup '.$pickup->toFormattedDateString()
                .' \u00b7 Return '.$return->toFormattedDateString().' \u00b7 Subject to staff approval' : null,
            'rental_days' => $days + 1,
            'conflicts' => array_map(fn ($block) => [
                'from' => $block['from'],
                'through' => $block['through'],
                'status' => $block['status'],
            ], $conflicts),
        ]);
    }

    /**
     * Submits the whole reservation cart as ONE reservation with one line item
     * per gown, so a customer reserving several gowns for the same dates signs a
     * single agreement and makes a single payment.
     */
    public function storeReservation(Request $request)
    {
        $contactNumber = preg_replace('/\D/', '', (string) $request->input('contact_number', ''));
        $request->merge(['contact_number' => $contactNumber]);

        $data = $request->validate([
            'contact_number' => ['required', 'digits:11', 'regex:/^09\d{9}$/'],
            'gown_ids' => ['sometimes', 'array', 'min:1'],
            'gown_ids.*' => ['integer', 'distinct', 'exists:gowns,id'],
            'reservation_source' => ['sometimes', Rule::in(['cart', 'single'])],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'bust' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'waist' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'hips' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'government_id' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'agreement_accepted' => ['accepted'],
            'payment_method' => ['required', Rule::in(['cash'])],
            'payment_option' => ['required', Rule::in(['full', 'downpayment'])],
            'payment_amount' => ['required', 'numeric', 'gt:0'],
        ], [
            'contact_number.digits' => 'Format: 09XXXXXXXXX (11 digits).',
            'contact_number.regex' => 'Format: 09XXXXXXXXX (11 digits).',
        ]);

        $gownIds = array_values(array_unique(array_map('intval', $data['gown_ids'] ?? [])));
        $reservationSource = $data['reservation_source'] ?? 'cart';
        if ($reservationSource === 'single' && count($gownIds) !== 1) {
            throw ValidationException::withMessages([
                'gown_ids' => 'Reserve Now must contain exactly one gown.',
            ]);
        }
        $gowns = $gownIds === []
            ? $this->loadCartGowns()
            : Gown::with('category')
                ->whereIn('id', $gownIds)
                ->whereNull('archived_at')
                ->get()
                ->sortBy(fn (Gown $gown) => array_search($gown->id, $gownIds, true))
                ->values();
        if ($gowns->isEmpty()) {
            return redirect()->route('customer.catalog')
                ->with('success', 'Choose at least one gown to begin your reservation.');
        }
        if ($gownIds !== [] && $gowns->count() !== count($gownIds)) {
            throw ValidationException::withMessages([
                'gown_ids' => 'One or more selected gowns are no longer available. Please review your reservation.',
            ]);
        }

        if (Carbon::parse($data['return_date'])->gt(Carbon::parse($data['pickup_date'])->addDays(self::MAX_RENTAL_DAYS - 1))) {
            throw ValidationException::withMessages([
                'return_date' => 'A gown may be rented for up to '.self::MAX_RENTAL_DAYS.' days from its pickup date.',
            ]);
        }
        $lateFeePerDay = (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value');
        if ($lateFeePerDay <= 0) {
            throw ValidationException::withMessages(['return_date' => 'The shop must configure a late fee per day before accepting reservations.']);
        }
        $user = $request->user();
        $measurements = collect([
            'Bust' => $data['bust'] ?? null,
            'Waist' => $data['waist'] ?? null,
            'Hips' => $data['hips'] ?? null,
        ])->filter(fn ($value) => $value !== null)
            ->map(fn ($value, $key) => $key.': '.$value.' cm')->values()->join('; ');
        $customer = Customer::updateOrCreate(['user_id' => $user->id], [
            'customer_code' => Customer::where('user_id', $user->id)->value('customer_code') ?? 'CUS-'.strtoupper(Str::random(8)),
            'full_name' => $user->name,
            'contact_number' => $data['contact_number'],
            'email' => $user->email,
            'status' => 'active',
        ]);

        $idPath = app(VercelBlobStorage::class)->store($request->file('government_id'), 'government-ids', 'private');

        try {
            $reservation = DB::transaction(function () use ($request, $gowns, $customer, $user, $data, $measurements, $lateFeePerDay, $idPath) {
                // Reload every cart gown with a row lock so two customers cannot
                // grab the same gown for overlapping dates at the same moment.
                $lockedGowns = Gown::whereIn('id', $gowns->pluck('id'))
                    ->whereNull('archived_at')
                    ->lockForUpdate()
                    ->get();
                if ($lockedGowns->count() !== $gowns->count()) {
                    throw ValidationException::withMessages(['pickup_date' => 'One of the gowns in your reservation is no longer available. Please review your list and try again.']);
                }
                foreach ($lockedGowns as $lockedGown) {
                    abort_unless(in_array($lockedGown->status, ['available', 'reserved'], true), 422, $lockedGown->name.' cannot be reserved at this time.');
                }
                $conflicts = $this->conflictsFor($lockedGowns, Carbon::parse($data['pickup_date']), Carbon::parse($data['return_date']));
                if ($conflicts->isNotEmpty()) {
                    $names = $conflicts->pluck('gown.name')->unique()->implode(', ');
                    throw ValidationException::withMessages(['pickup_date' => 'These gowns are already reserved for some or all of those dates: '.$names.'. Remove the blocked gown(s) or pick a different date range.']);
                }

                $rentalTotal = round($lockedGowns->sum(fn ($gown) => (float) $gown->rental_price), 2);
                $paymentAmount = (float) $data['payment_amount'];
                if ($paymentAmount > $rentalTotal) {
                    throw ValidationException::withMessages(['payment_amount' => 'Payment cannot be greater than the total rental fee.']);
                }
                if ($data['payment_option'] === 'downpayment' && $paymentAmount <= 500) {
                    throw ValidationException::withMessages(['payment_amount' => 'A down payment must be greater than ₱500.00 and less than the total rental fee.']);
                }
                if ($data['payment_option'] === 'full' && round($paymentAmount, 2) !== $rentalTotal) {
                    throw ValidationException::withMessages(['payment_amount' => 'Full payment must equal the total rental fee. Choose down payment to enter a smaller amount.']);
                }
                if ($data['payment_option'] === 'downpayment' && $paymentAmount >= $rentalTotal) {
                    throw ValidationException::withMessages(['payment_amount' => 'A down payment must be greater than zero and less than the total rental fee.']);
                }

                $booking = Reservation::create([
                    'reservation_code' => 'GR-'.now()->format('Y').'-'.strtoupper(Str::random(6)),
                    'customer_id' => $customer->id,
                    'created_by' => $user->id,
                    'pickup_date' => $data['pickup_date'],
                    'return_date' => $data['return_date'],
                    'rental_total' => $rentalTotal,
                    'security_deposit_total' => 0,
                    'late_fee_per_day' => $lateFeePerDay,
                    'grand_total' => $rentalTotal,
                    'amount_paid' => 0,
                    'balance' => $rentalTotal,
                    'status' => 'pending',
                    'customer_notes' => $data['notes'] ?? null,
                    'measurements' => $measurements ?: null,
                    'agreement_version' => self::AGREEMENT_VERSION,
                    'agreement_accepted_at' => now(),
                    'agreement_accepted_ip' => $request->ip(),
                    'payment_reference_number' => null,
                    'government_id_photo_path' => $idPath,
                    'collateral_status' => 'not_received',
                ]);

                foreach ($lockedGowns as $gown) {
                    $booking->items()->create([
                        'gown_id' => $gown->id,
                        'rental_price' => (float) $gown->rental_price,
                        'security_deposit' => 0,
                        'quantity' => 1,
                    ]);
                }
                Payment::create([
                    'reservation_id' => $booking->id,
                    'customer_id' => $customer->id,
                    'payment_reference' => 'SBP-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
                    'payment_type' => $data['payment_option'] === 'full' ? 'rental_balance' : 'downpayment',
                    'payment_method' => $data['payment_method'],
                    'amount' => $paymentAmount,
                    'status' => 'pending',
                    'remarks' => 'Cash payment awaiting staff confirmation.',
                ]);

                return $booking;
            });
        } catch (QueryException $exception) {
            app(VercelBlobStorage::class)->delete($idPath, 'private');
            report($exception);

            return back()->withInput()->withErrors([
                'reservation' => 'We could not submit your reservation (RES-500). Your request was not saved. Please try again or contact the boutique.',
            ]);
        } catch (Throwable $exception) {
            app(VercelBlobStorage::class)->delete($idPath, 'private');
            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            throw $exception;
        }

        if ($reservationSource === 'cart') {
            $this->cart->clear();
        }

        return redirect()->route('customer.reservations.show', $reservation)->with('success', 'Reservation '.$reservation->reservation_code.' submitted for '.$gowns->count().' gown(s). Staff will review your agreement and payment.');
    }

    public function reservations()
    {
        $pageSize = request()->integer('per_page', 12);
        $pageSize = in_array($pageSize, [12, 24, 48], true) ? $pageSize : 12;
        $customer = Customer::where('user_id', auth()->id())->first();
        $reservations = $customer ? Reservation::with(['items.gown', 'payments', 'gownReturn', 'gownRelease', 'penalties', 'gownPurchases.gown'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate($pageSize)
            ->withQueryString() : new LengthAwarePaginator(
                collect(),
                0,
                $pageSize,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => request()->url(), 'query' => request()->query()],
            );

        return view('customer.reservations', [
            'reservations' => $reservations,
            'lifecycles' => $reservations->mapWithKeys(fn ($reservation) => [$reservation->id => $this->lifecycleFor($reservation)]),
        ]);
    }

    public function showReservation(Reservation $reservation)
    {
        $customer = Customer::where('user_id', auth()->id())->firstOrFail();
        abort_unless($reservation->customer_id === $customer->id, 404);
        $reservation->load(['items.gown', 'payments', 'gownReturn', 'gownRelease', 'penalties', 'gownPurchases.gown', 'cancelledBy']);
        $cancellationHistory = $reservation->status === 'cancelled'
            ? AuditLog::with('user')->where('description', 'like', '%'.$reservation->reservation_code.'%')->oldest()->get()
            : collect();

        return view('customer.reservation-show', [
            'reservation' => $reservation,
            'lifecycle' => $this->lifecycleFor($reservation),
            'cancellationHistory' => $cancellationHistory,
        ]);
    }

    public function cancelReservation(Request $request, Reservation $reservation)
    {
        $customer = Customer::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($reservation->customer_id === $customer->id, 404);

        DB::transaction(function () use ($reservation, $request) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['pending', 'awaiting_payment'], true), 422, 'Only requests that staff have not confirmed can be cancelled here. Please contact the boutique for help with a confirmed booking.');
            $verifiedDownPayments = $locked->payments()
                ->where('payment_type', 'downpayment')
                ->where('status', 'verified')
                ->get();
            $retainedAmount = (float) $verifiedDownPayments->sum('amount');
            $now = now();

            $locked->update([
                'status' => 'cancelled',
                'cancelled_by' => $request->user()->id,
                'cancelled_at' => $now,
                'cancellation_reason' => 'Customer cancelled',
                'cancellation_refund_amount' => 0,
            ]);

            foreach ($verifiedDownPayments as $payment) {
                $payment->update([
                    'remarks' => trim(($payment->remarks ? $payment->remarks.' ' : '')
                        .'Retained after customer cancellation; non-refundable. Refund: ₱0.00.'),
                ]);
            }

            $locked->payments()
                ->where('payment_type', 'downpayment')
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'remarks' => 'Reservation cancelled before this cash payment was verified; amount is not counted as paid.',
                ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'reservation.cancelled',
                'description' => "Reservation {$locked->reservation_code}: Customer cancelled. Verified down payment retained: ₱".number_format($retainedAmount, 2).'. Refund: ₱0.00.',
                'ip_address' => $request->ip(),
            ]);

            if ($retainedAmount > 0) {
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'payment.retained',
                    'description' => "Reservation {$locked->reservation_code}: Down payment ₱".number_format($retainedAmount, 2).' retained after customer cancellation; original payment amount preserved.',
                    'ip_address' => $request->ip(),
                ]);
            }
        });

        return redirect()->route('customer.reservations.show', $reservation)
            ->with('success', 'Reservation cancelled. Any verified down payment is retained under the cancellation policy.');
    }

    public function submitPayment(Request $request, Reservation $reservation)
    {
        $customer = Customer::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($reservation->customer_id === $customer->id, 404);
        abort_if(in_array($reservation->status, ['cancelled', 'rejected', 'completed'], true), 422, 'Payments are closed for this reservation.');
        $pendingAmount = (float) Payment::where('reservation_id', $reservation->id)->where('status', 'pending')->sum('amount');
        $payableNow = max(0, (float) $reservation->balance - $pendingAmount);
        abort_if($payableNow <= 0, 422, 'Your outstanding balance is already covered by payments waiting for review.');
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'lte:'.$payableNow],
            'payment_type' => ['required', Rule::in(['downpayment', 'rental_balance', 'penalty', 'damage_fee'])],
            'amount' => ['required', 'numeric', $request->input('payment_type') === 'downpayment' ? 'gt:500' : 'gt:0', 'lte:'.$payableNow],
        ]);
        Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'payment_reference' => 'SBP-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
            'payment_type' => $data['payment_type'],
            'payment_method' => 'cash',
            'amount' => $data['amount'],
            'status' => 'pending',
            'remarks' => 'Cash payment awaiting staff confirmation.',
        ]);

        return back()->with('success', 'Payment submitted. The boutique will verify it shortly.');
    }

    /**
     * Date blocks through the return date and one additional day, making the
     * earliest next pickup the return date plus the two-day cleaning buffer.
     * Accepts every gown in the cart and returns one row per blocked (gown, reservation)
     * pair so the caller knows exactly which gowns are unavailable.
     *
     * @param  Collection<int, Gown>  $gowns
     * @return Collection<int, array{gown: Gown, from: Carbon, through: Carbon, status: string}>
     */
    private function conflictsFor(Collection $gowns, Carbon $pickup, Carbon $return): Collection
    {
        $gownIds = $gowns->pluck('id');
        if ($gownIds->isEmpty()) {
            return collect();
        }

        $gownsById = $gowns->keyBy('id');

        return Reservation::with('gownReturn')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereHas('items', fn ($items) => $items->whereIn('gown_id', $gownIds))
            ->get()
            ->flatMap(function ($existing) use ($gownsById, $pickup, $return) {
                $occupiedFrom = Carbon::parse($existing->pickup_date)->startOfDay();
                $returnDate = $existing->gownReturn?->actual_return_date ?? $existing->return_date;
                $occupiedThrough = self::gownBlockedThrough(Carbon::parse($returnDate));

                if (! ($occupiedFrom->lte($return) && $occupiedThrough->gte($pickup))) {
                    return [];
                }

                return $existing->items
                    ->pluck('gown_id')
                    ->unique()
                    ->filter(fn ($gownId) => $gownsById->has($gownId))
                    ->map(fn ($gownId) => [
                        'gown' => $gownsById->get($gownId),
                        'from' => $occupiedFrom,
                        'through' => $occupiedThrough,
                        'status' => $existing->status,
                    ])
                    ->values()
                    ->all();
            })
            ->values();
    }

    /**
     * Builds the customer-visible lifecycle of a reservation so a booking can
     * show real progress instead of a single status string.
     *
     * @return array<int, array{key: string, label: string, state: string, meta: ?string}>
     */
    private function lifecycleFor(Reservation $reservation): array
    {
        $release = $reservation->gownRelease;
        $returned = $reservation->gownReturn;
        $paid = (float) $reservation->amount_paid;
        $pending = (float) $reservation->payments->where('status', 'pending')->sum('amount');
        $closed = in_array($reservation->status, ['cancelled', 'rejected'], true);

        return [
            [
                'key' => 'requested',
                'label' => 'Reservation submitted',
                'state' => 'done',
                'meta' => $reservation->created_at?->toFormattedDateString(),
            ],
            [
                'key' => 'agreement',
                'label' => 'Rental agreement',
                'state' => $reservation->agreement_accepted_at ? 'done' : 'upcoming',
                'meta' => $reservation->agreement_accepted_at
                    ? 'Accepted · '.$reservation->agreement_accepted_at->toFormattedDateString()
                    : 'Awaiting acceptance',
            ],
            [
                'key' => 'payment',
                'label' => 'Payment',
                'state' => $closed ? 'upcoming' : ($pending > 0 ? 'current' : ($paid > 0 ? 'done' : 'upcoming')),
                'meta' => $pending > 0
                    ? '₱'.number_format($pending, 2).' under review'
                    : '₱'.number_format($paid, 2).' paid of ₱'.number_format($reservation->grand_total, 2),
            ],
            [
                'key' => 'pickup',
                'label' => 'Pickup',
                'state' => $release ? 'done' : 'upcoming',
                'meta' => $release?->release_date
                    ? 'Collected '.$release->release_date->toFormattedDateString()
                    : 'Scheduled '.($reservation->pickup_date?->toFormattedDateString() ?? '—'),
            ],
            [
                'key' => 'return',
                'label' => 'Return',
                'state' => $returned?->actual_return_date ? 'done' : ($release ? 'current' : 'upcoming'),
                'meta' => $returned?->actual_return_date
                    ? 'Returned '.$returned->actual_return_date->toFormattedDateString()
                    : 'Due '.($reservation->return_date?->toFormattedDateString() ?? '—'),
            ],
            [
                'key' => 'inspection',
                'label' => 'Inspection',
                'state' => ! $returned ? 'upcoming' : ($reservation->status === 'completed' ? 'done' : 'current'),
                'meta' => $returned
                    ? ($returned->condition_after ? 'Condition: '.$returned->condition_after : 'Awaiting inspection')
                    : 'Happens after return',
            ],
            [
                'key' => 'id',
                'label' => 'Government ID',
                'state' => match ($reservation->collateral_status) {
                    'held' => 'current',
                    'released' => 'done',
                    default => 'upcoming',
                },
                'meta' => match ($reservation->collateral_status) {
                    'held' => 'Held securely until the gown is returned',
                    'released' => 'Returned to the customer',
                    default => 'Bring a valid government-issued ID at pickup',
                },
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'state' => $reservation->status === 'completed' ? 'done' : 'upcoming',
                'meta' => $reservation->status === 'completed' ? 'Gown returned and account settled' : 'After the gown is returned and inspected',
            ],
        ];
    }
}

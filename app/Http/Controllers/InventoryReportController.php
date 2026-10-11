<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\Gown;
use App\Models\GownPurchase;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Inventory reporting modules: damage reports and gown purchases.
 * These surface the records already captured during rental returns so the
 * owner has a dedicated place to review them instead of leaving the data
 * buried inside individual reservations.
 */
class InventoryReportController extends Controller
{
    /** List every recorded damage report with its gown and repair cost. */
    public function damages(Request $request)
    {
        return $this->damageListing($request, false);
    }

    public function archivedDamages(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);

        return $this->damageListing($request, true);
    }

    private function damageListing(Request $request, bool $archived)
    {
        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;
        $query = DamageReport::with(['gown', 'reservation.customer', 'gownReturn.reservation.customer'])
            ->when($archived, fn($query) => $query->whereNotNull('archived_at'))
            ->when(!$archived, fn($query) => $query->whereNull('archived_at'))
            ->latest();

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(function ($builder) use ($term) {
                $builder->where('damage_type', 'like', "%$term%")
                    ->orWhere('description', 'like', "%$term%")
                    ->orWhereHas('gown', fn($gown) => $gown->where('name', 'like', "%$term%"))
                    ->orWhereHas('reservation', fn($reservation) => $reservation->where('reservation_code', 'like', "%$term%"))
                    ->orWhereHas('gownReturn.reservation', fn($reservation) => $reservation->where('reservation_code', 'like', "%$term%"));
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        $baseQuery = clone $query;
        $totalFinalCost = (float) (clone $query)->sum(DB::raw('COALESCE(final_repair_cost, repair_cost)'));

        return view('management.damages', [
            'damages' => $query->paginate($perPage)->withQueryString(),
            'totalCount' => (clone $baseQuery)->count(),
            'totalFinalCost' => $totalFinalCost,
            'reservations' => $archived ? collect() : Reservation::with('customer')
                ->whereHas('items')
                ->latest()
                ->limit(200)
                ->get(['id', 'reservation_code', 'customer_id', 'status']),
            'base' => $request->user()->role,
            'archived' => $archived,
        ]);
    }

    public function damageGowns(Request $request)
    {
        return $this->gownOptions($request, 'damage');
    }

    public function purchaseGowns(Request $request)
    {
        return $this->gownOptions($request, 'purchase');
    }

    private function gownOptions(Request $request, string $purpose)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'selected_id' => ['nullable', 'integer', 'exists:gowns,id'],
        ]);

        $eligibleGowns = Gown::whereNull('archived_at')
            ->when($purpose === 'damage', fn($query) => $query->where('status', '!=', 'retired'));
        $query = (clone $eligibleGowns)
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(fn($search) => $search
                    ->where('name', 'like', '%' . $term . '%')
                    ->orWhere('gown_code', 'like', '%' . $term . '%'));
            })
            ->orderBy('name');
        $gowns = $query->paginate(5)->withQueryString();
        $selected = isset($filters['selected_id'])
            ? (clone $eligibleGowns)->find($filters['selected_id'])
            : null;

        return response()->json([
            'data' => $gowns->getCollection()->map(fn(Gown $gown) => [
                'id' => $gown->id,
                'gown_code' => $gown->gown_code,
                'name' => $gown->name,
                'status' => $gown->status,
                'image_url' => $gown->image_url,
            ]),
            'current_page' => $gowns->currentPage(),
            'last_page' => $gowns->lastPage(),
            'total' => $gowns->total(),
            'selected' => $selected ? [
                'id' => $selected->id,
                'gown_code' => $selected->gown_code,
                'name' => $selected->name,
                'status' => $selected->status,
                'image_url' => $selected->image_url,
            ] : null,
        ]);
    }

    public function storeDamage(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);

        $data = $request->validate([
            'gown_id' => ['required', 'integer', Rule::exists('gowns', 'id')->whereNull('archived_at')],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
            'description' => ['required', 'string', 'max:5000'],
            'severity' => ['required', Rule::in(['minor', 'moderate', 'major'])],
            'discovered_at' => ['required', 'date', 'before_or_equal:today'],
            'estimated_repair_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mark_unavailable' => ['sometimes', 'boolean'],
        ]);

        $gown = Gown::findOrFail($data['gown_id']);
        if ($gown->status === 'retired') {
            throw ValidationException::withMessages([
                'gown_id' => 'A retired gown cannot receive a new damage report.',
            ]);
        }

        if (! empty($data['reservation_id']) && ! $gown->reservationItems()
            ->where('reservation_id', $data['reservation_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'reservation_id' => 'The selected rental does not include this gown.',
            ]);
        }

        $photoPath = $request->hasFile('photo')
            ? app(\App\Services\VercelBlobStorage::class)->store($request->file('photo'), 'damage-evidence', 'private')
            : null;

        DB::transaction(function () use ($request, $data, $gown, $photoPath) {
            DamageReport::create([
                'gown_id' => $gown->id,
                'reservation_id' => $data['reservation_id'] ?? null,
                'gown_return_id' => ! empty($data['reservation_id'])
                    ? \App\Models\GownReturn::where('reservation_id', $data['reservation_id'])->value('id')
                    : null,
                'damage_type' => 'Manual damage report',
                'description' => $data['description'],
                'severity' => $data['severity'],
                'discovered_at' => $data['discovered_at'],
                'estimated_repair_cost' => $data['estimated_repair_cost'] ?? null,
                'final_repair_cost' => null,
                'repair_cost' => 0,
                'photos' => $photoPath,
            ]);

            if ($request->boolean('mark_unavailable')) {
                $gown->update([
                    'condition' => 'damaged',
                    'status' => 'damaged',
                ]);
            }
        });

        return redirect()->route($request->user()->role . '.damages')
            ->with('success', 'Damage report saved to the care log.');
    }

    public function updateDamageFinalCost(Request $request, DamageReport $damage)
    {
        abort_unless($request->user()->role === 'owner', 403);

        $data = $request->validate([
            'final_repair_cost' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $damage->update([
            'final_repair_cost' => $data['final_repair_cost'],
            'repair_cost' => $data['final_repair_cost'],
        ]);

        return back()->with('success', 'Final repair cost updated.');
    }

    public function damagePhoto(Request $request, DamageReport $damage)
    {
        abort_unless($request->user()->role === 'owner', 403);

        return app(\App\Services\VercelBlobStorage::class)->privateFileResponse($damage->photos);
    }

    public function archiveDamage(Request $request, DamageReport $damage)
    {
        abort_unless($request->user()->role === 'owner', 403, 'Only the owner can archive damage reports.');

        $damage->update(['archived_at' => now()]);

        return back()->with('success', 'Damage report archived.');
    }

    public function restoreDamage(Request $request, DamageReport $damage)
    {
        abort_unless($request->user()->role === 'owner', 403, 'Only the owner can restore damage reports.');

        $damage->update(['archived_at' => null]);

        return back()->with('success', 'Damage report restored to the active log.');
    }

    /** List every gown a customer has purchased outright. */
    public function purchases(Request $request)
    {
        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;
        $query = GownPurchase::with(['gown.category', 'reservation.customer', 'processedBy'])
            ->latest('purchased_at');

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(function ($builder) use ($term) {
                $builder->whereHas('gown', fn($gown) => $gown->where('name', 'like', "%$term%")
                    ->orWhere('gown_code', 'like', "%$term%"))
                    ->orWhereHas('reservation.customer', fn($customer) => $customer->where('full_name', 'like', "%$term%"))
                    ->orWhereHas('reservation', fn($reservation) => $reservation->where('reservation_code', 'like', "%$term%"));
            });
        }

        $totalSales = (float) (clone $query)->sum('amount');

        return view('management.purchases', [
            'purchases' => $query->paginate($perPage)->withQueryString(),
            'totalCount' => (clone $query)->count(),
            'totalSales' => $totalSales,
            'base' => $request->user()->role,
        ]);
    }

    /**
     * Record a purchase manually, without going through a rental return form.
     * The gown is retired from the rentable collection right away.
     */
    public function storePurchase(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['owner', 'employee'], true), 403);

        $data = $request->validate([
            'gown_id' => ['required', 'exists:gowns,id'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'purchased_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $gown = Gown::whereKey($data['gown_id'])->lockForUpdate()->firstOrFail();
            $activeRental = Reservation::whereIn('status', ['pending', 'awaiting_payment', 'confirmed', 'ready_for_pickup', 'released', 'overdue'])
                ->whereHas('items', fn ($items) => $items->where('gown_id', $gown->id))
                ->exists();
            abort_if($activeRental, 422, 'This gown is linked to an active reservation and cannot be sold yet.');

            $reservationId = $data['reservation_id'] ?? null;
            $gownReturn = $reservationId
                ? \App\Models\GownReturn::where('reservation_id', $reservationId)->first()
                : null;

            GownPurchase::create([
                'reservation_id' => $reservationId,
                'gown_return_id' => $gownReturn?->id,
                'gown_id' => $gown->id,
                'processed_by' => $request->user()->id,
                'amount' => $data['amount'],
                'purchased_at' => !empty($data['purchased_at']) ? \Illuminate\Support\Carbon::parse($data['purchased_at']) : now(),
            ]);

            $gown->update(['status' => 'retired']);
        });

        return back()->with('success', 'Purchase recorded and the gown removed from the collection.');
    }
}

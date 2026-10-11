<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Accessory;
use App\Models\DamageReport;
use App\Models\Gown;
use App\Services\VercelBlobStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GownController extends Controller
{
    public function index(Request $request)
    {
        $query = Gown::with(['category', 'accessories'])->withCount([
            'reservationItems as bookings_count' => fn($items) => $items->whereHas(
                'reservation',
                fn($reservations) => $reservations->whereNotIn('status', ['cancelled', 'rejected'])
            ),
        ])->latest();
        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }
        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(fn($builder) => $builder->where('name', 'like', "%$term%")
                ->orWhere('gown_code', 'like', "%$term%")
                ->orWhere('color', 'like', "%$term%"));
        }
        if ($request->filled('category'))
            $query->where('category_id', $request->integer('category'));
        if ($request->filled('status'))
            $query->where('status', $request->string('status'));
        if ($request->filled('condition'))
            $query->where('condition', $request->string('condition'));
        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;
        $gowns = $query->paginate($perPage)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $accessories = Accessory::where('status', 'available')->orderBy('name')->get();
        $inventoryStats = [
            'total' => Gown::count(),
            'available' => Gown::where('status', 'available')->count(),
            'maintenance' => Gown::whereIn('status', ['for_cleaning', 'under_maintenance', 'damaged'])->count(),
        ];

        return view('gowns.index', compact('gowns', 'categories', 'accessories', 'inventoryStats'));
    }

    /** Dedicated archive module: every archived gown, with restore actions. */
    public function archiveIndex(Request $request)
    {
        $query = Gown::with('category')
            ->whereNotNull('archived_at')
            ->latest('archived_at');

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(fn($builder) => $builder->where('name', 'like', "%$term%")
                ->orWhere('gown_code', 'like', "%$term%")
                ->orWhere('color', 'like', "%$term%"));
        }
        if ($request->filled('category'))
            $query->where('category_id', $request->integer('category'));

        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;
        $gowns = $query->paginate($perPage)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $archivedDamageReports = DamageReport::with('gown')
            ->whereNotNull('archived_at')
            ->latest('archived_at')
            ->paginate(12, ['*'], 'damage_page')
            ->withQueryString();

        return view('gowns.archive', compact('gowns', 'categories', 'archivedDamageReports'));
    }

    public function archive(Gown $gown)
    {
        abort_if($gown->status === 'reserved', 422, 'A reserved gown cannot be archived.');
        abort_if($gown->archived_at, 422, 'This gown is already archived.');

        $gown->update(['archived_at' => now(), 'archived_status' => $gown->status]);

        return redirect()->route('owner.gowns.index', ['archived' => 1])->with('success', 'Gown archived. You can restore it anytime from this archive.');
    }

    public function restore(Gown $gown)
    {
        abort_unless($gown->archived_at, 422, 'This gown is not archived.');

        $gown->update([
            'status' => $gown->archived_status ?: 'available',
            'archived_at' => null,
            'archived_status' => null,
        ]);

        return redirect()->route('owner.gowns.index')->with('success', 'Gown restored to active inventory.');
    }


    public function create()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $accessories = Accessory::where('status', 'available')->orderBy('name')->get();
        return view('gowns.create', compact('categories', 'accessories'));
    }


    private function generateGownCode(): string
    {
        $lastGown = Gown::orderByDesc('id')->first();

        if (!$lastGown) {
            return 'GWN-0001';
        }

        $lastNumber = (int) str_replace(
            'GWN-',
            '',
            $lastGown->gown_code
        );

        $nextNumber = $lastNumber + 1;

        return 'GWN-' . str_pad(
            $nextNumber,
            4,
            '0',
            STR_PAD_LEFT
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'size' => [
                'required',
                'string',
                'max:50'
            ],

            'color' => [
                'required',
                'string',
                'max:100'
            ],

            'style' => [
                'nullable',
                'string',
                'max:255'
            ],

            'rental_price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'security_deposit' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'measurements' => [
                'nullable',
                'string'
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096'
            ],

            'condition' => [
                'required',
                Rule::in([
                    'excellent',
                    'good',
                    'fair',
                    'damaged'
                ])
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'for_cleaning',
                    'under_maintenance',
                    'damaged',
                    'unavailable',
                    'retired'
                ])
            ],

            'date_purchased' => [
                'nullable',
                'date'
            ],

            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['integer', 'exists:accessories,id'],
        ]);


        $validated['gown_code'] =
            $this->generateGownCode();
        $accessoryIds = $validated['accessories'] ?? [];
        unset($validated['accessories']);


        if ($request->hasFile('image')) {
            $validated['image'] = app(VercelBlobStorage::class)
                ->store($request->file('image'), 'gowns', 'public');
        }


        $validated['security_deposit'] = 0;
        if ($validated['status'] === 'damaged') {
            $validated['condition'] = 'damaged';
        }
        $gown = Gown::create($validated);
        DamageReport::ensureForDamagedGown($gown);
        $gown->accessories()->sync(collect($accessoryIds)->mapWithKeys(fn($id) => [$id => ['quantity' => 1]])->all());


        return redirect()
            ->route('owner.gowns.index')
            ->with(
                'success',
                'Gown added successfully.'
            );
    }


    public function show(Gown $gown)
    {
        $gown->load([
            'category',
            'accessories',
            'reservationItems',
        ]);

        return view(
            'gowns.show',
            compact('gown')
        );
    }


    public function edit(Gown $gown)
    {
        $categories = Category::where(fn($q) => $q->where('is_active', true)->orWhere('id', $gown->category_id))
            ->get();

        $accessories = Accessory::where('status', 'available')
            ->orWhereHas('gowns', fn($query) => $query->where('gowns.id', $gown->id))
            ->orderBy('name')->get();
        return view(
            'gowns.edit',
            compact(
                'gown',
                'categories',
                'accessories'
            )
        );
    }


    public function update(
        Request $request,
        Gown $gown
    ) {
        $wasArchived = (bool) $gown->archived_at;
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'size' => [
                'required',
                'string',
                'max:50'
            ],

            'color' => [
                'required',
                'string',
                'max:100'
            ],

            'style' => [
                'nullable',
                'string',
                'max:255'
            ],

            'rental_price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'security_deposit' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'measurements' => [
                'nullable',
                'string'
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096'
            ],

            'condition' => [
                'required',
                Rule::in([
                    'excellent',
                    'good',
                    'fair',
                    'damaged'
                ])
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'for_cleaning',
                    'under_maintenance',
                    'damaged',
                    'unavailable',
                    'retired'
                ])
            ],

            'date_purchased' => [
                'nullable',
                'date'
            ],

            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['integer', 'exists:accessories,id'],
        ]);
        $accessoryIds = $validated['accessories'] ?? [];
        unset($validated['accessories']);


        $previousImage = $gown->image;
        if ($request->hasFile('image')) {
            $validated['image'] = app(VercelBlobStorage::class)
                ->store($request->file('image'), 'gowns', 'public');
        }


        $validated['security_deposit'] = 0;
        if ($validated['status'] === 'damaged') {
            $validated['condition'] = 'damaged';
        }
        $gown->update($validated);
        DamageReport::ensureForDamagedGown($gown);
        if ($request->hasFile('image') && $previousImage) {
            app(VercelBlobStorage::class)->delete($previousImage, 'public');
        }
        $gown->accessories()->sync(collect($accessoryIds)->mapWithKeys(fn($id) => [$id => ['quantity' => 1]])->all());


        return redirect()
            ->route('owner.gowns.index', $wasArchived ? ['archived' => 1] : [])
            ->with(
                'success',
                'Gown updated successfully.'
            );
    }


    public function destroy(Gown $gown)
    {
        $gown->update([
            'status' => 'unavailable',
        ]);


        return redirect()
            ->route('owner.gowns.index')
            ->with(
                'success',
                'Gown marked unavailable successfully.'
            );
    }
}

<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">COLLECTION CARE</span>

                    <h1>Gown <em>maintenance.</em></h1>

                    <p>Log repairs and inspections, then return ready pieces to the collection.</p>
                </div>
            </div>


            {{-- SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="sb-success">
                    {{ session('success') }}
                </div>
            @endif


            {{-- MAINTENANCE PANEL --}}
            <section class="sb-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER: title left, Log button right --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">MAINTENANCE LOG</span>

                            <h2>All jobs</h2>

                            <p>
                                {{ $maintenanceRecords->total() }}
                                recorded {{ \Illuminate\Support\Str::plural('job', $maintenanceRecords->total()) }}
                            </p>
                        </div>

                        <div class="sb-inventory-toolbar-actions">
                            <button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                                onclick="document.getElementById('maintenance-create-dialog').showModal()">
                                <span aria-hidden="true">＋</span>
                                Log maintenance
                            </button>
                        </div>

                    </div>


                    {{-- TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-maintenance-table">

                            <thead>
                                <tr>
                                    <th>Gown</th>
                                    <th>Work</th>
                                    <th>Date</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($maintenanceRecords as $record)

                                    <tr class="sb-record-row" tabindex="0" aria-haspopup="dialog"
                                        aria-label="View maintenance job for {{ $record->gown->name ?? 'removed gown' }}"
                                        data-record-title="{{ $record->gown->name ?? 'Maintenance job' }}"
                                        data-record-image="{{ $record->gown?->image_url ?? '' }}"
                                        data-record-details="{{ json_encode([
                                            'Gown code' => $record->gown->gown_code ?? 'Removed gown',
                                            'Work type' => ucfirst($record->maintenance_type),
                                            'Description' => $record->description ?: 'No description recorded',
                                            'Notes' => $record->notes ?: 'No additional notes',
                                            'Date' => $record->maintenance_date?->format('M d, Y') ?? '—',
                                            'Cost' => '₱' . number_format($record->cost, 2),
                                            'Status' => $record->status === 'pending' ? 'In progress' : ucfirst($record->status),
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">

                                        {{-- GOWN (image + name) --}}
                                        <td data-label="Gown">

                                            <div class="sb-inventory-name">

                                                <div class="sb-inventory-thumb">
                                                    @if($record->gown && $record->gown->image)
                                                        <img src="{{ $record->gown->image_url }}"
                                                            alt="{{ $record->gown->name }}" loading="lazy">
                                                    @else
                                                        <span aria-hidden="true">✿</span>
                                                    @endif
                                                </div>

                                                <span>
                                                    <b>{{ $record->gown->name ?? 'Removed gown' }}</b>
                                                    <small>{{ $record->gown->gown_code ?? '' }}</small>
                                                </span>

                                            </div>

                                        </td>


                                        {{-- WORK --}}
                                        <td data-label="Work" class="sb-category-desc">
                                            <b class="sb-work-type">{{ $record->maintenance_type }}</b>
                                            <span>{{ $record->description }}@if($record->notes) ·
                                            {{ $record->notes }}@endif</span>
                                        </td>


                                        {{-- DATE --}}
                                        <td data-label="Date">
                                            {{ $record->maintenance_date?->format('M d, Y') }}
                                        </td>


                                        {{-- COST --}}
                                        <td data-label="Cost">
                                            <strong class="sb-rental-price">
                                                ₱{{ number_format($record->cost, 2) }}
                                            </strong>
                                        </td>


                                        {{-- STATUS --}}
                                        <td data-label="Status">
                                            <span
                                                class="sb-inventory-status sb-gown-status {{ $record->status === 'pending' ? 'is-reserved' : 'is-available' }}">
                                                {{ $record->status === 'pending' ? 'In progress' : ucfirst($record->status) }}
                                            </span>
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td data-label="Actions">

                                            @if($record->status === 'pending')

                                                <button class="sb-action-success sb-maintenance-complete-trigger" type="button"
                                                    data-open-maintenance-dialog="#complete-maintenance-{{ $record->id }}">
                                                    Complete job
                                                </button>

                                                <dialog class="sb-maintenance-complete-dialog"
                                                    id="complete-maintenance-{{ $record->id }}"
                                                    aria-labelledby="complete-maintenance-title-{{ $record->id }}">
                                                    <div class="sb-maintenance-complete-head">
                                                        <div>
                                                            <span class="sb-kicker">COLLECTION CARE</span>
                                                            <h2 id="complete-maintenance-title-{{ $record->id }}">Complete maintenance job</h2>
                                                            <p>{{ $record->gown->name ?? 'Gown' }} · {{ ucfirst($record->maintenance_type) }}</p>
                                                        </div>
                                                        <button class="sb-maintenance-dialog-close" type="button"
                                                            aria-label="Close dialog" data-close-maintenance-dialog>&times;</button>
                                                    </div>

                                                    <form method="POST"
                                                        action="{{ route($base . '.maintenance.complete', $record) }}"
                                                        class="sb-maintenance-complete-form">
                                                        @csrf
                                                        <label>Final condition
                                                            <select name="condition" required>
                                                                @foreach(['excellent', 'good', 'fair', 'damaged'] as $condition)
                                                                    <option value="{{ $condition }}"
                                                                        @selected(($record->gown->condition ?? 'good') === $condition)>
                                                                        {{ ucfirst($condition) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </label>

                                                        <label>Completion note
                                                            <input name="completion_notes"
                                                                placeholder="Optional repair outcome">
                                                        </label>

                                                        <button class="sb-action-success" type="submit">Close maintenance job</button>
                                                    </form>
                                                </dialog>

                                            @else

                                                <span class="sb-maintenance-done">Completed</span>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="6">
                                            <div class="sb-inventory-empty">
                                                <b>No maintenance jobs yet</b>
                                                <p>Log a repair or inspection to keep track of each gown's care.</p>
                                            </div>
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    @include('components.record-details-dialog')

                    {{-- PAGINATION --}}
                    @include('components.table-pagination', ['paginator' => $maintenanceRecords, 'itemLabel' => 'jobs'])

                </div>

            </section>


            {{-- LOG MAINTENANCE DRAWER --}}
            <dialog class="sb-side-drawer sb-inventory-modal" id="maintenance-create-dialog" aria-labelledby="maintenance-create-title"
                onclick="if (event.target === this) this.close()">

                <div class="sb-side-drawer-head">

                    <div>
                        <span class="sb-kicker">COLLECTION CARE</span>

                        <h2 id="maintenance-create-title">Log maintenance</h2>

                        <p>The selected gown will be removed from the rentable collection until its work is complete.
                        </p>
                    </div>

                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('maintenance-create-dialog').close()">
                        &times;
                    </button>

                </div>


                <form method="POST" action="{{ route($base . '.maintenance.store') }}" class="sb-side-drawer-form">

                    @csrf

                    <input type="hidden" name="_inventory_drawer" value="maintenance">

                    @if($errors->any() && old('_inventory_drawer') === 'maintenance')
                        <div class="sb-form-errors">{{ $errors->first() }}</div>
                    @endif

                    <x-gown-picker
                        name="gown_id"
                        :endpoint="route($base . '.maintenance.gowns')"
                    />

                    <label>Work type
                        <input name="maintenance_type" value="{{ old('maintenance_type') }}"
                            placeholder="Alteration, repair, inspection" required>
                        <x-input-error :messages="$errors->get('maintenance_type')" />
                    </label>

                    <label>Description
                        <textarea name="description" rows="3" required>{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" />
                    </label>

                    <label>Work date
                        <input type="date" name="maintenance_date"
                            value="{{ old('maintenance_date', today()->format('Y-m-d')) }}"
                            max="{{ today()->format('Y-m-d') }}" required>
                        <x-input-error :messages="$errors->get('maintenance_date')" />
                    </label>

                    <label>Cost (PHP)
                        <input type="number" name="cost" min="0" max="1000000" step="0.01" value="{{ old('cost', 0) }}">
                        <x-input-error :messages="$errors->get('cost')" />
                    </label>

                    <label>Notes
                        <input name="notes" value="{{ old('notes') }}" placeholder="Optional tracking note">
                        <x-input-error :messages="$errors->get('notes')" />
                    </label>

                    <div class="sb-side-drawer-actions">
                        <button class="sb-side-drawer-cancel" type="button"
                            onclick="document.getElementById('maintenance-create-dialog').close()">
                            Cancel
                        </button>

                        <button class="sb-inventory-primary" type="submit">Save maintenance record</button>
                    </div>

                </form>

            </dialog>


            @if(old('_inventory_drawer') === 'maintenance')
                <script>
                    document.getElementById('maintenance-create-dialog').showModal();
                </script>
            @endif

        </div>
    </div>

</x-app-layout>
<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · STYLING PIECES</span>

                    <h1>Gown <em>accessories.</em></h1>

                    <p>Track the smaller pieces that complete a rental look.</p>
                </div>
            </div>


            {{-- SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="sb-success">
                    {{ session('success') }}
                </div>
            @endif


            {{-- ACCESSORIES PANEL --}}
            <section class="sb-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER: title left, Add button right --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">ACCESSORY STOCK</span>

                            <h2>All accessories</h2>

                            <p>
                                {{ $accessories->total() }}
                                {{ \Illuminate\Support\Str::plural('accessory', $accessories->total()) }}
                            </p>
                        </div>

                        <div class="sb-inventory-toolbar-actions">
                            <button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                                onclick="document.getElementById('accessory-create-dialog').showModal()">
                                <span aria-hidden="true">＋</span>
                                Add accessory
                            </button>
                        </div>

                    </div>


                    {{-- TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-accessory-table">

                            <thead>
                                <tr>
                                    <th>Accessory</th>
                                    <th>Stock</th>
                                    <th>Linked gowns</th>
                                    <th>Replacement cost</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($accessories as $accessory)

                                    @php
                                        $statusClass = match ($accessory->status) {
                                            'available' => 'is-available',
                                            'damaged', 'unavailable' => 'is-damaged',
                                            default => 'is-disabled',
                                        };
                                    @endphp

                                    <tr class="sb-clickable-row"
                                        data-href="{{ route('owner.accessories.show', $accessory) }}"
                                        tabindex="0" role="link"
                                        aria-label="View accessory {{ $accessory->name }}">

                                        {{-- ACCESSORY (image + name) --}}
                                        <td data-label="Accessory">

                                            <div class="sb-inventory-name">

                                                <div class="sb-inventory-thumb">
                                                    @if($accessory->image_url)
                                                        <img src="{{ $accessory->image_url }}"
                                                            alt="{{ $accessory->name }}" loading="lazy">
                                                    @else
                                                        <span aria-hidden="true">✧</span>
                                                    @endif
                                                </div>

                                                <span>
                                                    <b>{{ $accessory->name }}</b>
                                                    <small>{{ $accessory->description ?: 'No description added' }}</small>
                                                </span>

                                            </div>

                                        </td>


                                        {{-- STOCK --}}
                                        <td data-label="Stock">
                                            <span
                                                class="sb-booking-count {{ $accessory->quantity <= 2 ? 'sb-stock-low' : '' }}">
                                                {{ $accessory->quantity }}
                                            </span>
                                        </td>


                                        {{-- LINKED GOWNS --}}
                                        <td data-label="Linked gowns">
                                            <span class="sb-booking-count">{{ $accessory->gowns_count }}</span>
                                        </td>


                                        {{-- REPLACEMENT COST --}}
                                        <td data-label="Replacement cost">
                                            <strong class="sb-rental-price">
                                                ₱{{ number_format($accessory->replacement_cost, 2) }}
                                            </strong>
                                        </td>


                                        {{-- STATUS --}}
                                        <td data-label="Status">
                                            <span class="sb-inventory-status sb-gown-status {{ $statusClass }}">
                                                {{ ucfirst($accessory->status) }}
                                            </span>
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td data-label="Actions">

                                            <div class="sb-inventory-actions">

                                                <a href="{{ route('owner.accessories.edit', $accessory) }}"
                                                    class="sb-action-edit">Edit</a>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="6">
                                            <div class="sb-inventory-empty">

                                                <b>No accessories added</b>

                                                <p>Add included styling pieces and link them to gowns when editing a gown.
                                                </p>

                                            </div>
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    @include('components.table-pagination', ['paginator' => $accessories, 'itemLabel' => 'accessories'])

                </div>

            </section>


            {{-- ADD ACCESSORY DRAWER --}}
            <dialog class="sb-side-drawer sb-inventory-modal" id="accessory-create-dialog" aria-labelledby="accessory-create-title"
                onclick="if (event.target === this) this.close()">

                <div class="sb-side-drawer-head">

                    <div>
                        <span class="sb-kicker">INVENTORY · STYLING PIECES</span>

                        <h2 id="accessory-create-title">Add an accessory</h2>

                        <p>Record stock, availability, and replacement cost.</p>
                    </div>

                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('accessory-create-dialog').close()">
                        &times;
                    </button>

                </div>


                <form method="POST" action="{{ route('owner.accessories.store') }}" enctype="multipart/form-data"
                    class="sb-side-drawer-form">

                    @csrf

                    <input type="hidden" name="_inventory_drawer" value="accessory">

                    @if($errors->any() && old('_inventory_drawer') === 'accessory')
                        <div class="sb-form-errors">{{ $errors->first() }}</div>
                    @endif

                    <label>Item name
                        <input name="name" value="{{ old('name') }}" maxlength="255" required>
                        <x-input-error :messages="$errors->get('name')" />
                    </label>

                    <label>Description
                        <textarea name="description" rows="3">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" />
                    </label>

                    <label>Accessory image
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
                        <img id="accessory-create-preview" class="sb-image-preview" alt="Image preview" hidden>
                        <small class="sb-field-hint">JPG, PNG or WEBP, up to 2 MB.</small>
                        <x-input-error :messages="$errors->get('image')" />
                    </label>

                    <label>Quantity in stock
                        <input type="number" name="quantity" min="0" value="{{ old('quantity', 1) }}" required>
                        <x-input-error :messages="$errors->get('quantity')" />
                    </label>

                    <label>Replacement cost (PHP)
                        <input type="number" name="replacement_cost" min="0" step="0.01"
                            value="{{ old('replacement_cost', 0) }}">
                        <x-input-error :messages="$errors->get('replacement_cost')" />
                    </label>

                    <label>Status
                        <select name="status" required>
                            @foreach(['available', 'unavailable', 'damaged', 'retired'] as $status)
                                <option value="{{ $status }}" @selected(old('status', 'available') === $status)>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" />
                    </label>

                    <div class="sb-side-drawer-actions">
                        <button class="sb-side-drawer-cancel" type="button"
                            onclick="document.getElementById('accessory-create-dialog').close()">
                            Cancel
                        </button>

                        <button class="sb-inventory-primary" type="submit">Create accessory</button>
                    </div>

                </form>

            </dialog>


            {{-- REOPEN DRAWER AFTER VALIDATION ERROR --}}
            @if(old('_inventory_drawer') === 'accessory')
                <script>
                    document.getElementById('accessory-create-dialog').showModal();
                </script>
            @endif

        </div>
    </div>

</x-app-layout>
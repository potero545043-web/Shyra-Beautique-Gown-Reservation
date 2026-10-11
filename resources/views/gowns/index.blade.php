<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · GOWNS</span>

                    <h1>Your gown <em>collection.</em></h1>

                    <p>Manage rentable pieces, their care, pricing, and included accessories.</p>
                </div>
            </div>


            {{-- SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="sb-success">
                    {{ session('success') }}
                </div>
            @endif


            {{-- INVENTORY STATS --}}
            <div class="sb-stats sb-inventory-stats">

                <article class="sb-stat">
                    <span>Total gowns</span>
                    <b>{{ $inventoryStats['total'] }}</b>
                    <small>All inventory</small>
                </article>

                <article class="sb-stat">
                    <span>Available</span>
                    <b>{{ $inventoryStats['available'] }}</b>
                    <small>Ready to reserve</small>
                </article>

                <article class="sb-stat">
                    <span>Maintenance</span>
                    <b>{{ $inventoryStats['maintenance'] }}</b>
                    <small>Cleaning, repair, or damage</small>
                </article>

            </div>


            {{-- INVENTORY PANEL --}}
            <section class="sb-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER: title left, Add button right --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">GOWN INVENTORY</span>

                            <h2>{{ request()->boolean('archived') ? 'Archived gowns' : 'All gowns' }}</h2>

                            <p>
                                {{ $gowns->total() }}
                                {{ \Illuminate\Support\Str::plural('gown', $gowns->total()) }}
                                in this view
                            </p>
                        </div>

                        @if(request()->boolean('archived'))
                            <a class="sb-inventory-secondary" href="{{ route('owner.gowns.index') }}">
                                Active inventory
                            </a>
                        @endif
                        @unless(request()->boolean('archived'))<button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                            onclick="document.getElementById('gown-create-dialog').showModal()">
                            <span aria-hidden="true">＋</span>
                            Add a gown
                        </button>@endunless

                    </div>


                    {{-- FILTERS --}}
                    <form method="GET" class="sb-inventory-filter" data-live-filter>
                        @if(request()->boolean('archived'))<input type="hidden" name="archived" value="1">@endif
                        <input type="hidden" name="per_page" value="{{ $gowns->perPage() }}">

                        <div class="sb-inventory-search">

                            <input type="search" name="q" value="{{ request('q') }}"
                                placeholder="Search gowns, code, or color" aria-label="Search gowns, code, or color">

                            <button class="sb-inventory-filter-submit" type="submit" aria-label="Search gowns">
                                Search
                            </button>
                        </div>


                        <select name="category" aria-label="Filter by category" onchange="this.form.requestSubmit()">

                            <option value="">All categories</option>

                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach

                        </select>


                        <select name="status" aria-label="Filter by availability" onchange="this.form.requestSubmit()">

                            <option value="">All statuses</option>

                            @foreach([
                                    'available',
                                    'reserved',
                                    'for_cleaning',
                                    'under_maintenance',
                                    'damaged',
                                    'unavailable',
                                    'retired'
                                ] as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach

                        </select>


                        <select name="condition" aria-label="Filter by condition" onchange="this.form.requestSubmit()">

                            <option value="">All conditions</option>

                            @foreach([
                                    'excellent',
                                    'good',
                                    'fair',
                                    'damaged'
                                ] as $condition)
                                <option value="{{ $condition }}" @selected(request('condition') === $condition)>
                                    {{ ucfirst($condition) }}
                                </option>
                            @endforeach

                        </select>

                    </form>


                    {{-- INVENTORY TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-gown-table">

                            <thead>
                                <tr>
                                    <th>Gown</th>
                                    <th>Category</th>
                                    <th>Size</th>
                                    <th>Rental price</th>
                                    <th>Condition</th>
                                    <th>Availability</th>
                                    <th>Bookings</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($gowns as $gown)

                                    @php
                                        [$availabilityLabel, $availabilityClass] = request()->boolean('archived')
                                            ? ['Archived', 'is-disabled']
                                            : match ($gown->status) {
                                            'available' => ['Available', 'is-available'],
                                            'reserved' => ['Reserved', 'is-reserved'],
                                            'for_cleaning',
                                            'under_maintenance' => ['Maintenance', 'is-maintenance'],
                                            'damaged' => ['Damaged', 'is-damaged'],
                                            'retired' => ['Sold / retired', 'is-disabled'],
                                            default => ['Disabled', 'is-disabled'],
                                        };
                                    @endphp

                                    <tr class="sb-gown-row-link" data-href="{{ route('owner.gowns.show', $gown) }}"
                                        tabindex="0" role="link"
                                        aria-label="View details for {{ $gown->name }}">

                                        {{-- GOWN --}}
                                        <td data-label="Gown">

                                            <div class="sb-inventory-name">

                                                <div class="sb-inventory-thumb">
                                                    @if($gown->image)
                                                        <img src="{{ $gown->image_url }}"
                                                            alt="{{ $gown->name }}" loading="lazy">
                                                    @else
                                                        <span aria-hidden="true">✿</span>
                                                    @endif
                                                </div>

                                                <span>
                                                    <b>{{ $gown->name }}</b>

                                                    <small>
                                                        {{ $gown->gown_code }}
                                                        ·
                                                        {{ ucfirst($gown->color ?: 'Color not set') }}
                                                    </small>
                                                </span>

                                            </div>

                                        </td>


                                        {{-- CATEGORY --}}
                                        <td data-label="Category">
                                            {{ $gown->category->name ?? 'Uncategorized' }}
                                        </td>


                                        {{-- SIZE --}}
                                        <td data-label="Size">
                                            {{ $gown->size ?: '—' }}
                                        </td>


                                        {{-- RENTAL PRICE --}}
                                        <td data-label="Rental price">
                                            <strong class="sb-rental-price">
                                                ₱{{ number_format($gown->rental_price, 2) }}
                                            </strong>
                                        </td>


                                        {{-- CONDITION --}}
                                        <td data-label="Condition">
                                            <span
                                                class="sb-inventory-status is-condition is-condition-{{ \Illuminate\Support\Str::slug($gown->condition) }}">
                                                {{ ucfirst($gown->condition) }}
                                            </span>
                                        </td>


                                        {{-- AVAILABILITY --}}
                                        <td data-label="Availability">
                                            <span class="sb-inventory-status sb-gown-status {{ $availabilityClass }}">
                                                <i aria-hidden="true"></i>
                                                {{ $availabilityLabel }}
                                            </span>
                                        </td>


                                        {{-- BOOKINGS --}}
                                        <td data-label="Bookings">
                                            <span class="sb-booking-count">
                                                {{ $gown->bookings_count }}
                                            </span>
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td data-label="Actions">

                                            <div class="sb-inventory-actions">

                                                <a href="{{ route('owner.gowns.edit', $gown) }}" class="sb-action-edit">
                                                    Edit
                                                </a>

                                                @unless(request()->boolean('archived'))
                                                    <form method="POST" action="{{ route('owner.gowns.archive', $gown) }}"
                                                        data-confirm-title="Archive"
                                                        data-confirm-accent="this gown?"
                                                        data-confirm-message="You can restore this gown later from the Archive module."
                                                        data-confirm-yes="Yes, archive"
                                                        data-confirm-no="Keep gown">
                                                        @csrf
                                                        <button class="sb-action-warning" type="submit"
                                                            @disabled($gown->status === 'reserved')
                                                            title="{{ $gown->status === 'reserved' ? 'Reserved gowns cannot be archived.' : 'Move this gown to the archive.' }}">
                                                            Archive
                                                        </button>
                                                    </form>
                                                @endunless

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="8">

                                            <div class="sb-inventory-empty">

                                                <div class="sb-empty-icon">✿</div>

                                                <b>No gowns found</b>

                                                <p>Try changing your filters or add your first gown.</p>

                                                @unless(request()->boolean('archived'))
                                                    <button type="button" class="sb-inventory-primary"
                                                        onclick="document.getElementById('gown-create-dialog').showModal()">
                                                        Add a gown
                                                    </button>
                                                @endunless

                                            </div>

                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINATION --}}
                    @include('components.table-pagination', ['paginator' => $gowns, 'itemLabel' => 'gowns'])

                </div>

            </section>


            {{-- ADD GOWN DRAWER --}}
            <dialog class="sb-side-drawer sb-inventory-modal" id="gown-create-dialog" aria-labelledby="gown-create-title"
                onclick="if (event.target === this) this.close()">

                <div class="sb-side-drawer-head">

                    <div>
                        <span class="sb-kicker">INVENTORY · GOWNS</span>

                        <h2 id="gown-create-title">Add a gown</h2>

                        <p>Enter the gown details, rental pricing, and included accessories.</p>
                    </div>

                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('gown-create-dialog').close()">
                        &times;
                    </button>

                </div>


                <form method="POST" action="{{ route('owner.gowns.store') }}" enctype="multipart/form-data"
                    class="sb-side-drawer-form">

                    @csrf

                    <input type="hidden" name="_inventory_drawer" value="gown">

                    @if($errors->any() && old('_inventory_drawer') === 'gown')
                        <div class="sb-form-errors">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @include('gowns._fields', [
                        'gown' => null,
                        'isEditing' => false,
                        'inDrawer' => true,
                    ])

                </form>

            </dialog>


            {{-- REOPEN DRAWER AFTER VALIDATION ERROR --}}
            @if(old('_inventory_drawer') === 'gown')
                <script>
                    document.getElementById('gown-create-dialog').showModal();
                </script>
            @endif

        </div>
    </div>

</x-app-layout>

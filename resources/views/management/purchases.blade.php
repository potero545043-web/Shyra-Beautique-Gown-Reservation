<x-app-layout>

    <div class="sb-page sb-inventory-page sb-sales-log-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · SALES LOG</span>

                    <h1>Gown <em>purchases.</em></h1>

                    <p>Every gown a customer bought outright, with the agreed sale price.</p>
                </div>

                <button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                    onclick="document.getElementById('purchase-create-dialog').showModal()">
                    <span aria-hidden="true">＋</span>
                    Record purchase
                </button>
            </div>


            {{-- MESSAGES --}}
            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($errors->any() && old('_inventory_drawer') !== 'purchase')
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif


            {{-- STAT CARDS --}}
            <div class="sb-stat-grid sb-inventory-ledger-stats">
                <article class="sb-stat">
                    <span>Gowns purchased</span>
                    <b>{{ $totalCount }}</b>
                    <small>Sold to customers</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-dress" />
                        </svg></i>
                </article>
                <article class="sb-stat">
                    <span>Total sales</span>
                    <b>&#8369;{{ number_format($totalSales, 2) }}</b>
                    <small>Recorded sale value</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-card" />
                        </svg></i>
                </article>
            </div>


            {{-- PURCHASES PANEL --}}
            <section class="sb-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">SALES LOG</span>

                            <h2>Recorded purchases</h2>

                            <p>
                                {{ $totalCount }}
                                {{ \Illuminate\Support\Str::plural('purchase', $totalCount) }}
                            </p>
                        </div>

                        <form class="sb-filterbar sb-ledger-filter" method="GET" data-live-filter>
                            <input type="hidden" name="per_page" value="{{ $purchases->perPage() }}">
                            <input type="search" name="q" value="{{ request('q') }}"
                                placeholder="Search gown or customer" aria-label="Search gown purchases">
                            <button class="sb-btn" type="submit">Search</button>
                            @if(request()->filled('q'))
                                <a class="sb-filter-clear" href="{{ route($base . '.purchases') }}">Clear</a>
                            @endif
                        </form>

                    </div>


                    {{-- TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-purchase-table">

                            <thead>
                                <tr>
                                    <th>Gown</th>
                                    <th>Customer</th>
                                    <th>Reservation</th>
                                    <th>Sale price</th>
                                    <th>Purchased</th>
                                    <th>Processed by</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($purchases as $purchase)

                                    <tr class="sb-record-row" tabindex="0" aria-haspopup="dialog"
                                        aria-label="View purchase of {{ $purchase->gown->name ?? 'removed gown' }}"
                                        data-record-title="{{ $purchase->gown->name ?? 'Purchase record' }}"
                                        data-record-image="{{ $purchase->gown?->image_url ?? '' }}"
                                        data-record-details="{{ json_encode([
                                            'Gown code' => $purchase->gown->gown_code ?? 'Removed gown',
                                            'Customer' => $purchase->reservation?->customer?->full_name ?? 'Walk-in customer',
                                            'Contact' => $purchase->reservation?->customer?->contact_number ?? 'Not provided',
                                            'Reservation' => $purchase->reservation?->reservation_code ?? 'Direct sale',
                                            'Sale price' => '₱' . number_format($purchase->amount, 2),
                                            'Purchased' => $purchase->purchased_at?->format('M d, Y') ?? '—',
                                            'Processed by' => $purchase->processedBy?->name ?? '—',
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">

                                        {{-- GOWN --}}
                                        <td data-label="Gown">
                                            <div class="sb-inventory-name">
                                                <div class="sb-inventory-thumb">
                                                    @if($purchase->gown && $purchase->gown->image)
                                                        <img src="{{ $purchase->gown->image_url }}"
                                                            alt="{{ $purchase->gown->name }}" loading="lazy">
                                                    @else
                                                        <span aria-hidden="true">✿</span>
                                                    @endif
                                                </div>
                                                <span>
                                                    <b>{{ $purchase->gown->name ?? 'Removed gown' }}</b>
                                                    <small>{{ $purchase->gown->gown_code ?? '' }}</small>
                                                </span>
                                            </div>
                                        </td>


                                        {{-- CUSTOMER --}}
                                        <td data-label="Customer">
                                            <strong>{{ $purchase->reservation->customer->full_name ?? 'Walk-in customer' }}</strong>
                                            <small
                                                class="sb-cell-sub">{{ $purchase->reservation->customer->contact_number ?? '' }}</small>
                                        </td>


                                        {{-- RESERVATION --}}
                                        <td data-label="Reservation">
                                            @if($purchase->reservation)
                                                <strong>{{ $purchase->reservation->reservation_code }}</strong>
                                            @else
                                                <span class="sb-cell-sub">Direct sale</span>
                                            @endif
                                        </td>


                                        {{-- SALE PRICE --}}
                                        <td data-label="Sale price">
                                            <strong class="sb-rental-price">
                                                &#8369;{{ number_format($purchase->amount, 2) }}
                                            </strong>
                                        </td>


                                        {{-- PURCHASED --}}
                                        <td data-label="Purchased">
                                            {{ $purchase->purchased_at?->format('M d, Y') }}
                                        </td>


                                        {{-- PROCESSED BY --}}
                                        <td data-label="Processed by">
                                            {{ $purchase->processedBy->name ?? '—' }}
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="6">
                                            <div class="sb-inventory-empty">
                                                <b>No purchases recorded</b>
                                                <p>When a customer buys a gown after a rental, the sale is listed here.</p>
                                            </div>
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    @include('components.record-details-dialog')

                    {{-- PAGINATION --}}
                    @include('components.table-pagination', ['paginator' => $purchases, 'itemLabel' => 'purchases'])

                </div>

            </section>


            {{-- RECORD PURCHASE DRAWER --}}
            <dialog class="sb-side-drawer sb-inventory-modal" id="purchase-create-dialog" aria-labelledby="purchase-create-title"
                onclick="if (event.target === this) this.close()">

                <div class="sb-side-drawer-head">

                    <div>
                        <span class="sb-kicker">INVENTORY · SALES LOG</span>

                        <h2 id="purchase-create-title">Record a purchase</h2>

                        <p>Log a direct gown sale. The gown is retired from the rentable collection.</p>
                    </div>

                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('purchase-create-dialog').close()">
                        &times;
                    </button>

                </div>

                <form method="POST" action="{{ route($base . '.purchases.store') }}" class="sb-side-drawer-form">

                    @csrf

                    <input type="hidden" name="_inventory_drawer" value="purchase">

                    @if($errors->any() && old('_inventory_drawer') === 'purchase')
                        <div class="sb-form-errors">{{ $errors->first() }}</div>
                    @endif

                    <x-gown-picker
                        name="gown_id"
                        :endpoint="route($base . '.purchases.gowns')"
                    />

                    <label>Sale price (PHP)
                        <input type="number" name="amount" min="0.01" max="1000000" step="0.01"
                            value="{{ old('amount') }}" required>
                        <x-input-error :messages="$errors->get('amount')" />
                    </label>

                    <label>Purchase date
                        <input type="date" name="purchased_at"
                            value="{{ old('purchased_at', today()->format('Y-m-d')) }}"
                            max="{{ today()->format('Y-m-d') }}">
                        <x-input-error :messages="$errors->get('purchased_at')" />
                    </label>

                    <div class="sb-side-drawer-actions">
                        <button class="sb-side-drawer-cancel" type="button"
                            onclick="document.getElementById('purchase-create-dialog').close()">
                            Cancel
                        </button>

                        <button class="sb-inventory-primary" type="submit">Record purchase</button>
                    </div>

                </form>

            </dialog>


            {{-- REOPEN DRAWER AFTER VALIDATION ERROR --}}
            @if(old('_inventory_drawer') === 'purchase')
                <script>
                    document.getElementById('purchase-create-dialog').showModal();
                </script>
            @endif

        </div>
    </div>

</x-app-layout>
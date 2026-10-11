@php
    $role = auth()->user()->role;
    $total = $gowns->count();
    $showRoute = $role === 'customer' ? 'customer.gowns.show' : $role . '.catalog.show';

    // Header action that makes sense for each role (instead of "Back to home").
    [$actionLabel, $actionUrl] = match ($role) {
        'owner' => ['Add gown +', route('owner.gowns.create')],
        'employee' => ['View reservations', route('employee.reservations')],
        default => ['My reservations', route('customer.reservations')],
    };
@endphp

<x-app-layout>
    <div class="sb-page sb-collection-page">
        <div class="sb-wrap">

            {{-- HEADING --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">SHYRA BEAUTIQUE · THE COLLECTION</span>
                    <h1>Browse the <em>collection.</em></h1>
                    <p>View gown sizes, rental prices, and availability.@if($role !== 'customer') Choose a gown to start
                    a reservation.@endif</p>
                </div>
                <a class="sb-btn" href="{{ $actionUrl }}">{{ $actionLabel }}</a>
            </div>

            {{-- FILTERS --}}
            <div class="sb-catalog-tools">
                <input id="gownSearch" type="search" placeholder="Search gowns, styles, or codes..."
                    value="{{ request('search') }}">

                <select id="categoryFilter">
                    <option value="">All categories</option>
                    @foreach($gowns->pluck('category.name')->filter()->unique() as $category)
                        <option value="{{ strtolower($category) }}" @selected(request('category') === strtolower($category))>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>

                <select id="sizeFilter">
                    <option value="">All sizes</option>
                    @foreach($gowns->pluck('size')->filter()->unique()->sort() as $size)
                        <option value="{{ strtolower($size) }}" @selected(request('size') === strtolower($size))>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>

                <select id="styleFilter">
                    <option value="">All styles</option>
                    @foreach($gowns->pluck('style')->filter()->unique()->sort() as $style)
                        <option value="{{ strtolower($style) }}" @selected(request('style') === strtolower($style))>
                            {{ $style }}
                        </option>
                    @endforeach
                </select>

                <select id="colorFilter">
                    <option value="">All colors</option>
                    @foreach($gowns->pluck('color')->filter()->unique()->sort() as $color)
                        <option value="{{ strtolower($color) }}" @selected(request('color') === strtolower($color))>
                            {{ $color }}
                        </option>
                    @endforeach
                </select>

                <select id="availabilityFilter">
                    <option value="">Any availability</option>
                    @foreach($gowns->pluck('status')->filter()->unique()->sort() as $status)
                        <option value="{{ $status }}" @selected(request('availability') === $status)>
                            {{ $status === 'under_maintenance' ? 'Repair' : ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- RESULT COUNT + CLEAR --}}
            @if($total > 0)
                <div class="sb-catalog-meta">
                    <span id="resultCount">Showing {{ $total }} of {{ $total }} gowns</span>
                    <button type="button" class="sb-catalog-clear" id="clearFilters" hidden>Clear filters</button>
                </div>
            @endif

            {{-- GOWN CARDS --}}
            <div class="sb-cards" id="gownCatalog">
                @forelse($gowns as $gown)
                    <article class="sb-product"
                        data-search="{{ strtolower($gown->name . ' ' . $gown->gown_code . ' ' . ($gown->category->name ?? '') . ' ' . ($gown->style ?? '') . ' ' . ($gown->size ?? '') . ' ' . ($gown->color ?? '')) }}"
                        data-category="{{ strtolower($gown->category->name ?? '') }}"
                        data-size="{{ strtolower($gown->size ?? '') }}" data-style="{{ strtolower($gown->style ?? '') }}"
                        data-color="{{ strtolower($gown->color ?? '') }}" data-status="{{ $gown->status }}">

                        <span class="sb-product-badge is-{{ $gown->status }}">
                            {{ $gown->status === 'under_maintenance' ? 'Repair' : ucfirst(str_replace('_', ' ', $gown->status)) }}
                        </span>

                        <a class="sb-product-image"
                            href="{{ route($showRoute, $gown) }}" aria-label="View {{ $gown->name }}">
                            <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}" loading="lazy"
                                decoding="async">
                        </a>

                        <div class="sb-product-info">
                            <h3>{{ $gown->name }}</h3>
                            <div>
                                <b>₱{{ number_format($gown->rental_price, 0) }}</b>

                                @if($gown->status === 'available' && $role === 'customer')
                                    <div class="sb-card-actions">
                                        <a class="sb-card-reserve-btn" href="{{ route('customer.reserve.gown', $gown) }}">
                                            Reserve Now
                                        </a>
                                        <form method="POST" action="{{ route('customer.cart.add', $gown) }}" class="sb-card-cart-form">
                                            @csrf
                                            <button type="submit" class="sb-card-cart-btn"
                                                title="Add to cart" aria-label="Add {{ $gown->name }} to cart">
                                                <svg><use href="#sb-i-cart" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($gown->status === 'available' && $role === 'employee')
                                    <div class="sb-card-actions">
                                        <a class="sb-card-reserve-btn" href="{{ route('employee.catalog.reserve', $gown) }}">
                                            Reserve Now
                                        </a>
                                        <form method="POST" action="{{ route('employee.cart.add', $gown) }}" class="sb-card-cart-form">
                                            @csrf
                                            <button type="submit" class="sb-card-cart-btn"
                                                title="Add to cart" aria-label="Add {{ $gown->name }} to cart">
                                                <svg><use href="#sb-i-cart" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($gown->status === 'available' && $role === 'owner')
                                    <div class="sb-card-actions">
                                        <a class="sb-card-reserve-btn" href="{{ route('owner.catalog.reserve', $gown) }}">
                                            Reserve Now
                                        </a>
                                        <form method="POST" action="{{ route('owner.cart.add', $gown) }}" class="sb-card-cart-form">
                                            @csrf
                                            <button type="submit" class="sb-card-cart-btn"
                                                title="Add to cart" aria-label="Add {{ $gown->name }} to cart">
                                                <svg><use href="#sb-i-cart" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="sb-catalog-empty">
                        <span class="sb-catalog-empty-icon">♡</span>
                        <h3>No gowns listed yet</h3>
                        @if($role === 'owner')
                            <p>Add your first gown and it will appear here for customers to browse.</p>
                            <a class="sb-btn" href="{{ route('owner.gowns.create') }}">Add your first gown</a>
                        @else
                            <p>The collection is being prepared. Please check back soon.</p>
                        @endif
                    </div>

                @endforelse

                {{-- shown by JS when filters match nothing --}}
                @if($total > 0)
                    <div class="sb-catalog-empty" id="noMatch" hidden>
                        <span class="sb-catalog-empty-icon">✧</span>
                        <h3>No gowns match your filters</h3>
                        <p>Try a different search, or clear the filters to see the full collection.</p>
                    </div>
                @endif
            </div>

            @if($total > 0)
                <nav class="sb-catalog-pagination" id="catalogPagination" aria-label="Gown catalog pagination">
                    <span id="catalogPageCount" aria-live="polite"></span>
                    <label for="catalogPageSize">Gowns per page</label>
                    <select id="catalogPageSize">
                        @foreach([12, 24, 48] as $size)
                            <option value="{{ $size }}">{{ $size }}</option>
                        @endforeach
                    </select>
                    <div class="sb-catalog-page-buttons" id="catalogPageButtons"></div>
                </nav>
            @endif
        </div>
    </div>

    <style>
        html body .sb-collection-page {
            background: #f8ecdf !important;
        }

        .sb-collection-page .sb-heading p {
            max-width: 720px;
        }

        .sb-collection-page .sb-catalog-tools input::placeholder {
            color: #8a7560;
        }

        .sb-collection-page .sb-catalog-tools .is-filtered {
            border-color: #6d1935;
            background: #fbeadd;
            color: #4e1126;
            font-weight: 600;
        }

        .sb-catalog-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 10px 2px 12px;
            font-size: 13px;
            color: #7a6642;
        }

        .sb-catalog-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
            padding: 12px;
            border: 1px solid #ecd9c8;
            border-radius: 14px;
            background: #fffdf8;
            color: #7a6642;
            font-size: 13px;
        }

        .sb-catalog-pagination > label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .sb-catalog-pagination select {
            width: auto;
            min-width: 72px;
            min-height: 36px;
            padding: 6px 28px 6px 10px;
            border: 1px solid #e3d6b8;
            border-radius: 8px;
            background: #fff;
            color: #3a2c1a;
        }

        .sb-catalog-page-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .sb-catalog-page-buttons button {
            min-width: 36px;
            min-height: 36px;
            padding: 0 10px;
            border: 1px solid #e6d9ca;
            border-radius: 8px;
            background: #fffdf9;
            color: #6b5551;
            cursor: pointer;
        }

        .sb-catalog-page-buttons button.is-active {
            border-color: #6d1935;
            background: #6d1935;
            color: #fff;
            font-weight: 700;
        }

        .sb-catalog-page-buttons button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        @media (max-width: 640px) {
            .sb-catalog-pagination {
                align-items: flex-start;
            }

            .sb-catalog-page-buttons {
                width: 100%;
            }
        }

        .sb-catalog-clear {
            border: 0;
            background: none;
            color: #6d1935;
            font-weight: 600;
            text-decoration: underline;
            cursor: pointer;
        }

        .sb-collection-page .sb-product {
            position: relative;
            border: 1px solid #ecd9c8;
            box-shadow: 0 6px 18px rgba(94, 15, 39, .08);
        }

        .sb-product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
            padding: 4px 10px;
            border-radius: 99px;
            background: rgba(255, 255, 255, .92);
            color: #8a6520;
            font-size: 11px;
            font-weight: 700;
        }

        .sb-product-badge.is-available {
            color: #3f7a4a;
        }

        .sb-product-badge.is-reserved {
            color: #a3323f;
        }

        .sb-catalog-empty {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 56px 24px;
            border: 1px solid #d9b8a0;
            border-radius: 16px;
            background: #fdf6ef;
            text-align: center;
        }

        .sb-catalog-empty[hidden] {
            display: none;
        }

        .sb-catalog-empty h3 {
            margin: 0;
            color: #4e1126;
            font: 700 17px 'DM Sans', sans-serif;
        }

        .sb-catalog-empty p {
            max-width: 360px;
            margin: 0;
            color: #7a6642;
            font-size: 13px;
        }

        .sb-catalog-empty-icon {
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #5e0f27;
            color: #fbe9d0;
            font-size: 20px;
        }
    </style>

    <script>
        (() => {
            const f = {
                search: document.querySelector('#gownSearch'),
                category: document.querySelector('#categoryFilter'),
                size: document.querySelector('#sizeFilter'),
                style: document.querySelector('#styleFilter'),
                color: document.querySelector('#colorFilter'),
                status: document.querySelector('#availabilityFilter'),
            };
            const cards = [...document.querySelectorAll('#gownCatalog .sb-product')];
            const count = document.querySelector('#resultCount');
            const pageCount = document.querySelector('#catalogPageCount');
            const pageSizeSelect = document.querySelector('#catalogPageSize');
            const pageButtons = document.querySelector('#catalogPageButtons');
            const clear = document.querySelector('#clearFilters');
            const noMatch = document.querySelector('#noMatch');
            let currentPage = 1;
            let matches = [];

            const renderPageButtons = pageTotal => {
                if (!pageButtons) return;

                pageButtons.replaceChildren();
                const addButton = (label, page, disabled = false, active = false) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = label;
                    button.disabled = disabled;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-label', label === 'Previous'
                        ? 'Previous page'
                        : label === 'Next' ? 'Next page' : `Page ${page}`);
                    if (active) button.setAttribute('aria-current', 'page');
                    if (!disabled && !active) {
                        button.addEventListener('click', () => {
                            currentPage = page;
                            renderPage();
                        });
                    }
                    pageButtons.append(button);
                };

                addButton('Previous', Math.max(1, currentPage - 1), currentPage === 1);
                const start = Math.max(1, Math.min(currentPage - 2, pageTotal - 4));
                const end = Math.min(pageTotal, start + 4);
                for (let page = start; page <= end; page++) {
                    addButton(String(page), page, false, page === currentPage);
                }
                addButton('Next', Math.min(pageTotal, currentPage + 1), currentPage === pageTotal);
            };

            const renderPage = () => {
                const pageSize = Number(pageSizeSelect?.value) || 12;
                const pageTotal = Math.max(1, Math.ceil(matches.length / pageSize));
                currentPage = Math.min(currentPage, pageTotal);
                const first = matches.length ? (currentPage - 1) * pageSize : 0;
                const visible = new Set(matches.slice(first, first + pageSize));

                cards.forEach(card => {
                    card.hidden = !visible.has(card);
                });

                if (pageCount) {
                    pageCount.textContent = matches.length
                        ? `Showing ${first + 1}–${Math.min(first + pageSize, matches.length)} of ${matches.length} gowns`
                        : 'No gowns match these filters';
                }
                renderPageButtons(pageTotal);
            };

            const apply = () => {
                const q = f.search.value.trim().toLowerCase();

                matches = cards.filter(card => {
                    const d = card.dataset;
                    return d.search.includes(q)
                        && (!f.category.value || d.category === f.category.value)
                        && (!f.size.value || d.size === f.size.value)
                        && (!f.style.value || d.style === f.style.value)
                        && (!f.color.value || d.color === f.color.value)
                        && (!f.status.value || d.status === f.status.value);
                });

                // highlight the filters that are in use
                Object.values(f).forEach(el => el.classList.toggle('is-filtered', !!el.value));

                const filtering = Object.values(f).some(el => !!el.value);
                if (count) count.textContent = `${matches.length} ${matches.length === 1 ? 'gown' : 'gowns'} match`;
                if (clear) clear.hidden = !filtering;
                if (noMatch) noMatch.hidden = matches.length !== 0;
                currentPage = 1;
                renderPage();
            };

            f.search.addEventListener('input', apply);
            Object.values(f)
                .filter(el => el !== f.search)
                .forEach(el => el.addEventListener('change', apply));

            if (pageSizeSelect) {
                pageSizeSelect.addEventListener('change', () => {
                    currentPage = 1;
                    renderPage();
                });
            }

            if (clear) {
                clear.addEventListener('click', () => {
                    Object.values(f).forEach(el => (el.value = ''));
                    apply();
                });
            }

            apply();
        })();
    </script>
</x-app-layout>
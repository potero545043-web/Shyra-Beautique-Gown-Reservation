<x-app-layout>
    <div class="sb-page sb-customer-dashboard">
        <div class="sb-wrap">
            @if(session('reservation_code'))
                <div class="sb-success">Reservation {{ session('reservation_code') }} received. It is waiting for staff
                    confirmation.</div>
            @endif
            <section class="sb-simple-overview-hero sb-customer-hero">
                <div class="sb-customer-hero-copy">
                    <span class="sb-kicker">ACCOUNT OVERVIEW</span>
                    <h1>Welcome, <em>{{ auth()->user()->name }}</em></h1>
                    <p>Find the gown for your next special occasion.</p>
                    <div class="sb-customer-hero-actions">
                        <a class="sb-btn" href="{{ route('customer.catalog') }}">Browse collection
                            <span>&rarr;</span></a>
                    </div>
                </div>
            </section>

            @php
                $alerts = $rentals->filter(fn($rental) => in_array($rental->status, ['awaiting_payment', 'ready_for_pickup', 'overdue'], true));
            @endphp
            @if($alerts->isNotEmpty())
                <section class="sb-customer-alerts" aria-label="Reservation updates">
                    @foreach($alerts as $alert)
                        @php
                            [$alertTitle, $alertMessage] = match ($alert->status) {
                                'awaiting_payment' => ['Payment required', 'Review this reservation to complete its payment.'],
                                'ready_for_pickup' => ['Gown ready for pickup', 'Review the pickup details for this reservation.'],
                                default => ['Return overdue', 'Please review the return details or contact the boutique.'],
                            };
                        @endphp
                        <a class="sb-customer-alert {{ $alert->status === 'overdue' ? 'is-urgent' : '' }}"
                            href="{{ route('customer.reservations.show', $alert) }}">
                            <span
                                class="sb-customer-alert-copy"><strong>{{ $alertTitle }}</strong><span>{{ $alertMessage }}</span></span>
                            <span class="sb-customer-alert-link">Review <span aria-hidden="true">&rarr;</span></span>
                        </a>
                    @endforeach
                </section>
            @endif

            <section class="sb-customer-find" aria-labelledby="find-gown-heading">
                <div>
                    <span class="sb-kicker">QUICK SEARCH</span>
                    <h2 id="find-gown-heading">Find a gown</h2>
                </div>
                <form action="{{ route('customer.catalog') }}" method="GET" class="sb-customer-find-form" data-live-filter>
                    <label class="sb-visually-hidden" for="customer-gown-search">Search by gown name, style, or
                        code</label>
                    <input id="customer-gown-search" type="search" name="search"
                        placeholder="Name, style, or gown code">
                    <label class="sb-visually-hidden" for="customer-gown-size">Size</label>
                    <select id="customer-gown-size" name="size">
                        <option value="">Any size</option>
                        @foreach($filterOptions['sizes'] as $size)
                        <option value="{{ strtolower($size) }}">{{ $size }}</option>@endforeach
                    </select>
                    <label class="sb-visually-hidden" for="customer-gown-style">Style</label>
                    <select id="customer-gown-style" name="style">
                        <option value="">Any style</option>
                        @foreach($filterOptions['styles'] as $style)
                        <option value="{{ strtolower($style) }}">{{ $style }}</option>@endforeach
                    </select>
                    <label class="sb-visually-hidden" for="customer-gown-color">Color</label>
                    <select id="customer-gown-color" name="color">
                        <option value="">Any color</option>
                        @foreach($filterOptions['colors'] as $color)
                        <option value="{{ strtolower($color) }}">{{ $color }}</option>@endforeach
                    </select>
                    <button class="sb-btn" type="submit">Search collection <span
                            aria-hidden="true">&rarr;</span></button>
                </form>
            </section>

            <div class="sb-section-head">
                <div><span class="sb-kicker">THE COLLECTION</span>
                    <h2>Featured gowns</h2>
                </div><a class="sb-text-link" href="{{ route('customer.catalog') }}">View all gowns &rarr;</a>
            </div>
            <div class="sb-cards">
                @forelse($featured as $gown)
                    <article class="sb-product">
                        <a class="sb-product-image"
                            href="{{ route('customer.gowns.show', $gown) }}" aria-label="View {{ $gown->name }}">
                            <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}" loading="lazy"
                                decoding="async">
                            <span
                                class="sb-available {{ $gown->status === 'available' ? '' : 'is-unavailable' }}">{{ ucfirst(str_replace('_', ' ', $gown->status)) }}</span>
                        </a>
                        <div class="sb-product-info"><small>{{ $gown->category->name ?? 'GOWN' }}</small>
                            <h3>{{ $gown->name }}</h3>
                            <div>
                                <b>&#8369;{{ number_format($gown->rental_price, 0) }}</b><span>/ rental</span>
                                <div class="sb-card-actions">
                                    <a class="sb-card-reserve-btn" href="{{ route('customer.reserve.gown', $gown) }}">Reserve Now</a>
                                    <form method="POST" action="{{ route('customer.cart.add', $gown) }}" class="sb-card-cart-form">
                                        @csrf
                                        <button type="submit" class="sb-card-cart-btn" title="Add to cart"
                                            aria-label="Add {{ $gown->name }} to cart">
                                            <svg><use href="#sb-i-cart" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="sb-panel sb-empty">
                        No gowns are available in the catalog yet. Please contact the boutique or ask an administrator
                        to restore the gown inventory.
                    </div>
                @endforelse
            </div>

            <section class="sb-panel sb-customer-rentals">
                <div class="sb-customer-rentals-head">
                    <div>
                        <span class="sb-kicker">YOUR RENTALS</span>
                        <h2>Upcoming rentals &amp; returns</h2>
                        <p>Keep track of gown pickup and return dates.</p>
                    </div>
                    <a class="sb-customer-reservations-button" href="{{ route('customer.reservations') }}">View all reservations <span
                            aria-hidden="true">&rarr;</span></a>
                </div>

                @forelse($rentals as $rental)
                    @php
                        $isOverdue = $rental->status === 'overdue'
                            || ($rental->status === 'released' && $rental->return_date?->isBefore(today()));
                        $rentalGown = $rental->items->first(fn($item) => $item->gown)?->gown;
                    @endphp
                    <article class="sb-customer-rental-row">
                        <div class="sb-customer-rental-photo">
                            @if($rentalGown)
                                <img src="{{ $rentalGown->image_url }}" alt="{{ $rentalGown->name }}" loading="lazy">
                            @else
                                <span aria-hidden="true">✿</span>
                            @endif
                        </div>
                        <div class="sb-customer-rental-name">
                            <small class="sb-customer-rental-eyebrow">
                                {{ $rentalGown?->category?->name ?? 'Your rental' }}
                                @if($rental->items->count() > 1) · {{ $rental->items->count() }} gowns @endif
                            </small>
                            <strong>{{ $rental->items->map(fn($item) => $item->gown?->name)->filter()->join(', ') ?: 'Gown rental' }}</strong>
                            <small>{{ $rental->reservation_code }}</small>
                        </div>
                        <div class="sb-customer-rental-date">
                            <small>PICKUP</small>
                            <b>{{ $rental->pickup_date?->format('M d, Y') }}</b>
                        </div>
                        <div class="sb-customer-rental-date {{ $isOverdue ? 'is-overdue' : '' }}">
                            <small>{{ $isOverdue ? 'RETURN OVERDUE' : 'RETURN DUE' }}</small>
                            <b>{{ $rental->return_date?->format('M d, Y') }}</b>
                        </div>
                        <span class="sb-status {{ $isOverdue ? 'is-overdue' : '' }}">
                            {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                        </span>
                        <a class="sb-customer-rental-action" href="{{ route('customer.reservations.show', $rental) }}">View
                            reservation <span aria-hidden="true">&rarr;</span></a>
                    </article>
                @empty
                    <div class="sb-customer-rentals-empty">
                        <span class="sb-customer-rentals-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3a1.5 1.5 0 0 0-1.5 1.5c0 .7.4 1.3 1 1.6L9 8h6l-2.5-1.9c.6-.3 1-.9 1-1.6A1.5 1.5 0 0 0 12 3Z" />
                                <path d="M9 8 4.6 18.2a1 1 0 0 0 .9 1.4h12.9a1 1 0 0 0 .9-1.4L15 8" />
                            </svg>
                        </span>
                        <div class="sb-customer-rentals-empty-copy">
                            <strong>You don't have any upcoming rentals yet.</strong>
                            <p>Explore the collection and find a gown for your next special occasion.</p>
                        </div>
                        <a class="sb-text-link" href="{{ route('customer.catalog') }}">Browse collection <span
                                aria-hidden="true">&rarr;</span></a>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>

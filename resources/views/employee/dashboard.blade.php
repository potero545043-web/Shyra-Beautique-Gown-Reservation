<x-app-layout>
    <div class="sb-page sb-emp">
        <div class="sb-wrap">

            {{-- HEADING --}}
            <div class="sb-simple-overview-hero sb-customer-hero">
                <div class="sb-customer-hero-copy">
                    <span class="sb-kicker">EMPLOYEE OVERVIEW</span>
                    <h1>Welcome back, <em>{{ explode(' ', auth()->user()->name)[0] }}.</em></h1>
                    <p>Reservations, rentals, payments, and returns at a glance.</p>
                    <div class="sb-customer-hero-actions">
                        <a class="sb-btn" href="{{ route('employee.catalog') }}">Browse collection
                            <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </div>
            </div>

            {{-- 4 CARDS, one row --}}
            <div class="sb-stats">
                <div class="sb-stat">
                    <span>Reservations</span>
                    <b>{{ $reservationCount }}</b>
                    <small>This year</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-calendar" />
                        </svg></i>
                </div>

                <div class="sb-stat">
                    <span>Active rentals</span>
                    <b>{{ $rentalCount }}</b>
                    <small>Released or overdue</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-dress" />
                        </svg></i>
                </div>

                <div class="sb-stat">
                    <span>Available gowns</span>
                    <b>{{ $availableCount }}<em class="sb-of"> / {{ $gownCount }}</em></b>
                    <small>Available / total gowns</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-tag" />
                        </svg></i>
                </div>

                <div class="sb-stat sb-stat-gold">
                    <span>Verified payments</span>
                    <b>₱{{ number_format($paymentTotal, 0) }}</b>
                    <small>Verified in {{ $yearLabel }}</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-card" />
                        </svg></i>
                </div>
            </div>

            <div class="sb-columns">

                {{-- RESERVATIONS TABLE --}}
                <section class="sb-panel">
                    <div class="sb-panel-head">
                        <div>
                            <h2>Reservation activity</h2>
                            <p>Recent bookings to take care of</p>
                        </div>
                        <a class="sb-pill" href="{{ route('employee.reservations') }}">View all</a>
                    </div>

                    <div class="sb-table-wrap">
                        <table class="sb-table">
                            <thead>
                                <tr>
                                    <th>BOOKING</th>
                                    <th>CUSTOMER</th>
                                    <th>DATES</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reservations as $booking)
                                    <tr class="sb-clickable-row"
                                        data-href="{{ route('employee.reservations.show', $booking) }}"
                                        tabindex="0" role="link"
                                        aria-label="View reservation {{ $booking->reservation_code }}">
                                        <td>
                                            <strong>{{ $booking->reservation_code }}</strong>
                                            <small class="sb-cell-sub">
                                                {{ $booking->items->first()?->gown?->name ?? 'Gown reservation' }}
                                            </small>
                                        </td>
                                        <td>{{ $booking->customer->full_name ?? 'Customer' }}</td>
                                        <td>
                                            {{ $booking->pickup_date?->format('M d') }} –
                                            {{ $booking->return_date?->format('M d') }}
                                        </td>
                                        <td>
                                            <span class="sb-status sb-status-{{ $booking->status }}">
                                                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="sb-empty">No reservations yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- UPCOMING RETURNS --}}
                <section class="sb-panel sb-sidepanel">
                    <div class="sb-panel-head">
                        <div>
                            <h2>Upcoming returns</h2>
                            <p>Scheduled returns</p>
                        </div>
                        <a class="sb-pill" href="{{ route('employee.rentals') }}">View all</a>
                    </div>

                    @forelse($returns as $booking)
                        <div class="sb-return sb-clickable-return">
                            <span class="sb-date-chip">
                                {{ $booking->return_date?->format('d') }}
                                <small>{{ $booking->return_date?->format('M') }}</small>
                            </span>
                            <a class="sb-return-booking-link"
                                href="{{ route('employee.reservations.show', $booking) }}"
                                aria-label="View reservation {{ $booking->reservation_code }}">
                                <b>{{ $booking->customer->full_name ?? 'Customer' }}</b>
                                <small>Reservation {{ $booking->reservation_code }}</small>
                            </a>
                            <a class="sb-small-btn sb-dashboard-record-return"
                                href="{{ route('employee.rentals') }}#rental-row-{{ $booking->id }}">
                                Record Return
                            </a>
                        </div>
                    @empty
                        <div class="sb-empty">No returns coming up.</div>
                    @endforelse

                    <div class="sb-tip">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#sb-i-spark" />
                            </svg></span>
                        <div>
                            <b>Today’s reminder</b>
                            <p>Check the reservation details before each pickup.</p>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

    {{-- Gold / cream restyle, scoped to this page (.sb-emp) so other pages are untouched --}}
    <style>
        .sb-page.sb-emp {
            --e-cream: #f8f0d6;
            --e-card: #fbf5e3;
            --e-maroon: #6b0020;
            --e-maroon-dark: #4f0018;
            --e-gold: #a67c2e;
            --e-line: #d9c089;
            --e-muted: #7a6a60;
            background: var(--e-cream) !important;
        }

        .sb-page.sb-emp::before,
        .sb-page.sb-emp::after {
            display: none !important;
        }

        body:has(.sb-emp) .sb-main-column,
        body:has(.sb-emp) .sb-main-content {
            background: var(--e-cream) !important;
        }

        /* numbers: real digits, not old-style "o" */
        .sb-emp .sb-stat b,
        .sb-emp .sb-date-chip {
            font-family: 'DM Sans', sans-serif !important;
            font-variant-numeric: lining-nums tabular-nums;
            font-feature-settings: 'lnum' 1;
        }

        /* heading, two-tone like the hero */
        .sb-emp .sb-heading h1 {
            color: var(--e-maroon);
        }

        .sb-emp .sb-heading h1 em {
            color: var(--e-gold);
            font-weight: 500;
        }

        .sb-emp .sb-kicker {
            color: var(--e-gold);
        }

        .sb-emp .sb-heading p {
            color: var(--e-muted);
        }

        /* pills */
        .sb-emp .sb-btn {
            background: var(--e-maroon) !important;
            border-radius: 999px !important;
            box-shadow: 0 8px 20px rgba(107, 0, 32, .18) !important;
        }

        .sb-emp .sb-btn:hover {
            background: var(--e-maroon-dark) !important;
        }

        .sb-emp .sb-pill {
            background: transparent !important;
            color: var(--e-maroon) !important;
            border: 1px solid var(--e-gold) !important;
            border-radius: 999px !important;
        }

        .sb-emp .sb-pill:hover {
            background: rgba(166, 124, 46, .1) !important;
        }

        /* 4 cards in one row */
        .sb-emp .sb-stats {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 16px !important;
        }

        .sb-emp .sb-stat {
            position: relative;
            display: grid !important;
            grid-template-columns: 36px minmax(0, 1fr) auto !important;
            grid-template-rows: auto auto;
            align-items: center;
            column-gap: 12px;
            background: var(--e-card) !important;
            border: 1px solid var(--e-line) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 26px rgba(107, 0, 32, .08) !important;
            color: var(--e-maroon);
        }

        .sb-emp .sb-stat::before {
            content: "";
            position: absolute;
            inset: 20px auto 20px 0;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background: var(--e-gold);
        }

        .sb-emp .sb-stat:nth-child(2)::before { background: #438b59; }
        .sb-emp .sb-stat:nth-child(3)::before { background: #c9821a; }
        .sb-emp .sb-stat:nth-child(4)::before { background: #d9b878; }

        .sb-emp .sb-stat span {
            grid-column: 2;
            grid-row: 1;
            color: var(--e-muted) !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            letter-spacing: .08em !important;
            text-transform: uppercase;
        }

        .sb-emp .sb-stat b {
            grid-column: 3;
            grid-row: 1 / span 2;
            justify-self: end;
            text-align: right;
            color: var(--e-maroon) !important;
            font-weight: 600 !important;
            font-size: 27px !important;
        }

        .sb-emp .sb-stat b .sb-of {
            font-style: normal;
            font-size: .55em;
            font-weight: 500;
            color: var(--e-muted);
        }

        .sb-emp .sb-stat small {
            grid-column: 2;
            grid-row: 2;
            color: var(--e-muted) !important;
            display: block;
        }

        .sb-emp .sb-stat i {
            position: static;
            grid-column: 1;
            grid-row: 1 / span 2;
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(166, 124, 46, .14) !important;
            color: var(--e-gold) !important;
            font-style: normal;
        }

        .sb-emp .sb-stat i svg,
        .sb-emp .sb-tip span svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* the one highlighted card */
        .sb-emp .sb-stat.sb-stat-gold {
            background: var(--e-maroon) !important;
            border-color: var(--e-maroon) !important;
        }

        .sb-emp .sb-stat.sb-stat-gold span,
        .sb-emp .sb-stat.sb-stat-gold small {
            color: rgba(255, 255, 255, .8) !important;
        }

        .sb-emp .sb-stat.sb-stat-gold b {
            color: #f0d79a !important;
        }

        .sb-emp .sb-stat.sb-stat-gold i {
            background: rgba(255, 255, 255, .14) !important;
            color: #f0d79a !important;
        }

        /* panels */
        .sb-emp .sb-panel {
            background: var(--e-card) !important;
            border: 1px solid var(--e-line) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 26px rgba(107, 0, 32, .06) !important;
        }

        .sb-emp .sb-panel-head h2 {
            color: var(--e-maroon) !important;
        }

        .sb-emp .sb-panel-head p {
            color: var(--e-muted) !important;
        }

        /* table: cream header with a gold line */
        .sb-emp .sb-table-wrap {
            background: transparent !important;
            border-radius: 14px;
        }

        .sb-emp .sb-table thead th,
        .sb-emp .sb-table thead tr {
            background: transparent !important;
            color: var(--e-gold) !important;
            border-bottom: 1px solid var(--e-line) !important;
        }

        .sb-emp .sb-table td {
            color: var(--e-text, #4a3b35);
            border-bottom: 1px solid rgba(217, 192, 137, .45);
        }

        .sb-emp .sb-table strong {
            color: var(--e-maroon);
        }

        .sb-emp .sb-cell-sub {
            color: var(--e-muted);
        }

        .sb-emp .sb-status {
            background: rgba(166, 124, 46, .14) !important;
            color: #7a5a1c !important;
            border-radius: 999px;
        }

        .sb-emp .sb-empty {
            color: var(--e-muted) !important;
            background: transparent !important;
        }

        /* returns */
        .sb-emp .sb-date-chip {
            background: var(--e-maroon) !important;
            color: #fff !important;
            border-radius: 12px !important;
        }

        .sb-emp .sb-return b {
            color: var(--e-maroon);
        }

        .sb-emp .sb-return small {
            color: var(--e-muted);
        }

        .sb-emp .sb-tip {
            background: #fffbf0 !important;
            border: 1px dashed var(--e-line) !important;
            border-radius: 14px !important;
        }

        .sb-emp .sb-tip span {
            color: var(--e-gold) !important;
        }

        .sb-emp .sb-tip b {
            color: var(--e-maroon) !important;
        }

        .sb-emp .sb-tip p {
            color: var(--e-muted) !important;
        }

        @media (max-width: 1100px) {
            .sb-emp .sb-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 560px) {
            .sb-emp .sb-stats {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</x-app-layout>

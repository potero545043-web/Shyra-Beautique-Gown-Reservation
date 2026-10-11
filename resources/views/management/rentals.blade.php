<x-app-layout>
    <div class="sb-page sb-rentals-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">SHYRA BEAUTIQUE · HANDOFFS</span>
                    <h1>Handoffs & <em>returns.</em></h1>
                    <p>Track gown pickups, returns, inspections, and cleaning.</p>
                </div>
                <a class="sb-btn" href="{{ route($base . '.reservations') }}">Reservation desk <span
                        aria-hidden="true">&rarr;</span></a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            {{-- Validation errors were silently dropped before, so a failed pickup looked like "nothing happened". --}}
            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            @if($activeRentals === 0)
                <div class="sb-return-queue-notice" role="status">
                    <strong>No rentals are currently awaiting return.</strong>
                    <span>“Record Return” appears for active rentals after staff record the gown pickup. Returned reservations show a link to their inspection record instead.</span>
                </div>
            @endif

            <div class="sb-stats sb-ops-summary">
                <article class="sb-stat"><span>Pickups today</span><b>{{ $pickupsToday }}</b><small>Confirmed
                        bookings</small><i><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#sb-i-calendar" /></svg></i></article>
                <article class="sb-stat"><span>Active rentals</span><b>{{ $activeRentals }}</b><small>Currently with
                        customers</small><i><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#sb-i-dress" /></svg></i></article>
                <article class="sb-stat"><span>Returns due</span><b>{{ $returnsDue }}</b><small>Due today or
                        overdue</small><i><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#sb-i-users" /></svg></i></article>
                <article class="sb-stat"><span>To clean</span><b>{{ $cleaningCount }}</b><small>Waiting for
                        inspection</small><i><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#sb-i-spark" /></svg></i></article>
            </div>

            {{-- PICKUP & RETURN QUEUE --}}
            <section class="sb-panel sb-table-section">
                <div class="sb-panel-head">
                    <div>
                        <h2>Pickup &amp; return queue</h2>
                        <p>Confirmed reservations, active rentals, and items due back.</p>
                    </div>
                    <span class="sb-pill">{{ $rentals->total() }} {{ $rentals->total() === 1 ? 'record' : 'records' }}</span>
                </div>
                <div class="sb-table-wrap sb-ops-table-wrap">
                    <table class="sb-table sb-ops-table sb-ops-table--rentals">
                        <thead>
                            <tr>
                                <th>BOOKING / CUSTOMER</th>
                                <th>GOWN</th>
                                <th>STATUS</th>
                                <th>RENTAL DATES</th>
                                <th>HANDOFF</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentals as $rental)
                                <tr class="sb-clickable-row"
                                    id="rental-row-{{ $rental->id }}"
                                    data-href="{{ route($base . '.reservations.show', $rental) }}"
                                    tabindex="0" role="link"
                                    aria-label="View reservation {{ $rental->reservation_code }}">
                                    <td data-label="Booking / customer">
                                        <strong>{{ $rental->reservation_code }}</strong>
                                        <small class="sb-cell-sub">{{ $rental->customer->full_name ?? 'Guest' }}</small>
                                        <small
                                            class="sb-cell-sub">{{ $rental->customer->contact_number ?? 'No contact' }}</small>
                                    </td>
                                    <td data-label="Gown">
                                        {{ $rental->items->map(fn($item) => $item->gown?->name)->filter()->join(', ') ?: 'Gown' }}
                                    </td>
                                    <td data-label="Status"><span
                                            class="sb-status sb-status-{{ $rental->status }}">{{ ucfirst(str_replace('_', ' ', $rental->status)) }}</span>
                                    </td>
                                    <td data-label="Rental dates">
                                        <strong>{{ $rental->pickup_date?->format('M d, Y') }}</strong>
                                        <small class="sb-cell-sub">Due {{ $rental->return_date?->format('M d, Y') }}</small>
                                        @if($rental->gownReturn)
                                            <small class="sb-cell-sub">Returned {{ $rental->gownReturn->actual_return_date?->format('M d, Y') ?? 'date unavailable' }}</small>
                                        @endif
                                    </td>
                                    <td data-label="Handoff">
                                        {{ $rental->gownRelease?->release_date?->format('M d, Y') ?? 'Not released' }}</td>
                                    <td data-label="Action">
                                        @if(in_array($rental->status, ['confirmed', 'ready_for_pickup'], true))
                                            <details class="sb-row-actions">
                                                <summary>Record pickup</summary>
                                                <form method="POST" action="{{ route($base . '.rentals.release', $rental) }}"
                                                    enctype="multipart/form-data" class="sb-row-action-form">
                                                    <button type="button" class="sb-row-actions-close"
                                                        aria-label="Close pickup form">×</button>
                                                    @csrf
                                                    <p class="sb-cell-sub">
                                                        Only submit after the customer physically takes the gown. Scheduled pickup is {{ $rental->pickup_date?->format('M d, Y') ?? 'not set' }}; record the actual handoff date today.
                                                    </p>
                                                    <label>Condition at handoff
                                                        <select name="condition_before" required>
                                                            <option value="excellent">Excellent</option>
                                                            <option value="good" selected>Good</option>
                                                            <option value="fair">Fair</option>
                                                            <option value="damaged">Damaged</option>
                                                        </select>
                                                    </label>
                                                    <label>Secure safe slot
                                                        <input name="id_safe_slot" value="{{ old('id_safe_slot', $rental->id_safe_slot) }}"
                                                            placeholder="Example: Safe A · Slot 04" required>
                                                    </label>
                                                    @unless($rental->physical_id_photo_path)
                                                        <label>Photo of physical ID
                                                            <input type="file" name="physical_id_photo"
                                                                accept="image/jpeg,image/png,image/webp" capture="environment" required>
                                                            <small class="sb-field-hint">JPG, PNG or WEBP, up to 4 MB.</small>
                                                        </label>
                                                    @endunless
                                                    <label>Handoff note<input name="notes" placeholder="Optional note"></label>
                                                    <button class="sb-small-btn">Save pickup</button>
                                                </form>
                                            </details>
                                        @elseif(in_array($rental->status, ['released', 'overdue'], true))
                                            @include('management.partials.return-form', [
                                                'reservation' => $rental,
                                                'base' => $base,
                                            ])
                                        @elseif(in_array($rental->status, ['returned', 'completed'], true))
                                            <div class="sb-rental-complete-actions">
                                                @if((float) $rental->balance <= 0 && $rental->collateral_status !== 'held')
                                                    <a class="sb-small-btn sb-return-details-link"
                                                        href="{{ route($base . '.reservations.show', $rental) }}#return-details">
                                                        View Return Details
                                                    </a>
                                                @endif
                                                @if((float) $rental->balance > 0)
                                                    <a class="sb-small-btn sb-payment-action-link"
                                                        href="{{ route($base . '.payments', ['reservation_id' => $rental->id]) }}">
                                                        Record Payment
                                                    </a>
                                                @endif
                                                @if($rental->collateral_status === 'held')
                                                    @if((float) $rental->balance > 0)
                                                        <div class="sb-id-release-blocked" data-row-nav-ignore>
                                                            <button class="sb-small-btn" type="button" disabled>Release ID</button>
                                                            <small>Clear ₱{{ number_format($rental->balance, 2) }} before returning the held ID.</small>
                                                        </div>
                                                    @else
                                                        <form method="POST" action="{{ route($base . '.rentals.release-id', $rental) }}"
                                                            data-confirm-title="Release" data-confirm-accent="ID?" data-confirm-message="Give the original ID back to the customer?" data-confirm-yes="Yes, release ID" data-confirm-no="Not yet">
                                                            @csrf
                                                            <button class="sb-small-btn">Release ID</button>
                                                        </form>
                                                    @endif
                                                @elseif($rental->collateral_status === 'released')
                                                    <span class="sb-id-release-status">ID already released</span>
                                                @else
                                                    <span class="sb-id-release-status">No ID held for this rental</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="sb-cell-sub">No action</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="sb-empty sb-empty-state">
                                        <div class="sb-res-empty">
                                            <span class="sb-res-empty-icon">◷</span>
                                            <strong>No active handoffs</strong>
                                            <small>Confirmed pickups and active rentals will appear here.</small>
                                            <a class="sb-btn" href="{{ route($base . '.reservations') }}">View
                                                reservations</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('components.table-pagination', [
                    'paginator' => $rentals,
                    'parameter' => 'rentals_per_page',
                    'pageParameter' => 'rentals_page',
                    'itemLabel' => 'records',
                ])
            </section>

            {{-- GOWNS TO CLEAN --}}
            <section class="sb-panel sb-cleaning-panel">
                <div class="sb-panel-head">
                    <div>
                        <h2>Gowns to clean</h2>
                        <p>Date checks include a two-day cleaning buffer after return. Complete the cleaning check before making a gown available again.</p>
                    </div>
                    <span class="sb-pill">{{ $cleanings->total() }} waiting</span>
                </div>

                <div class="sb-table-wrap sb-ops-table-wrap">
                    <table class="sb-table sb-ops-table sb-ops-table--cleaning">
                        <thead>
                            <tr>
                                <th>GOWN</th>
                                <th>CLEANING TYPE</th>
                                <th>DATE</th>
                                <th>NOTES</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cleanings as $cleaning)
                                <tr>
                                    <td data-label="Gown"><strong>{{ $cleaning->gown->name ?? 'Gown' }}</strong></td>
                                    <td data-label="Cleaning type">{{ $cleaning->cleaning_type }}</td>
                                    <td data-label="Date">{{ $cleaning->cleaning_date?->format('M d, Y') ?? '—' }}</td>
                                    <td data-label="Notes">{{ $cleaning->notes ?: '—' }}</td>
                                    <td data-label="Action">
                                        <form method="POST" action="{{ route($base . '.cleaning.complete', $cleaning) }}">
                                            @csrf
                                            <button class="sb-small-btn sb-action-success">Mark clean &amp; available</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="sb-empty sb-empty-state">
                                        <div class="sb-res-empty">
                                            <span class="sb-res-empty-icon">✓</span>
                                            <strong>Nothing to clean</strong>
                                            <small>Returned gowns needing inspection or cleaning will appear here.</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('components.table-pagination', [
                    'paginator' => $cleanings,
                    'parameter' => 'cleanings_per_page',
                    'pageParameter' => 'cleanings_page',
                    'itemLabel' => 'cleaning records',
                ])
            </section>

        </div>
    </div>

    <style>
        .sb-heading p { max-width: 720px; }

        /* stat cards: cream palette, gold icons */
        html body .sb-ops-summary .sb-stat { position: relative; }
        html body .sb-ops-summary .sb-stat span { color: #7a6a60; font-weight: 600; }
        html body .sb-ops-summary .sb-stat b { color: #6b0020; }
        html body .sb-ops-summary .sb-stat small { color: #7a6a60; }
        html body .sb-ops-summary .sb-stat i svg {
            width: 20px; height: 20px;
            fill: none; stroke: currentColor; stroke-width: 1.8;
            stroke-linecap: round; stroke-linejoin: round;
        }

        /* first/last column line up with the panel header padding */
        html body .sb-ops-table th:first-child,
        html body .sb-ops-table td:first-child { padding-left: 24px; }
        html body .sb-ops-table th:last-child,
        html body .sb-ops-table td:last-child { padding-right: 24px; }

        /* empty state: cream, not peach */
        html body .sb-ops-table td.sb-empty-state { background: transparent; }

        .sb-res-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 26px 12px;
        }
        .sb-res-empty-icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #6b0020;
            color: #f0d79a;
            font-size: 18px;
        }
        .sb-res-empty strong { color: #6b0020; font: 700 17px 'DM Sans', sans-serif; }
        .sb-res-empty small { max-width: 360px; color: #7a6a60; font-size: 13px; }
        .sb-res-empty .sb-btn { margin-top: 6px; }
    </style>
</x-app-layout>
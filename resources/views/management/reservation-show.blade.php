<x-app-layout>
    <div class="sb-page sb-reservation-details-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">RESERVATION {{ $reservation->reservation_code }}</span>
                    <h1>Reservation <em>details.</em></h1>
                    <p>Review the booking, customer, gowns, and payment history.</p>
                </div>
                <a class="sb-outline-btn sb-back-btn" href="{{ route($base . '.reservations') }}">Back to reservations</a>
            </div>

            <section class="sb-panel sb-reservation-details">
                <div class="sb-reservation-details-head">
                    <div>
                        <span class="sb-kicker">BOOKING STATUS</span>
                        <h2>{{ $reservation->reservation_code }}</h2>
                    </div>
                    <span class="sb-status sb-status-{{ $reservation->status }}">
                        {{ ucfirst(str_replace('_', ' ', $reservation->status)) }}
                    </span>
                </div>

                <div class="sb-reservation-details-grid sb-reservation-customer-grid">
                    <div><small>CUSTOMER</small><b>{{ $reservation->customer->full_name ?? 'Guest' }}</b></div>
                    <div><small>CONTACT</small><b>{{ $reservation->customer->contact_number ?? 'No contact' }}</b></div>
                    <div><small>EMAIL</small><b>{{ $reservation->customer->email ?? 'Not provided' }}</b></div>
                    <div><small>PICKUP DATE</small><b>{{ $reservation->pickup_date?->toFormattedDateString() ?? '—' }}</b></div>
                    <div><small>RETURN DATE</small><b>{{ $reservation->return_date?->toFormattedDateString() ?? '—' }}</b></div>
                    <div><small>BOOKED ON</small><b>{{ $reservation->created_at?->toFormattedDateString() ?? '—' }}</b></div>
                </div>

                @if($reservation->gownReturn)
                    <section id="return-details" class="sb-return-recorded-panel" role="status">
                        <h3>Return details</h3>
                        <div><span>Actual return date</span><b>{{ $reservation->gownReturn->actual_return_date?->format('M d, Y') ?? '—' }}</b></div>
                        <div><span>Condition</span><b>{{ ucfirst(str_replace('_', ' ', $reservation->gownReturn->condition_after)) }}</b></div>
                        @if($reservation->gownReturn->notes)
                            <div><span>Inspection notes</span><b>{{ $reservation->gownReturn->notes }}</b></div>
                        @endif
                    </section>
                @elseif(in_array($reservation->status, ['returned', 'completed'], true))
                    <section id="return-details" class="sb-return-recorded-panel sb-return-record-missing" role="alert">
                        <h3>Return inspection record is missing</h3>
                        <p>This reservation is marked returned, but no inspection details were saved. Contact an administrator to review the rental record.</p>
                    </section>
                @endif

                <h3 class="sb-section-title sb-reservation-section-title">Reserved gowns</h3>
                <div class="sb-table-wrap sb-reservation-gowns-wrap">
                    <table class="sb-table sb-reservation-gowns-table">
                        <thead>
                            <tr>
                                <th>PHOTO</th>
                                <th>GOWN</th>
                                <th>CATEGORY</th>
                                <th>SIZE</th>
                                <th>RENTAL PRICE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservation->items as $item)
                                <tr>
                                    <td data-label="Photo">
                                        <div class="sb-reservation-gown-photo">
                                            @if($item->gown)
                                                <img src="{{ $item->gown->image_url }}"
                                                    alt="{{ $item->gown->name }}" loading="lazy">
                                            @else
                                                <span aria-hidden="true">Gown unavailable</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td data-label="Gown">
                                        <strong>{{ $item->gown?->name ?? 'Gown no longer available' }}</strong>
                                        <small>{{ $item->gown?->gown_code ?? '—' }}</small>
                                    </td>
                                    <td>{{ $item->gown?->category?->name ?? '—' }}</td>
                                    <td>{{ $item->gown?->size ?: '—' }}</td>
                                    <td>₱{{ number_format((float) ($item->rental_price ?? $item->gown?->rental_price ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No gown items are attached to this reservation.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h3 class="sb-section-title sb-reservation-section-title">Payment summary</h3>
                <div class="sb-reservation-details-grid sb-reservation-payment-grid">
                    <div><small>RENTAL TOTAL</small><b>₱{{ number_format((float) $reservation->rental_total, 2) }}</b></div>
                    <div><small>GRAND TOTAL</small><b>₱{{ number_format((float) $reservation->grand_total, 2) }}</b></div>
                    <div><small>PAID</small><b>₱{{ number_format((float) $reservation->amount_paid, 2) }}</b></div>
                    <div><small>BALANCE</small><b>₱{{ number_format((float) $reservation->balance, 2) }}</b></div>
                    <div><small>AGREEMENT</small><b>{{ $reservation->agreement_accepted_at ? 'Accepted' : 'Pending' }}</b></div>
                    <div><small>ID COLLATERAL</small><b>{{ ucfirst(str_replace('_', ' ', $reservation->collateral_status ?? 'not received')) }}</b></div>
                </div>

                @if($reservation->customer_notes)
                    <h3 class="sb-section-title">Customer notes</h3>
                    <p>{{ $reservation->customer_notes }}</p>
                @endif

                @if($reservation->admin_notes)
                    <h3 class="sb-section-title">Staff notes</h3>
                    <p>{{ $reservation->admin_notes }}</p>
                @endif

                @if(in_array($reservation->status, ['released', 'overdue'], true))
                    @include('management.partials.return-form', [
                        'reservation' => $reservation,
                        'base' => $base,
                        'expanded' => true,
                    ])
                @elseif($reservation->status === 'returned')
                    <section class="sb-return-recorded-panel sb-return-action-complete" role="status">
                        <h3>Return already recorded</h3>
                        <p>This rental has been inspected and marked returned. No further return action is needed.</p>
                    </section>
                @endif

                @if(in_array($reservation->status, ['returned', 'completed'], true))
                    <section class="sb-id-release-panel" aria-labelledby="id-release-title">
                        <div>
                            <h3 id="id-release-title">Return the customer's ID</h3>
                            <p>Release the original ID after the gown is returned and the account balance is cleared.</p>
                        </div>
                        @if($reservation->collateral_status === 'held' && (float) $reservation->balance <= 0)
                            <form method="POST" action="{{ route($base . '.rentals.release-id', $reservation) }}"
                                data-confirm-title="Release" data-confirm-accent="ID?"
                                data-confirm-message="Give the original ID back to the customer?"
                                data-confirm-yes="Yes, release ID" data-confirm-no="Not yet">
                                @csrf
                                <button class="sb-small-btn sb-id-release-button" type="submit">Release ID to customer</button>
                            </form>
                        @elseif($reservation->collateral_status === 'held')
                            <p class="sb-id-release-guidance" role="status">
                                ID is still held. Clear ₱{{ number_format((float) $reservation->balance, 2) }} before releasing it.
                            </p>
                        @elseif($reservation->collateral_status === 'released')
                            <p class="sb-id-release-guidance" role="status">The customer's ID has already been released.</p>
                        @else
                            <p class="sb-id-release-guidance" role="status">No customer ID is being held for this rental.</p>
                        @endif
                    </section>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

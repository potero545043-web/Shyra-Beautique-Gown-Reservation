<x-app-layout>
    <div class="sb-page sb-customer-account-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div><span class="sb-kicker">CUSTOMER ACCOUNT · {{ $customer->customer_code }}</span>
                    <h1>{{ $customer->full_name }}<em>.</em></h1>
                    <p>{{ $customer->contact_number ?: 'No phone on file' }} ·
                        {{ $customer->email ?: 'No email on file' }}</p>
                </div>
                <a class="sb-outline-btn sb-back-btn" href="{{ route($base . '.customers') }}">Back to customers</a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            <section class="sb-panel sb-customer-detail-summary">
                <div class="sb-panel-head">
                    <div>
                        <h2>Account summary</h2>
                        <p>Reservation and payment overview</p>
                    </div><span class="sb-status sb-status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span>
                </div>
                <div class="sb-booking-info">
                    <div><small>RESERVATIONS</small><b>{{ $reservationCount }}</b></div>
                    <div><small>VERIFIED
                            PAID</small><b>₱{{ number_format($verifiedPaid, 2) }}</b>
                    </div>
                    <div><small>OUTSTANDING
                            BALANCE</small><b>₱{{ number_format($outstandingBalance, 2) }}</b></div>
                </div>

                <div class="sb-customer-name-editor">
                    <div class="sb-panel-head">
                        <div>
                            <h2>Edit customer name</h2>
                            <p>Updating this customer record also updates the name shown in all linked reservations and payments.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route($base . '.customers.update', $customer) }}" class="sb-customer-name-form">
                        @csrf
                        @method('PATCH')
                        <div class="sb-customer-name-field">
                            <label for="customer-full-name">Full name</label>
                            <input id="customer-full-name" name="full_name" value="{{ old('full_name', $customer->full_name) }}" required maxlength="255">
                        </div>
                        @error('full_name')
                            <span class="sb-field-error">{{ $message }}</span>
                        @enderror
                        <button class="sb-btn" type="submit">Save name</button>
                    </form>
                </div>
            </section>

            <div class="sb-section-head">
                <div>
                    <h2>Reservation history</h2>
                    <p>Gowns, rental dates, late returns, and account payments</p>
                </div>
            </div>
            @forelse($reservations as $reservation)
                <section class="sb-panel sb-customer-reservation-card">
                    <div class="sb-panel-head">
                        <div><span class="sb-kicker">{{ $reservation->reservation_code }}</span>
                            <h2>{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</h2>
                        </div><b>₱{{ number_format($reservation->grand_total, 2) }}</b>
                    </div>
                    <div class="sb-booking-info">
                        <div><small>PICKUP</small><b>{{ $reservation->pickup_date?->format('M d, Y') ?? '—' }}</b></div>
                        <div><small>RETURN DUE</small><b>{{ $reservation->return_date?->format('M d, Y') ?? '—' }}</b></div>
                        <div><small>ACTUAL
                                RETURN</small><b>{{ $reservation->gownReturn?->actual_return_date?->format('M d, Y') ?? 'Not returned' }}</b>
                        </div>
                        @if($reservation->gownReturn)
                            <div><small>RETURN CONDITION</small><b>{{ ucfirst(str_replace('_', ' ', $reservation->gownReturn->condition_after)) }}</b></div>
                        @endif
                        <div><small>LATE
                                DAYS</small><b>{{ $reservation->display_late_days }}{{ $reservation->projected_late_fee > 0 ? ' · projected ₱' . number_format($reservation->projected_late_fee, 2) . ' late fee' : '' }}</b>
                        </div>
                        <div><small>ID
                                COLLATERAL</small><b>{{ ucfirst(str_replace('_', ' ', $reservation->collateral_status ?? 'not received')) }}</b>
                        </div>
                    </div>
                    @if($reservation->gownReturn?->notes)
                        <div class="sb-return-inspection-note">
                            <b>Return inspection notes</b>
                            <p>{{ $reservation->gownReturn->notes }}</p>
                        </div>
                    @endif
                    @if($reservation->status === 'cancelled')
                        <section class="sb-cancellation-admin-summary">
                            <h3>Cancellation</h3>
                            <div><span>Cancelled by</span><b>{{ $reservation->cancelledBy?->name ?? 'Customer' }}</b></div>
                            <div><span>Cancelled at</span><b>{{ $reservation->cancelled_at?->format('M d, Y, g:i A') ?? '—' }}</b></div>
                            <div><span>Reason</span><b>{{ $reservation->cancellation_reason ?? 'Customer cancelled' }}</b></div>
                            <div><span>Down payment retained</span><b>₱{{ number_format($reservation->retained_down_payment, 2) }}</b></div>
                            <div><span>Refund</span><b>₱{{ number_format($reservation->cancellation_refund_amount, 2) }}</b></div>
                            <div><span>Payment status</span><b>{{ $reservation->retained_down_payment > 0 ? 'Retained · non-refundable' : 'No verified down payment' }}</b></div>
                            @if($reservation->cancellation_history->isNotEmpty())
                                <details>
                                    <summary>View Reservation History</summary>
                                    <ol>
                                        @foreach($reservation->cancellation_history as $event)
                                            <li><time>{{ $event->created_at?->format('M d, g:i A') }}</time> {{ $event->display_description }}</li>
                                        @endforeach
                                    </ol>
                                </details>
                            @endif
                        </section>
                    @endif
                    @if($reservation->measurements)
                    <p class="sb-form-intro">Measurements: {{ $reservation->measurements }}</p>@endif

                    <h3 class="sb-section-subtitle">Reserved gowns</h3>
                    <div class="sb-customer-item-grid">
                        @forelse($reservation->items as $item)
                            <article class="sb-customer-item-card">
                                <div class="sb-customer-item-photo">
                                    @if($item->gown)
                                        <img src="{{ $item->gown->image_url }}" alt="{{ $item->gown->name }}" loading="lazy">
                                    @else
                                        <span aria-hidden="true">Gown unavailable</span>
                                    @endif
                                </div>
                                <div class="sb-customer-item-copy">
                                    <span class="sb-customer-item-category">{{ $item->gown?->category?->name ?? 'Reserved gown' }}</span>
                                    <h4>{{ $item->gown?->name ?? 'Removed gown' }}</h4>
                                    <p>{{ $item->gown?->gown_code ?? 'Gown record unavailable' }}
                                        · Size {{ $item->gown?->size ?: '—' }}
                                        · {{ ucfirst($item->gown?->color ?: 'Color not specified') }}</p>
                                    <div class="sb-customer-item-price">
                                        <span>Quantity <b>{{ $item->quantity }}</b></span>
                                        <strong>₱{{ number_format((float) $item->rental_price, 2) }}</strong>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="sb-empty">No reserved items recorded.</div>
                        @endforelse
                    </div>
                    @if($reservation->gownPurchases->isNotEmpty())
                        <div class="sb-purchase-history">
                            <h3 class="sb-section-subtitle">Gowns purchased by customer</h3>
                            @foreach($reservation->gownPurchases as $purchase)
                                <p>
                                    <strong>{{ $purchase->gown?->name ?? 'Gown' }}</strong>
                                    · ₱{{ number_format($purchase->amount, 2) }}
                                    · {{ $purchase->purchased_at?->format('M d, Y') }}
                                </p>
                            @endforeach
                        </div>
                    @endif

                    <div class="sb-customer-finance-grid">
                        <div>
                            <h3 class="sb-section-subtitle">Payments</h3>
                            <div class="sb-table-wrap">
                                <table class="sb-table">
                                    <thead>
                                        <tr>
                                            <th>DATE</th>
                                            <th>TYPE / REFERENCE</th>
                                            <th>METHOD</th>
                                            <th>AMOUNT</th>
                                            <th>STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->created_at?->format('M d, Y') }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}<small
                                                        class="sb-cell-sub">{{ $payment->payment_reference }}</small></td>
                                                <td>{{ ucfirst($payment->payment_method) }}</td>
                                                <td>₱{{ number_format($payment->amount, 2) }}</td>
                                                <td>{{ $reservation->status === 'cancelled' && $payment->payment_type === 'downpayment' && $payment->status === 'verified' ? 'Retained' : ucfirst($payment->status) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="sb-empty">No payments recorded. Reservation is unpaid.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <h3 class="sb-section-subtitle">Penalties and balance</h3>
                            <div class="sb-table-wrap">
                                <table class="sb-table">
                                    <thead>
                                        <tr>
                                            <th>PENALTY</th>
                                            <th>AMOUNT</th>
                                            <th>STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->penalties as $penalty)
                                            <tr>
                                                <td>{{ $penalty->penalty_type === 'gown_purchase' ? 'Gown purchase' : ucfirst(str_replace('_', ' ', $penalty->penalty_type)) }}</td>
                                                <td>₱{{ number_format($penalty->amount, 2) }}</td>
                                                <td>{{ ucfirst($penalty->status) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="sb-empty">No penalties recorded.</td>
                                            </tr>
                                        @endforelse
                                        <tr>
                                            <td><strong>Outstanding balance</strong></td>
                                            <td colspan="2">
                                                <strong>₱{{ number_format($reservation->balance, 2) }}</strong>{{ $reservation->balance > 0 ? ' · payment due' : ' · paid' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            @empty
                <div class="sb-panel sb-empty">This customer has no reservations yet.</div>
            @endforelse
            @include('components.table-pagination', ['paginator' => $reservations, 'itemLabel' => 'reservations'])
        </div>
    </div>
</x-app-layout>
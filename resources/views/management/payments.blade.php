<x-app-layout>

    <div class="sb-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <h1>Payment <em>records.</em></h1>
                    <p>Verify customer receipts and track recorded rental payments.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <div class="sb-payment-toolbar">
                <form class="sb-payment-filters" method="GET" action="{{ route($base . '.payments') }}" data-live-filter>
                    <input type="hidden" name="per_page" value="{{ $payments->perPage() }}">
                    <label class="sb-payment-search">
                        <span class="sb-visually-hidden">Search payments</span>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Search payments">
                    </label>
                    <label>
                        <span class="sb-visually-hidden">Filter by status</span>
                        <select name="status">
                            <option value="">All statuses</option>
                            @foreach(['pending' => 'Needs review', 'verified' => 'Verified', 'rejected' => 'Rejected', 'refunded' => 'Refunded'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="sb-visually-hidden">Filter by payment method</span>
                        <select name="method">
                            <option value="">All methods</option>
                            <option value="cash" @selected(($filters['method'] ?? '') === 'cash')>Cash</option>
                            <option value="gcash" @selected(($filters['method'] ?? '') === 'gcash')>GCash</option>
                        </select>
                    </label>
                    <label>
                        <span class="sb-visually-hidden">From date</span>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                    </label>
                    <label>
                        <span class="sb-visually-hidden">To date</span>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                    </label>
                    <button class="sb-payment-filter-button" type="submit">Filter</button>
                </form>
                <button class="sb-payment-add-button" type="button" data-open-record-payment
                    aria-label="Record cash received against an outstanding reservation balance">+ Record payment</button>
            </div>

            @if($reviewCount > 0)
                <a class="sb-payment-attention" href="{{ route($base . '.payments', ['status' => 'pending']) }}">
                    <span><b>Needs attention</b><small>{{ $reviewCount }} payment{{ $reviewCount === 1 ? '' : 's' }} waiting
                            for verification</small></span>
                    <strong>₱{{ number_format($reviewAmount, 2) }}</strong>
                    <span class="sb-payment-attention-link">Review pending payments →</span>
                </a>
            @endif

            <div class="sb-payment-stats" aria-label="Payment summary">
                <div><span>Outstanding balance</span><strong>₱{{ number_format($openReservations->sum('balance'), 2) }}</strong><small>{{ $openReservations->count() }}
                        open reservation{{ $openReservations->count() === 1 ? '' : 's' }}</small></div>
                <div><span>Verified</span><strong>₱{{ number_format($verifiedAmount, 2) }}</strong><small>{{ $verifiedCount }}
                        transaction{{ $verifiedCount === 1 ? '' : 's' }}</small></div>
                <div><span>Needs review</span><strong>₱{{ number_format($reviewAmount, 2) }}</strong><small>{{ $reviewCount }}
                        transaction{{ $reviewCount === 1 ? '' : 's' }}</small></div>
            </div>

            <section class="sb-payment-transactions" aria-labelledby="payment-transactions-title">
                <div class="sb-payment-transactions-head">
                    <h2 id="payment-transactions-title">Payment transactions</h2>
                    <span>{{ $payments->total() }} result{{ $payments->total() === 1 ? '' : 's' }}</span>
                </div>
                <div class="sb-table-wrap">
                    <table class="sb-payment-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Customer</th>
                                <th>Reservation</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                @php
                                    $paymentStatusLabel = $payment->reservation?->status === 'cancelled'
                                        && $payment->payment_type === 'downpayment'
                                        && $payment->status === 'verified'
                                        ? 'Retained'
                                        : ($payment->status === 'pending' ? 'Pending review' : ucfirst($payment->status));
                                    $canReview = $payment->status === 'pending'
                                        && !in_array($payment->reservation?->status, ['cancelled', 'rejected', 'completed'], true);
                                @endphp
                                <tr class="sb-payment-row" tabindex="0"
                                    aria-label="View payment {{ $payment->payment_reference }}">
                                    <td><button type="button" class="sb-payment-id-button" data-payment-open
                                            data-reference="{{ $payment->payment_reference }}"
                                            data-customer="{{ $payment->customer->full_name ?? 'Customer' }}"
                                            data-reservation="{{ $payment->reservation->reservation_code ?? 'Reservation' }}"
                                            data-type="{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}"
                                            data-method="{{ ucfirst($payment->payment_method) }}"
                                            data-amount="{{ number_format($payment->amount, 2, '.', '') }}"
                                            data-date="{{ $payment->created_at?->format('F j, Y, g:i A') }}"
                                            data-status="{{ $paymentStatusLabel }}"
                                            data-proof="{{ $payment->proof_of_payment ? route('payments.proof', $payment) : '' }}"
                                            data-review="{{ $canReview ? route($base . '.payments.update', $payment) : '' }}"
                                            data-remarks="{{ $payment->remarks }}">{{ $payment->payment_reference }}</button>
                                    </td>
                                    <td>{{ $payment->customer->full_name ?? 'Customer' }}</td>
                                    <td>{{ $payment->reservation->reservation_code ?? 'Reservation' }}<small>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</small>
                                    </td>
                                    <td>{{ ucfirst($payment->payment_method) }}</td>
                                    <td class="sb-payment-table-amount">₱{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->created_at?->format('M j, Y') }}</td>
                                    <td><span
                                            class="sb-payment-status is-{{ $payment->status }}">{{ $paymentStatusLabel }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="sb-payment-table-empty">No payments match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('components.table-pagination', ['paginator' => $payments, 'itemLabel' => 'payments'])
            </section>

            <dialog class="sb-payment-dialog" data-payment-dialog aria-labelledby="payment-dialog-title">
                <div class="sb-payment-dialog-head">
                    <div><span class="sb-kicker">PAYMENT DETAILS</span>
                        <h2 id="payment-dialog-title">Transaction</h2>
                    </div><button type="button" data-close-payment aria-label="Close">×</button>
                </div>
                <span class="sb-payment-dialog-status" data-payment-detail-status></span>
                <strong class="sb-payment-dialog-amount" data-payment-detail-amount></strong>
                <dl class="sb-payment-detail-list">
                    <div>
                        <dt>Payment ID</dt>
                        <dd data-payment-detail-reference></dd>
                    </div>
                    <div>
                        <dt>Date</dt>
                        <dd data-payment-detail-date></dd>
                    </div>
                    <div>
                        <dt>Method</dt>
                        <dd data-payment-detail-method></dd>
                    </div>
                    <div>
                        <dt>Customer</dt>
                        <dd data-payment-detail-customer></dd>
                    </div>
                    <div>
                        <dt>Reservation</dt>
                        <dd><span data-payment-detail-reservation></span><small data-payment-detail-type></small></dd>
                    </div>
                </dl>
                <div class="sb-payment-proof" data-payment-proof-section hidden>
                    <h3>Payment proof</h3><img data-payment-proof-image alt="Customer payment proof"><a
                        data-payment-proof-link target="_blank" rel="noopener">View full size</a>
                </div>
                <div class="sb-payment-note" data-payment-detail-remarks hidden></div>
                <form class="sb-payment-review-form" data-payment-review-form method="POST" hidden>
                    @csrf @method('PATCH')
                    <h3>Review</h3>
                    <label>Optional review note<textarea name="remarks" rows="3" maxlength="500"></textarea></label>
                    <div><button type="submit" name="status" value="rejected"
                            class="sb-reject-btn">Reject</button><button type="submit" name="status" value="verified">✓
                            Verify payment</button></div>
                </form>
                <button class="sb-outline-btn sb-payment-dialog-close" type="button" data-close-payment>Close</button>
            </dialog>

            <dialog class="sb-payment-dialog sb-record-payment-dialog" data-record-payment-dialog
                aria-labelledby="record-payment-title">
                <form method="POST" data-record-payment-form>
                    @csrf
                    <div class="sb-payment-dialog-head">
                        <div><span class="sb-kicker">PAYMENT ENTRY</span>
                            <h2 id="record-payment-title">Record payment</h2>
                        </div><button type="button" data-close-record-payment aria-label="Close">×</button>
                    </div>
                    @if($openReservations->isEmpty())
                        <p>No reservations currently have an outstanding balance. Use this form to record cash already collected against an existing reservation; start a new reservation to record its first payment.</p>
                    @else
                        <label>Reservation<select name="reservation_picker" data-record-reservation required>
                                @foreach($openReservations as $reservation)
                                    <option value="{{ $reservation->id }}"
                                        data-action="{{ route($base . '.payments.store', $reservation) }}"
                                        data-balance="{{ number_format($reservation->balance, 2, '.', '') }}"
                                        @selected((string) request('reservation_id') === (string) $reservation->id)>
                                        {{ $reservation->reservation_code }}·
                                        {{ $reservation->customer->full_name ?? 'Customer' }}·
                                        ₱{{ number_format($reservation->balance, 2) }} due</option>
                                @endforeach
                            </select></label>
                        <input type="hidden" name="payment_method" value="cash">
                        <label>Payment type<select name="payment_type" required>
                                <option value="downpayment">Down payment</option>
                                <option value="rental_balance">Rental balance</option>
                                <option value="other">Other</option>
                            </select></label>
                        <label>Amount received (PHP)<input type="number" name="amount" min="0.01" step="0.01" required
                                data-record-amount></label>
                        <label>Note (optional)<input name="remarks" maxlength="500"
                                placeholder="Optional payment note"></label>
                        <div class="sb-payment-review-actions"><button class="sb-outline-btn" type="button"
                                data-close-record-payment>Cancel</button><button type="submit">Save payment</button></div>
                    @endif
                </form>
            </dialog>

            <script>
                (() => {
                    const paymentDialog = document.querySelector('[data-payment-dialog]');
                    const detailForm = paymentDialog.querySelector('[data-payment-review-form]');
                    const openPayment = trigger => {
                        const data = trigger.dataset;
                        paymentDialog.querySelector('[data-payment-detail-reference]').textContent = data.reference;
                        paymentDialog.querySelector('[data-payment-detail-customer]').textContent = data.customer;
                        paymentDialog.querySelector('[data-payment-detail-reservation]').textContent = data.reservation;
                        paymentDialog.querySelector('[data-payment-detail-type]').textContent = data.type;
                        paymentDialog.querySelector('[data-payment-detail-method]').textContent = data.method;
                        paymentDialog.querySelector('[data-payment-detail-amount]').textContent = '₱' + Number(data.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        paymentDialog.querySelector('[data-payment-detail-date]').textContent = data.date;
                        paymentDialog.querySelector('[data-payment-detail-status]').textContent = data.status;
                        const proofSection = paymentDialog.querySelector('[data-payment-proof-section]');
                        proofSection.hidden = !data.proof;
                        if (data.proof) {
                            paymentDialog.querySelector('[data-payment-proof-image]').src = data.proof;
                            paymentDialog.querySelector('[data-payment-proof-link]').href = data.proof;
                        }
                        const remarks = paymentDialog.querySelector('[data-payment-detail-remarks]');
                        remarks.textContent = data.remarks;
                        remarks.hidden = !data.remarks;
                        detailForm.hidden = !data.review;
                        detailForm.action = data.review || '';
                        detailForm.querySelector('[name="remarks"]').value = '';
                        paymentDialog.showModal();
                    };
                    document.querySelectorAll('.sb-payment-row').forEach(row => {
                        const trigger = row.querySelector('[data-payment-open]');
                        row.addEventListener('click', event => {
                            if (event.target.closest('button, a, input, select, textarea, form')
                                && !event.target.closest('[data-payment-open]')) return;
                            openPayment(trigger);
                        });
                        row.addEventListener('keydown', event => {
                            if ((event.key === 'Enter' || event.key === ' ') && event.target === row) {
                                event.preventDefault();
                                openPayment(trigger);
                            }
                        });
                    });
                    paymentDialog.querySelectorAll('[data-close-payment]').forEach(button => button.addEventListener('click', () => paymentDialog.close()));

                    const recordDialog = document.querySelector('[data-record-payment-dialog]');
                    const recordForm = recordDialog.querySelector('[data-record-payment-form]');
                    const reservationSelect = recordDialog.querySelector('[data-record-reservation]');
                    const amountField = recordDialog.querySelector('[data-record-amount]');
                    const typeField = recordDialog.querySelector('[name="payment_type"]');
                    const updateRecordAction = () => {
                        const selected = reservationSelect?.selectedOptions[0];
                        if (!selected) return;
                        recordForm.action = selected.dataset.action;
                        amountField.max = selected.dataset.balance;
                        if (Number(selected.dataset.balance) <= 500) typeField.value = 'rental_balance';
                    };
                    reservationSelect?.addEventListener('change', updateRecordAction);
                    amountField?.addEventListener('input', () => {
                        if (amountField.max && Number(amountField.value) >= Number(amountField.max)) typeField.value = 'rental_balance';
                    });
                    updateRecordAction();
                    document.querySelector('[data-open-record-payment]').addEventListener('click', () => recordDialog.showModal());
                    const requestedReservationId = new URLSearchParams(window.location.search).get('reservation_id');
                    if (requestedReservationId && reservationSelect?.value === requestedReservationId) {
                        recordDialog.showModal();
                    }
                    recordDialog.querySelectorAll('[data-close-record-payment]').forEach(button => button.addEventListener('click', () => recordDialog.close()));
                    [paymentDialog, recordDialog].forEach(dialog => dialog.addEventListener('click', event => {
                        if (event.target === dialog) dialog.close();
                    }));
                })();
            </script>

        </div>
    </div>

</x-app-layout>

<x-app-layout>
    <div class="sb-page sb-reserve-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">IN-STORE BOOKING</span>
                    <h1>Reserve <em>{{ $cartGowns->count() }}</em> gown{{ $cartGowns->count() === 1 ? '' : 's' }}.</h1>
                    <p>
                        @if($reservationSource === 'single')
                            Complete the five steps for this gown only. Your My Cart selections stay separate.
                        @else
                            Complete the five steps to record this bulk in-store reservation.
                        @endif
                    </p>
                </div>
                <a class="sb-outline-btn sb-back-btn" href="{{ route(auth()->user()->role . '.catalog') }}">Back to collection</a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors" role="alert">{{ $errors->first() }}</div>
            @endif
            @if($lateFeePerDay <= 0)
                <div class="sb-form-errors" role="alert">Set a positive daily late fee in Settings before accepting reservations.</div>
            @endif

            <div class="sb-reserve-layout">
                <aside class="sb-reserve-summary">
                    <div class="sb-reserve-summary-body">
                        <span class="sb-kicker">SELECTED GOWNS</span>
                        <h2>{{ $cartGowns->count() }} gown{{ $cartGowns->count() === 1 ? '' : 's' }}</h2>
                        <p>One reservation · one agreement · one payment</p>
                        <div class="sb-cart-list">
                            @foreach($cartGowns as $cartGown)
                                <div class="sb-cart-item">
                                    <a class="sb-cart-item-photo"
                                        style="background-image:url('{{ $cartGown->image_url }}')"
                                        href="{{ route(auth()->user()->role . '.catalog.show', $cartGown) }}"
                                        aria-label="{{ $cartGown->name }}"></a>
                                    <div class="sb-cart-item-info">
                                        <b>{{ $cartGown->name }}</b>
                                        <small>{{ $cartGown->gown_code }} · {{ $cartGown->size ?? 'Various' }}</small>
                                        <span class="sb-cart-item-price">₱{{ number_format((float) $cartGown->rental_price, 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="sb-reserve-summary-rows">
                            <div><span>Gowns</span><b>{{ $cartGowns->count() }}</b></div>
                            <div><span>Daily late fee</span><b>₱{{ number_format($lateFeePerDay, 2) }}</b></div>
                            <div><span>Maximum rental</span><b>3 days</b></div>
                        </div>
                        <div class="sb-reserve-summary-total">
                            <span>Rental total</span><b data-total-display>₱{{ number_format((float) $cartGowns->sum(fn($g) => (float) $g->rental_price), 2) }}</b>
                        </div>
                        <div class="sb-reserve-summary-note">The selected gowns will be saved together in one reservation with a single agreement and payment.</div>
                    </div>
                </aside>

                <div class="sb-reserve-main">
                    <form method="POST" action="{{ route(auth()->user()->role . '.reservations.store') }}"
                        enctype="multipart/form-data" class="sb-reservation-form" data-reservation-wizard>
                        @csrf
                        <input type="hidden" name="reservation_source" value="{{ $reservationSource }}">

                        <div class="sb-progress-head">
                            <div class="sb-progress-meta">
                                <b data-progress-label>Step 1 of 5 · Customer &amp; dates</b>
                                <span data-progress-percent>20% complete</span>
                            </div>
                            <div class="sb-progress-track" role="progressbar" aria-label="Reservation progress"
                                aria-valuemin="1" aria-valuemax="5" aria-valuenow="1">
                                <div class="sb-progress-fill" data-progress-fill style="width:20%"></div>
                            </div>
                        </div>

                        <ol class="sb-step-dots" aria-label="Reservation steps">
                            <li data-step-indicator class="is-active"><span>1</span><b>Customer &amp; dates</b></li>
                            <li data-step-indicator><span>2</span><b>Gown &amp; sizing</b></li>
                            <li data-step-indicator><span>3</span><b>Agreement &amp; ID</b></li>
                            <li data-step-indicator><span>4</span><b>Payment</b></li>
                            <li data-step-indicator><span>5</span><b>Confirmation</b></li>
                        </ol>

                        <div class="sb-reserve-card">
                            <div class="sb-form-errors" data-step-error role="alert" hidden></div>

                            <section class="sb-flow-step">
                                <span class="sb-kicker">STEP 1</span>
                                <h2>Customer &amp; dates</h2>
                                <label>Existing customer account
                                    <select name="existing_customer_id">
                                        <option value="">Guest / new customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('existing_customer_id') == $customer->id)>
                                                {{ $customer->full_name }} · {{ $customer->contact_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="sb-form-row">
                                    <label>Guest full name
                                        <input name="customer_name" value="{{ old('customer_name') }}"
                                            placeholder="Required for a new guest">
                                    </label>
                                    <label>Guest contact number
                                        <input name="contact_number" value="{{ old('contact_number') }}"
                                            placeholder="Required for a new guest">
                                    </label>
                                </div>
                                <label>Guest email
                                    <input type="email" name="customer_email" value="{{ old('customer_email') }}">
                                </label>
                                <div class="sb-form-row">
                                    <label>Pickup date
                                        <input id="pickup-date" type="date" name="pickup_date"
                                            min="{{ today()->format('Y-m-d') }}" value="{{ old('pickup_date') }}" required>
                                    </label>
                                    <label>Return date
                                        <input id="return-date" type="date" name="return_date"
                                            min="{{ today()->format('Y-m-d') }}" value="{{ old('return_date') }}" required>
                                    </label>
                                </div>
                                <div class="sb-availability" data-availability data-state="idle" aria-live="polite">
                                    <span class="sb-availability-icon" data-availability-icon>…</span>
                                    <div><b data-availability-title>Checking availability</b>
                                        <p data-availability-text>Choose dates to confirm every gown in this reservation is free.</p>
                                    </div>
                                </div>
                                <p class="sb-form-intro">Rental is up to {{ \App\Http\Controllers\Customer\DashboardController::MAX_RENTAL_DAYS }} days, followed by a {{ \App\Http\Controllers\Customer\DashboardController::CLEANING_BUFFER_DAYS }}-day cleaning buffer.</p>
                            </section>

                            <section class="sb-flow-step" hidden>
                                <span class="sb-kicker">STEP 2</span>
                                <h2>Gowns &amp; sizing</h2>
                                <div class="sb-selected-gowns">
                                    @foreach($cartGowns as $cartGown)
                                        <div class="sb-selected-gown" data-gown-row="{{ $cartGown->id }}">
                                            <b>{{ $cartGown->name }}</b>
                                            <small>{{ $cartGown->gown_code }} · {{ $cartGown->size ?? 'Various sizes' }}</small>
                                            <strong>₱{{ number_format((float) $cartGown->rental_price, 2) }}</strong>
                                            <input type="hidden" name="gown_ids[]" value="{{ $cartGown->id }}"
                                                data-price="{{ (float) $cartGown->rental_price }}">
                                            @if($cartGowns->count() > 1)
                                                <button type="button" class="sb-cart-item-remove" data-remove-gown-btn
                                                    data-gown-id="{{ $cartGown->id }}"
                                                    aria-label="Remove {{ $cartGown->name }}">×</button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <p class="sb-form-intro">Record the customer’s measurements in centimeters to help prepare the fit.</p>
                                <div class="sb-form-row">
                                    <label>Bust (cm)
                                        <input name="bust" type="number" min="0" max="300" step="0.1" value="{{ old('bust') }}">
                                    </label>
                                    <label>Waist (cm)
                                        <input name="waist" type="number" min="0" max="300" step="0.1" value="{{ old('waist') }}">
                                    </label>
                                </div>
                                <div class="sb-form-row">
                                    <label>Hips (cm)
                                        <input name="hips" type="number" min="0" max="300" step="0.1" value="{{ old('hips') }}">
                                    </label>
                                    <label>Length (cm)
                                        <input name="length" type="number" min="0" max="400" step="0.1" value="{{ old('length') }}">
                                    </label>
                                </div>
                            </section>

                            @include('components.reservation-agreement-step', [
                                'gownCount' => $cartGowns->count(),
                                'maxRentalDays' => 3,
                                'lateFeePerDay' => $lateFeePerDay,
                            ])

                            <section class="sb-flow-step" hidden>
                                <span class="sb-kicker">STEP 4</span>
                                <h2>Payment</h2>
                                <div class="sb-payment-breakdown">
                                    <h3>Payment breakdown</h3>
                                    <div><span>Rental fee (all gowns)</span><b data-total-display>₱{{ number_format((float) $cartGowns->sum(fn($g) => (float) $g->rental_price), 2) }}</b></div>
                                    <div><span>Total amount due</span><b data-total-display>₱{{ number_format((float) $cartGowns->sum(fn($g) => (float) $g->rental_price), 2) }}</b></div>
                                </div>
                                <div class="sb-method-options sb-payment-choice-options" role="radiogroup"
                                    aria-label="Payment amount">
                                    <label class="sb-method-option">
                                        <input type="radio" name="payment_option" value="full" required
                                            data-payment-option @checked(old('payment_option', 'full') === 'full')>
                                        <span><b>Full payment</b><small data-full-payment-label>₱{{ number_format((float) $cartGowns->sum(fn($g) => (float) $g->rental_price), 2) }}</small></span>
                                    </label>
                                    <label class="sb-method-option">
                                        <input type="radio" name="payment_option" value="downpayment" required
                                            data-payment-option @checked(old('payment_option') === 'downpayment')>
                                        <span><b>Down payment</b><small>More than ₱500.00</small></span>
                                    </label>
                                </div>
                                @error('payment_option')<small class="sb-check-error">{{ $message }}</small>@enderror
                                <label>Amount to pay now (PHP)
                                    <input type="number" name="payment_amount" min="0.01"
                                        max="{{ (float) $cartGowns->sum(fn($g) => (float) $g->rental_price) }}" step="0.01"
                                        value="{{ old('payment_amount', (float) $cartGowns->sum(fn($g) => (float) $g->rental_price)) }}" required
                                        data-payment-amount>
                                    <small>Payment is recorded as pending and must be verified by staff.</small>
                                </label>
                                <input type="hidden" name="payment_method" value="cash">
                                <p class="sb-payment-method-note">Payment method <b>Cash</b></p>
                                <p>Remaining balance: <b data-balance-remaining>₱0.00</b></p>
                            </section>

                            <section class="sb-flow-step" hidden>
                                <span class="sb-kicker">STEP 5</span>
                                <h2>Confirmation</h2>
                                <p class="sb-form-intro">Review the details before saving this in-store reservation.</p>
                                <div class="sb-confirm-list">
                                    <section class="sb-confirm-group">
                                        <h3>Reservation</h3>
                                        <div><span>Customer</span><b data-confirm="customer">Guest / new customer</b></div>
                                        <div><span>Gowns</span><b data-confirm="gowns">{{ $cartGowns->map(fn($g) => $g->name . ' · ' . $g->gown_code)->join(' + ') }}</b></div>
                                        <div><span>Pickup</span><b data-confirm="pickup_date">—</b></div>
                                        <div><span>Return</span><b data-confirm="return_date">—</b></div>
                                    </section>
                                    <section class="sb-confirm-group">
                                        <h3>Payment</h3>
                                        <div><span>Payment method</span><b>Cash</b></div>
                                        <div><span>Payment option</span><b data-confirm="payment_option">Full payment</b></div>
                                        <div><span>Amount to pay now</span><b data-confirm="payment_amount">—</b></div>
                                    </section>
                                    <section class="sb-confirm-group">
                                        <h3>Agreement &amp; ID custody</h3>
                                        <div><span>ID photo</span><b>Uploaded privately</b></div>
                                        <div><span>Physical ID</span><b>Required at pickup</b></div>
                                        <div><span>Payment review</span><b>Pending staff verification</b></div>
                                    </section>
                                </div>
                            </section>

                            <div class="sb-step-controls">
                                <button type="button" class="sb-outline-btn sb-back-btn" data-step-back disabled>Back</button>
                                <span data-step-count>Step 1 of 5</span>
                                <button type="button" class="sb-btn" data-step-next>Continue</button>
                                <button class="sb-btn sb-btn-confirm" type="submit" data-step-submit hidden>Submit reservation request</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-reservation-wizard]');
            const steps = [...form.querySelectorAll('.sb-flow-step')];
            const indicators = [...form.querySelectorAll('[data-step-indicator]')];
            const errors = @json(array_keys($errors->getMessages()));
            const labels = ['Customer & dates', 'Gowns & sizing', 'Agreement & ID', 'Payment', 'Confirmation'];
            const existing = form.querySelector('[name="existing_customer_id"]');
            const guestName = form.querySelector('[name="customer_name"]');
            const guestPhone = form.querySelector('[name="contact_number"]');
            const pickup = form.querySelector('[name="pickup_date"]');
            const returned = form.querySelector('[name="return_date"]');
            const paymentAmount = form.querySelector('[name="payment_amount"]');
            const paymentOptions = [...form.querySelectorAll('[data-payment-option]')];
            const availabilityBox = form.querySelector('[data-availability]');
            const availabilityUrl = @json(route(auth()->user()->role . '.reservations.availability', [], false));
            const maxRentalDays = @json(\App\Http\Controllers\Customer\DashboardController::MAX_RENTAL_DAYS);
            let datesVerified = false;
            let availabilityRequest = 0;
            let current = steps.findIndex(step =>
                errors.some(name => step.querySelector(`[name="${name}"]`))
            );

            if (current < 0) current = 0;

            const peso = n => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const selectedPaymentOption = () => paymentOptions.find(input => input.checked)?.value || 'full';
            const updatePayment = () => {
                const total = [...form.querySelectorAll('input[name="gown_ids[]"]')].reduce((sum, input) => {
                    return sum + Number(input.dataset.price || 0);
                }, 0);
                const isFull = selectedPaymentOption() === 'full';
                paymentAmount.max = total.toFixed(2);
                paymentAmount.min = isFull ? '0.01' : '500.01';
                paymentAmount.readOnly = isFull;
                if (isFull) {
                    paymentAmount.value = total.toFixed(2);
                } else if (Number(paymentAmount.value) >= total) {
                    paymentAmount.value = '';
                }
                form.querySelector('[data-full-payment-label]').textContent = peso(total);
                form.querySelector('[data-balance-remaining]').textContent = peso(Math.max(0, total - (Number(paymentAmount.value) || 0)));
            };

            /* ---- Gowns carried over from the staff cart ---- */
            const refreshGownTotals = () => {
                const total = [...form.querySelectorAll('input[name="gown_ids[]"]')].reduce((sum, input) => {
                    return sum + Number(input.dataset.price || 0);
                }, 0);
                form.querySelectorAll('[data-total-display]').forEach(el => el.textContent = peso(total));
                updatePayment();
                const gownNames = [...form.querySelectorAll('[data-gown-row]')].map(row => {
                    return row.querySelector('b').textContent + ' · ' + row.querySelector('small').textContent.split(' · ')[0];
                });
                form.querySelector('[data-confirm="gowns"]').textContent = gownNames.length
                    ? gownNames.join(' + ')
                    : '—';
                updateSummary();
                checkAvailability();
            };

            // Staff can remove selected gowns here; adding gowns happens in the cart.
            form.querySelectorAll('[data-remove-gown-btn]').forEach(button => {
                button.addEventListener('click', () => {
                    const row = button.closest('[data-gown-row]');
                    const hidden = row.querySelector('input[name="gown_ids[]"]');
                    if (hidden) hidden.remove();
                    row.remove();
                    refreshGownTotals();
                });
            });

            const setGuestRequired = () => {
                guestName.required = guestPhone.required = !existing.value;
            };

            const updateSummary = () => {
                const customer = existing.value
                    ? existing.selectedOptions[0].textContent.trim()
                    : (guestName.value.trim() || 'Guest / new customer');
                form.querySelector('[data-confirm="customer"]').textContent = customer;
                form.querySelector('[data-confirm="pickup_date"]').textContent = pickup.value || '—';
                form.querySelector('[data-confirm="return_date"]').textContent = returned.value || '—';
                form.querySelector('[data-confirm="payment_amount"]').textContent = paymentAmount.value
                    ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(paymentAmount.value))
                    : '—';
                form.querySelector('[data-confirm="payment_option"]').textContent =
                    selectedPaymentOption() === 'full' ? 'Full payment' : 'Down payment';
            };

            const setAvailability = (state, title, text) => {
                availabilityBox.dataset.state = state;
                availabilityBox.querySelector('[data-availability-icon]').textContent =
                    state === 'ok' ? '✓' : (state === 'error' ? '!' : '…');
                availabilityBox.querySelector('[data-availability-title]').textContent = title;
                availabilityBox.querySelector('[data-availability-text]').textContent = text;
            };

            const checkAvailability = async () => {
                const requestId = ++availabilityRequest;
                datesVerified = false;
                const gownIds = [...form.querySelectorAll('input[name="gown_ids[]"]')].map(input => input.value);
                if (!pickup.value || !returned.value || gownIds.length === 0) {
                    setAvailability('idle', 'Checking availability', 'Choose dates and at least one gown to check availability.');
                    return;
                }
                setAvailability('busy', 'Checking availability', 'Verifying every gown is free for these dates…');
                try {
                    const query = new URLSearchParams({ pickup_date: pickup.value, return_date: returned.value });
                    gownIds.forEach(id => query.append('gown_ids[]', id));
                    const response = await fetch(availabilityUrl + '?' + query, {
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) throw new Error('Availability check failed.');
                    const result = await response.json();
                    if (requestId !== availabilityRequest) return;
                    datesVerified = result.available;
                    setAvailability(result.available ? 'ok' : 'error',
                        result.available ? 'All gowns are available' : 'Not available for these dates',
                        result.available ? `${result.summary} · ${result.rental_days} day rental` : result.reason);
                } catch (error) {
                    if (requestId !== availabilityRequest) return;
                    setAvailability('error', 'Could not verify availability', 'Try again, or contact the boutique to book these gowns.');
                }
            };

            const show = () => {
                steps.forEach((step, index) => step.hidden = index !== current);
                indicators.forEach((indicator, index) => {
                    indicator.classList.toggle('is-active', index === current);
                    indicator.classList.toggle('is-complete', index < current);
                });

                const percent = Math.round(((current + 1) / steps.length) * 100);
                form.querySelector('[data-step-count]').textContent = `Step ${current + 1} of ${steps.length}`;
                form.querySelector('[data-progress-label]').textContent = `Step ${current + 1} of ${steps.length} · ${labels[current]}`;
                form.querySelector('[data-progress-percent]').textContent = `${percent}% complete`;
                form.querySelector('[data-progress-fill]').style.width = `${percent}%`;
                form.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', String(current + 1));
                form.querySelector('[data-step-back]').disabled = current === 0;
                form.querySelector('[data-step-next]').hidden = current === steps.length - 1;
                form.querySelector('[data-step-submit]').hidden = current !== steps.length - 1;
                updateSummary();
            };

            existing.addEventListener('change', setGuestRequired);
            [guestName, pickup, returned, paymentAmount].forEach(field => {
                field.addEventListener('input', updateSummary);
                field.addEventListener('change', updateSummary);
            });
            paymentOptions.forEach(input => input.addEventListener('change', () => {
                updatePayment();
                updateSummary();
            }));
            setGuestRequired();
            refreshGownTotals();

            form.querySelector('[data-step-back]').addEventListener('click', () => {
                if (current > 0) {
                    current--;
                    show();
                }
            });

            form.querySelector('[data-step-next]').addEventListener('click', () => {
                const invalid = [...steps[current].querySelectorAll('input,select,textarea')]
                    .find(field => !field.checkValidity());

                if (invalid) {
                    invalid.reportValidity();
                    return;
                }
                if (current === 0 && !datesVerified) {
                    setAvailability('error', 'Availability not confirmed', 'Wait for a successful date availability check before continuing.');
                    checkAvailability();
                    return;
                }

                if (current < steps.length - 1) {
                    current++;
                    show();
                }
            });

            const dateLimit = () => {
                if (!pickup.value) {
                    datesVerified = false;
                    checkAvailability();
                    return;
                }

                const [year, month, day] = pickup.value.split('-').map(Number);
                const max = new Date(Date.UTC(year, month - 1, day + maxRentalDays - 1)).toISOString().slice(0, 10);
                returned.min = pickup.value;
                returned.max = max;

                // Auto-set the return date to the last rental day; staff can still
                // shorten it to any date within the window.
                if (!returned.value || returned.value < pickup.value || returned.value > max) {
                    returned.value = max;
                }

                updateSummary();
                checkAvailability();
            };

            pickup.addEventListener('change', dateLimit);
            returned.addEventListener('change', checkAvailability);
            paymentAmount.addEventListener('input', () => {
                const total = [...form.querySelectorAll('input[name="gown_ids[]"]')].reduce((sum, input) => sum + Number(input.dataset.price || 0), 0);
                form.querySelector('[data-balance-remaining]').textContent = peso(Math.max(0, total - (Number(paymentAmount.value) || 0)));
                updateSummary();
            });
            dateLimit();
            show();
        })();
    </script>
</x-app-layout>

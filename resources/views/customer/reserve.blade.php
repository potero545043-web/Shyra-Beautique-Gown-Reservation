<x-app-layout>
    @php
        $rentalTotal = $gowns->sum(fn($g) => (float) $g->rental_price);
        $gownCount = $gowns->count();
    @endphp
    <div class="sb-page sb-reserve-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div><span class="sb-kicker">RESERVATION REQUEST</span>
                    <h1>Reserve your <em>gowns.</em></h1>
                    <p>
                        @if($reservationSource === 'single')
                            Review this gown, choose your dates, and submit its reservation. Your My Cart selections stay separate.
                        @else
                            Add every gown you need, set the dates once, and submit a single request. Nothing is sent until you confirm at the end.
                        @endif
                    </p>
                </div><a class="sb-outline-btn sb-back-btn" href="{{ route('customer.catalog') }}">Back to collection</a>
            </div>
            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="sb-form-errors" role="alert">
                    @if($errors->has('reservation'))
                        {{ $errors->first('reservation') }}
                    @else
                        Please correct this reservation issue: {{ $errors->first() }}
                    @endif
                </div>
            @endif
            @if($lateFeePerDay <= 0)
                <div class="sb-form-errors">The shop needs to configure its daily late fee before accepting
                    reservations.
            </div>@endif

            <div class="sb-reserve-layout">
                <aside class="sb-reserve-summary">
                    <div class="sb-reserve-summary-body">
                        <span class="sb-kicker">YOUR RESERVATION</span>
                        <h2>{{ $gownCount }} gown{{ $gownCount === 1 ? '' : 's' }} selected</h2>
                        <p>One reservation · one agreement · one payment</p>

                        <div class="sb-cart-list">
                            @foreach($gowns as $gown)
                                <div class="sb-cart-item">
                                    <a class="sb-cart-item-photo" style="background-image:url('{{ $gown->image_url }}')"
                                        href="{{ route('customer.gowns.show', $gown) }}" aria-label="{{ $gown->name }}"></a>
                                    <div class="sb-cart-item-info">
                                        <b>{{ $gown->name }}</b>
                                        <small>{{ $gown->gown_code }} · {{ $gown->size ?? 'Various' }}</small>
                                        <span
                                            class="sb-cart-item-price">₱{{ number_format((float) $gown->rental_price, 2) }}</span>
                                    </div>
                                    @if($reservationSource === 'cart')
                                        <form method="POST" action="{{ route('customer.cart.remove', $gown) }}"
                                            class="sb-cart-item-remove">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Remove {{ $gown->name }}"
                                                title="Remove">×</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="sb-reserve-summary-rows">
                            <div><span>Daily late fee</span><b>₱{{ number_format($lateFeePerDay, 2) }}</b></div>
                            <div><span>Max rental</span><b>{{ $maxRentalDays }} days</b></div>
                        </div>
                        <div class="sb-reserve-summary-total"><span>Rental total</span><b
                                data-total-display>₱{{ number_format($rentalTotal, 2) }}</b></div>
                        <div class="sb-reserve-summary-note">Bring one valid government-issued ID at pickup. Staff
                            will keep it securely as a record and return it after the gowns are returned.
                        </div>
                    </div>
                </aside>

                <div class="sb-reserve-main">
                    <form method="POST" action="{{ route('customer.reserve.store') }}" enctype="multipart/form-data"
                        class="sb-reservation-form" data-reservation-wizard>@csrf
                        @foreach($gowns as $gown)
                            <input type="hidden" name="gown_ids[]" value="{{ $gown->id }}">
                        @endforeach
                        <input type="hidden" name="reservation_source" value="{{ $reservationSource }}">
                        <div class="sb-form-errors" data-step-error role="alert" hidden></div>

                        <div class="sb-progress-head">
                            <div class="sb-progress-meta">
                                <b data-progress-label>Step 1 of 5 · Customer &amp; dates</b>
                                <span data-progress-percent>20% complete</span>
                            </div>
                            <div class="sb-progress-track" role="progressbar" aria-valuemin="1" aria-valuemax="5"
                                aria-valuenow="1">
                                <div class="sb-progress-fill" data-progress-fill style="width:20%"></div>
                            </div>
                        </div>

                        <ol class="sb-step-dots" aria-label="Reservation steps">
                            <li data-step-dot class="is-active"><span>1</span><b>Dates</b></li>
                            <li data-step-dot><span>2</span><b>Gowns &amp; sizing</b></li>
                            <li data-step-dot><span>3</span><b>Agreement &amp; ID</b></li>
                            <li data-step-dot><span>4</span><b>Payment</b></li>
                            <li data-step-dot><span>5</span><b>Confirmation</b></li>
                        </ol>

                        <div class="sb-reserve-card">

                            {{-- STEP 1 · Customer & dates --}}
                            <section class="sb-flow-step"><span class="sb-kicker">STEP 1</span>
                                <h2>Customer &amp; dates</h2>
                                <label>Booked by<input value="{{ auth()->user()->name }} · {{ auth()->user()->email }}"
                                        disabled></label>
                                <label>Contact number<input type="tel" name="contact_number" required
                                        value="{{ old('contact_number') }}" inputmode="tel" autocomplete="tel"
                                        pattern="09[0-9]{9}" maxlength="11" title="Format: 09XXXXXXXXX (11 digits)."
                                        placeholder="Format: 09XXXXXXXXX">@error('contact_number')<small>{{ $message }}</small>@enderror</label>
                                <div class="sb-form-row">
                                    <label>Pickup date<input type="date" name="pickup_date" required
                                            min="{{ today()->format('Y-m-d') }}" value="{{ old('pickup_date') }}"
                                            data-pickup>@error('pickup_date')<small>{{ $message }}</small>@enderror</label>
                                    <label>Return date<input type="date" name="return_date" required
                                            min="{{ today()->format('Y-m-d') }}" value="{{ old('return_date') }}"
                                            data-return>@error('return_date')<small>{{ $message }}</small>@enderror</label>
                                </div>
                                <div class="sb-availability" data-availability data-state="idle" aria-live="polite">
                                    <span class="sb-availability-icon" data-availability-icon>…</span>
                                    <div><b data-availability-title>Checking availability</b>
                                        <p data-availability-text>Pick your pickup and return dates to confirm every
                                            gown in your reservation is free.</p>
                                    </div>
                                </div>

                                <p class="sb-form-intro">All {{ $gownCount }} gown{{ $gownCount === 1 ? '' : 's' }}
                                    share these dates. Rental is up to {{ $maxRentalDays }} days, followed by a
                                    {{ \App\Http\Controllers\Customer\DashboardController::CLEANING_BUFFER_DAYS }}-day cleaning buffer. Availability is
                                    subject to staff approval. Late returns accrue
                                    ₱{{ number_format($lateFeePerDay, 2) }} per day.
                                </p>
                            </section>

                            {{-- STEP 2 · Gowns & sizing --}}
                            <section class="sb-flow-step"><span class="sb-kicker">STEP 2</span>
                                <h2>Gowns &amp; sizing</h2>
                                <div class="sb-selected-gowns">
                                    @foreach($gowns as $gown)
                                        <div class="sb-selected-gown" data-gown-availability="{{ $gown->id }}"
                                            data-state="idle"><b>{{ $gown->name }}</b><small>{{ $gown->gown_code }} ·
                                                {{ $gown->size ?? 'Various sizes' }}</small><strong>₱{{ number_format((float) $gown->rental_price, 2) }}</strong>
                                            <em data-gown-flag></em>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="sb-form-intro">Need another gown in the same reservation? <a
                                        href="{{ route('customer.catalog') }}">Add more from the collection</a> — your
                                    dates are kept.</p>
                                <p class="sb-form-intro">Optional measurements in centimeters can help staff prepare
                                    the fit in advance.</p>
                                <div class="sb-form-row">
                                    <label>Bust (cm) (optional)<input name="bust" type="number" min="0" max="300"
                                            step="0.1" value="{{ old('bust') }}" data-measure="bust"></label>
                                    <label>Waist (cm) (optional)<input name="waist" type="number" min="0" max="300"
                                            step="0.1" value="{{ old('waist') }}" data-measure="waist"></label>
                                </div>
                                <label>Hips (cm) (optional)<input name="hips" type="number" min="0" max="300" step="0.1"
                                        value="{{ old('hips') }}" data-measure="hips"></label>

                                <div class="sb-size-recommendation" data-size-rec data-state="idle">
                                    <span class="sb-availability-icon">?</span>
                                    <div><b data-size-title>Recommended size</b>
                                        <p data-size-text>Enter your bust, waist and hips to get a size recommendation.
                                        </p>
                                    </div>
                                </div>

                                <label>Notes for the stylist (optional)<textarea name="notes" rows="2"
                                        placeholder="Shoes, hairstyle, or fit preferences...">{{ old('notes') }}</textarea></label>
                            </section>

                            @include('components.reservation-agreement-step', [
                                'gownCount' => $gownCount,
                                'maxRentalDays' => $maxRentalDays,
                                'lateFeePerDay' => $lateFeePerDay,
                            ])

                            {{-- STEP 4 · Payment --}}
                            <section class="sb-flow-step"><span class="sb-kicker">STEP 4</span>
                                <h2>Payment</h2>
                                <div class="sb-payment-breakdown" data-payment-summary>
                                    <h3>Payment summary</h3>
                                    @foreach($gowns as $gown)
                                        <div><span>Rental fee ·
                                                {{ $gown->name }}</span><b>₱{{ number_format((float) $gown->rental_price, 2) }}</b>
                                        </div>
                                    @endforeach
                                    <div><span>Total rental fee</span><b>₱{{ number_format($rentalTotal, 2) }}</b></div>
                                    <div><span data-payment-summary-label>Full payment</span><b
                                            data-payment-summary-amount>₱{{ number_format($rentalTotal, 2) }}</b>
                                    </div>
                                    <div><span>Remaining balance</span><b data-payment-summary-balance>₱0.00</b></div>
                                </div>

                                <div class="sb-method-options sb-payment-choice-options" role="radiogroup"
                                    aria-label="Payment amount">
                                    <label class="sb-method-option"><input type="radio" name="payment_option"
                                            value="full" required data-payment-option @checked(old('payment_option', 'full') === 'full')><span><b>Full
                                                payment</b><small>₱{{ number_format($rentalTotal, 2) }}</small></span></label>
                                    <label class="sb-method-option"><input type="radio" name="payment_option"
                                            value="downpayment" required data-payment-option
                                            @checked(old('payment_option') === 'downpayment')><span><b>Down
                                                payment</b><small>More than ₱500.00</small></span></label>
                                </div>
                                @error('payment_option')<small class="sb-check-error">{{ $message }}</small>@enderror

                                <input type="hidden" name="payment_method" value="cash">
                                <p class="sb-payment-method-note">Payment method <b>Cash</b></p>
                                <label>Amount to pay now (PHP)<input type="number" name="payment_amount" min="0.01"
                                        max="{{ $rentalTotal }}" step="0.01"
                                        value="{{ old('payment_amount', $rentalTotal) }}" required
                                        data-payment-amount>@error('payment_amount')<small>{{ $message }}</small>@enderror<small
                                        data-payment-hint>Full payment equals the total rental fee for all gowns. Down
                                        payments must be more than ₱500.00 and less than the total.</small></label>
                                <p class="sb-form-intro">Remaining balance due at pickup: <b
                                        data-balance-remaining>₱0.00</b></p>
                            </section>

                            {{-- STEP 5 · Confirmation --}}
                            <section class="sb-flow-step"><span class="sb-kicker">STEP 5</span>
                                <h2>Confirmation</h2>
                                <p class="sb-form-intro">Check everything below. Nothing is submitted until you press
                                    the final button.</p>
                                <div class="sb-confirm-list">
                                    <section class="sb-confirm-group">
                                        <h3>Reservation · {{ $gownCount }} gown{{ $gownCount === 1 ? '' : 's' }}</h3>
                                        @foreach($gowns as $gown)
                                            <div><span>Gown</span><b>{{ $gown->name }} · {{ $gown->gown_code }}</b></div>
                                        @endforeach
                                        <div><span>Pickup</span><b data-confirm="pickup_date">—</b></div>
                                        <div><span>Return</span><b data-confirm="return_date">—</b></div>
                                    </section>
                                    <section class="sb-confirm-group">
                                        <h3>Payment</h3>
                                        <div><span>Total rental</span><b>₱{{ number_format($rentalTotal, 2) }}</b></div>
                                        <div><span>Payment</span><b data-confirm="payment_option">—</b></div>
                                        <div><span>Amount to pay now</span><b data-confirm="payment_amount">—</b></div>
                                    </section>
                                    <section class="sb-confirm-group">
                                        <h3>Requirements</h3>
                                        <div><span>Pickup requirement</span><b>Bring one valid government-issued ID</b>
                                        </div>
                                        <div><span>Agreement</span><b>Accepted upon submission</b></div>
                                    </section>
                                </div>
                                <p class="sb-confirm-status"><span
                                        class="sb-status-dot is-pending"></span><span><b>Pending approval after
                                            submission</b><br>Staff will verify your agreement and payment before
                                        confirming the reservation.</span></p>
                            </section>

                            <div class="sb-step-controls">
                                <button type="button" class="sb-outline-btn sb-back-btn" data-step-back>Back</button>
                                <span data-step-count>Step 1 of 5</span>
                                <button type="button" class="sb-btn" data-step-next>Continue</button>
                                <button class="sb-btn sb-btn-confirm" type="submit" data-step-submit hidden>Submit
                                    Reservation Request</button>
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
            const dots = [...form.querySelectorAll('[data-step-dot]')];
            const fill = form.querySelector('[data-progress-fill]');
            const track = form.querySelector('.sb-progress-track');
            const labels = ['Customer & dates', 'Gowns & sizing', 'Agreement', 'Payment', 'Confirmation'];

            const errors = @json(array_keys($errors->getMessages()));
            let current = steps.findIndex(step => errors.some(name => step.querySelector(`[name="${name}"]`)));
            if (current < 0) current = 0;

            const peso = n => '\u20b1' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const formatDate = iso => iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '\u2014';

            /* ---- Step 1: live availability so the customer cannot continue on blocked dates ---- */
            const pickup = form.querySelector('[data-pickup]');
            const ret = form.querySelector('[data-return]');
            const contactInput = form.querySelector('[name="contact_number"]');
            const availabilityBox = form.querySelector('[data-availability]');
            const stepError = form.querySelector('[data-step-error]');
            const maxRentalDays = @json($maxRentalDays);
            const availabilityUrl = @json(route('customer.reserve.availability', $gowns->first(), false));
            const selectedGownIds = @json($gowns->pluck('id')->values());
            let datesVerified = false;

            const normalizeContactNumber = () => {
                contactInput.value = contactInput.value.replace(/\D/g, '');
            };
            contactInput.addEventListener('blur', normalizeContactNumber);
            contactInput.addEventListener('input', () => contactInput.setCustomValidity(''));
            contactInput.addEventListener('invalid', () => contactInput.setCustomValidity('Format: 09XXXXXXXXX (11 digits).'));

            const setAvailability = (state, title, text) => {
                availabilityBox.dataset.state = state;
                form.querySelector('[data-availability-icon]').textContent = state === 'ok' ? '\u2713' : (state === 'error' ? '!' : '\u2026');
                form.querySelector('[data-availability-title]').textContent = title;
                form.querySelector('[data-availability-text]').textContent = text;
            };

            /* Mark each gown card as free or blocked for the chosen dates. */
            const setGownAvailability = (blocked) => {
                form.querySelectorAll('[data-gown-availability]').forEach(card => {
                    const block = blocked[card.dataset.gownAvailability] || null;
                    card.dataset.state = block ? 'error' : 'ok';
                    card.querySelector('[data-gown-flag]').textContent = block
                        ? 'Busy ' + formatDate(block.from) + ' \u2013 ' + formatDate(block.through)
                        : '';
                });
            };

            const clearGownAvailability = () => {
                form.querySelectorAll('[data-gown-availability]').forEach(card => {
                    card.dataset.state = 'idle';
                    card.querySelector('[data-gown-flag]').textContent = '';
                });
            };

            const checkAvailability = async () => {
                if (!pickup.value || !ret.value) {
                    datesVerified = false;
                    clearGownAvailability();
                    setAvailability('idle', 'Checking availability', 'Pick your pickup and return dates to confirm every gown in your reservation is free.');
                    return;
                }
                datesVerified = false;
                setAvailability('busy', 'Checking availability', 'Verifying every gown is free for your dates\u2026');
                try {
                    const query = new URLSearchParams({ pickup_date: pickup.value, return_date: ret.value });
                    selectedGownIds.forEach(id => query.append('gown_ids[]', String(id)));
                    const response = await fetch(availabilityUrl + '?' + query, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('bad response');
                    const result = await response.json();
                    setGownAvailability(result.conflicts || {});
                    if (result.available) {
                        datesVerified = true;
                        setAvailability('ok', 'All gowns are available', result.summary + ' · ' + result.rental_days + ' day rental');
                    } else {
                        setAvailability('busy', 'Not available for these dates', result.reason);
                    }
                } catch (error) {
                    clearGownAvailability();
                    setAvailability('error', 'Could not verify availability', 'Please try again, or contact the boutique to book these gowns.');
                }
            };

            const applyReturnWindow = () => {
                if (!pickup.value) return;
                const parts = pickup.value.split('-').map(Number);
                const max = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2] + maxRentalDays - 1)).toISOString().slice(0, 10);
                ret.min = pickup.value;
                ret.max = max;

                // Auto-set the return date to the last rental day; the customer can
                // still shorten it to any date within the window.
                if (!ret.value || ret.value < pickup.value || ret.value > max) {
                    ret.value = max;
                }
            };

            pickup.addEventListener('change', () => { applyReturnWindow(); checkAvailability(); });
            ret.addEventListener('change', () => {
                if (pickup.value && ret.value && ret.value < pickup.value) ret.value = pickup.value;
                checkAvailability();
            });
            applyReturnWindow();
            if (pickup.value && ret.value) checkAvailability();

            /* ---- Step 2: size recommendation ---- */
            const recBox = form.querySelector('[data-size-rec]');
            const recTitle = recBox.querySelector('[data-size-title]');
            const recText = recBox.querySelector('[data-size-text]');

            const recommend = () => {
                const entered = ['bust', 'waist', 'hips']
                    .map(key => [key, parseFloat(form.querySelector('[data-measure="' + key + '"]').value)])
                    .filter(entry => !isNaN(entry[1]));
                if (!entered.length) {
                    recBox.dataset.state = 'idle';
                    recTitle.textContent = 'Recommended size';
                    recText.textContent = 'Enter your bust, waist and hips to get a size recommendation.';
                    return;
                }
                recBox.dataset.state = 'ok';
                recTitle.textContent = 'Recorded measurements';
                recText.textContent = 'Based on ' + entered.map(entry => entry[0] + ' ' + entry[1] + ' cm').join(', ')
                    + '. Our stylists confirm the fit of each gown at pickup and can pin them for you.';
            };
            form.querySelectorAll('[data-measure]').forEach(input => input.addEventListener('input', recommend));
            recommend();

            /* ---- Step 4: payment amount behaviour ---- */
            const amountInput = form.querySelector('[data-payment-amount]');
            const paymentOptionInputs = [...form.querySelectorAll('[data-payment-option]')];
            const total = {{ (float) $rentalTotal }};

            const updateBalance = () => {
                const paid = parseFloat(amountInput.value) || 0;
                const balance = Math.max(0, total - paid);
                form.querySelector('[data-balance-remaining]').textContent = peso(balance);
                form.querySelector('[data-payment-summary-label]').textContent = paymentOptionInputs.find(input => input.checked)?.value === 'full' ? 'Full payment' : 'Down payment';
                form.querySelector('[data-payment-summary-amount]').textContent = peso(paid);
                form.querySelector('[data-payment-summary-balance]').textContent = peso(balance);
            };

            const applyPaymentOption = () => {
                const isFull = paymentOptionInputs.find(input => input.checked)?.value === 'full';
                amountInput.readOnly = isFull;
                amountInput.min = isFull ? total : 500.01;
                if (isFull) amountInput.value = total.toFixed(2);
                else if ((parseFloat(amountInput.value) || 0) >= total) amountInput.value = '';
                updateBalance();
            };

            amountInput.addEventListener('input', updateBalance);
            paymentOptionInputs.forEach(input => input.addEventListener('change', applyPaymentOption));
            applyPaymentOption();

            /* ---- Step 5: live confirmation summary ---- */
            const confirmField = name => form.querySelector('[data-confirm="' + name + '"]');
            const refreshConfirm = () => {
                confirmField('pickup_date').textContent = formatDate(pickup.value);
                confirmField('return_date').textContent = formatDate(ret.value);
                confirmField('payment_option').textContent = paymentOptionInputs.find(input => input.checked)?.value === 'full' ? 'Full payment' : 'Down payment';
                confirmField('payment_amount').textContent = peso(amountInput.value);
            };
            [pickup, ret, amountInput].forEach(input => input.addEventListener('change', refreshConfirm));
            paymentOptionInputs.forEach(input => input.addEventListener('change', refreshConfirm));
            refreshConfirm();

            /* ---- Wizard shell ---- */
            const scrollTop = () => form.querySelector('.sb-progress-head').scrollIntoView({ behavior: 'smooth', block: 'start' });

            const show = () => {
                stepError.hidden = true;
                steps.forEach((step, index) => step.hidden = index !== current);
                dots.forEach((dot, index) => {
                    dot.classList.toggle('is-active', index === current);
                    dot.classList.toggle('is-complete', index < current);
                    // Completed steps read as a check, not a dark filled circle.
                    dot.querySelector('span').innerHTML = index < current ? '\u2713' : String(index + 1);
                });
                const percent = Math.round(((current + 1) / steps.length) * 100);
                fill.style.width = percent + '%';
                track.setAttribute('aria-valuenow', current + 1);
                form.querySelector('[data-progress-percent]').textContent = percent + '% complete';
                form.querySelector('[data-progress-label]').textContent = 'Step ' + (current + 1) + ' of ' + steps.length + ' \u00b7 ' + labels[current];
                form.querySelector('[data-step-count]').textContent = 'Step ' + (current + 1) + ' of ' + steps.length;
                form.querySelector('[data-step-back]').disabled = current === 0;
                form.querySelector('[data-step-next]').hidden = current === steps.length - 1;
                form.querySelector('[data-step-submit]').hidden = current !== steps.length - 1;
            };

            const stepIsValid = index => {
                if (index === 0) normalizeContactNumber();
                const invalid = [...steps[index].querySelectorAll('input, select, textarea')]
                    .find(input => !input.disabled && !input.checkValidity());
                if (invalid) {
                    stepError.textContent = 'Reservation validation (RSV-001): Complete the required fields on this step before continuing.';
                    stepError.hidden = false;
                    invalid.reportValidity();
                    invalid.focus();
                    return false;
                }
                if (index === 0 && !datesVerified) {
                    stepError.textContent = 'Availability check (RSV-002): Wait for the date check to finish before continuing.';
                    stepError.hidden = false;
                    if (pickup.value && ret.value) checkAvailability();
                    else pickup.focus();
                    return false;
                }
                return true;
            };

            form.querySelector('[data-step-back]').addEventListener('click', () => { if (current > 0) { current--; show(); scrollTop(); } });
            form.querySelector('[data-step-next]').addEventListener('click', () => {
                if (!stepIsValid(current)) return;
                if (current < steps.length - 1) { current++; show(); scrollTop(); }
            });
            form.addEventListener('submit', event => { if (!stepIsValid(current)) event.preventDefault(); });

            show();
        })();
    </script>
</x-app-layout>
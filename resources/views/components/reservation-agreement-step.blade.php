<section class="sb-flow-step">
    <span class="sb-kicker">STEP 3</span>
    <h2>Rental agreement &amp; ID custody</h2>
    <p class="sb-form-intro">This agreement covers all {{ $gownCount }}
        gown{{ $gownCount === 1 ? '' : 's' }} in this reservation. Upload a clear photo of the renter's valid
        government-issued ID. The renter must also bring the physical ID to pickup for secure custody.</p>

    <label class="sb-agreement-id-upload">Live photo of valid physical government ID
        <input type="file" name="government_id" accept="image/jpeg,image/png,image/webp"
            capture="environment" required>
        <small class="sb-field-hint">JPG, PNG or WEBP, up to 4 MB. Stored privately and only accessible to authorized staff.</small>
    </label>
    @error('government_id')<small class="sb-check-error">{{ $message }}</small>@enderror

    <div class="sb-agreement-key-details" aria-label="Key rental details">
        <div><span>Gowns</span><b>{{ $gownCount }} piece{{ $gownCount === 1 ? '' : 's' }}</b></div>
        <div><span>Rental period</span><b>Up to {{ $maxRentalDays }} days</b></div>
        <div><span>Pickup</span><b>In person</b></div>
        <div><span>ID required</span><b>Valid government-issued ID</b></div>
        <div><span>Late fee</span><b>₱{{ number_format($lateFeePerDay, 2) }} per day</b></div>
    </div>

    <details class="sb-agreement">
        <summary><b>View full rental terms</b><span aria-hidden="true">VIEW</span></summary>
        <ol>
            <li><b>Rental duration.</b> Gowns may be rented for up to {{ $maxRentalDays }} days from pickup and must
                be returned by the agreed return date.</li>
            <li><b>ID custody.</b> The renter must present the same valid physical government-issued ID at pickup.
                The shop securely holds it and returns it after all gowns are returned.</li>
            <li><b>Late returns.</b> A late fee of ₱{{ number_format($lateFeePerDay, 2) }} accrues for every late day.</li>
            <li><b>Condition and damage.</b> Staff inspect gowns at return. The renter is responsible for damage or
                loss under the rental terms.</li>
            <li><b>Cancellation.</b> Reservation requests may be cancelled before staff approval. A verified down
                payment becomes non-refundable after confirmation.</li>
            <li><b>Availability.</b> The request is subject to staff approval and a final availability check.</li>
        </ol>
    </details>

    <div class="sb-agreement-checks">
        <label class="sb-agreement-check">
            <input type="checkbox" name="agreement_accepted" value="1" required @checked(old('agreement_accepted'))>
            <span>I have read and agree to the rental terms and ID custody requirements for this reservation.</span>
        </label>
        @error('agreement_accepted')<small class="sb-check-error">{{ $message }}</small>@enderror
    </div>
</section>

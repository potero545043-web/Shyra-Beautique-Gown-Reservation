<details id="record-return-form" class="sb-row-actions sb-record-return-actions" @if($expanded ?? false) open @endif>
    <summary class="sb-small-btn sb-record-return-trigger">
        {{ ($expanded ?? false) ? 'Return inspection' : 'Record Return' }}
    </summary>
    <form method="POST" action="{{ route($base . '.rentals.return', $reservation) }}" class="sb-row-action-form">
        <button type="button" class="sb-row-actions-close" aria-label="Close return form">×</button>
        @csrf
        <div class="sb-return-inspection-context">
            <h3>Confirm gown return</h3>
            <p>The customer brings the gown to the shop. Record the return here after the employee has physically inspected it.</p>
            <dl>
                <div><dt>Customer</dt><dd>{{ $reservation->customer?->full_name ?? 'Guest' }}</dd></div>
                <div><dt>Rental number</dt><dd>{{ $reservation->reservation_code }}</dd></div>
                <div>
                    <dt>Gowns</dt>
                    <dd class="sb-return-gown-list">
                        @forelse($reservation->items->unique('gown_id') as $item)
                            @if($item->gown)
                                <span class="sb-return-gown-chip">
                                    <img src="{{ $item->gown->image_url }}" alt="{{ $item->gown->name }}" loading="lazy">
                                    <span>{{ $item->gown->name }}</span>
                                </span>
                            @endif
                        @empty
                            <span>Gown details unavailable</span>
                        @endforelse
                    </dd>
                </div>
                <div><dt>Due date</dt><dd>{{ $reservation->return_date?->format('M d, Y') ?? '—' }}</dd></div>
            </dl>
        </div>
        <div class="sb-return-form-fields">
            <label>Actual return date
                <input type="date" name="actual_return_date"
                    min="{{ ($reservation->gownRelease?->release_date ?? $reservation->pickup_date)?->toDateString() }}"
                    max="{{ today()->toDateString() }}"
                    value="{{ old('actual_return_date', today()->toDateString()) }}" required>
            </label>
            <label>Condition on return
                <select name="condition_after" required>
                    <option value="excellent" @selected(old('condition_after') === 'excellent')>Excellent</option>
                    <option value="good" @selected(old('condition_after', 'good') === 'good')>Good</option>
                    <option value="repair" @selected(old('condition_after') === 'repair')>Repair</option>
                    <option value="damaged" @selected(old('condition_after') === 'damaged')>Damaged</option>
                </select>
            </label>
            <label class="sb-return-repair-field">Repair estimate / damage fee
                <input type="number" min="0" step="0.01" name="repair_cost" value="{{ old('repair_cost') }}" placeholder="₱0.00">
            </label>
            @error('actual_return_date')
                <small class="sb-field-error">{{ $message }}</small>
            @enderror
        </div>
        <label class="sb-return-notes-field">Inspection notes
            <input name="notes" value="{{ old('notes') }}" placeholder="Stains, tears, missing accessories, or other observations">
        </label>
        <button class="sb-small-btn sb-return-submit" type="submit">Confirm and record return</button>
    </form>
</details>

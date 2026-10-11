<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · ACCESSORIES</span>

                    <h1>Add an <em>accessory.</em></h1>

                    <p>Track each styling item and the gowns it accompanies.</p>
                </div>

                <a class="sb-inventory-secondary sb-back-btn" href="{{ route('owner.accessories.index') }}">
                    Back to accessories
                </a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel sb-inventory-form-card">

                <form method="POST" action="{{ route('owner.accessories.store') }}" enctype="multipart/form-data">

                    @csrf

                    <h2>Accessory details</h2>

                    <p>Examples include shawls, belts, gloves, tiaras, and garment bags.</p>

                    <label>Item name
                        <input name="name" value="{{ old('name') }}" maxlength="255" required>
                    </label>

                    <label>Description
                        <textarea name="description" rows="3">{{ old('description') }}</textarea>
                    </label>

                    <label>Accessory image
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
                        <img id="accessory-preview" class="sb-image-preview" alt="Image preview" hidden>
                        <small class="sb-field-hint">JPG, PNG or WEBP, up to 2 MB.</small>
                    </label>

                    <div class="sb-form-row">
                        <label>Quantity in stock
                            <input type="number" name="quantity" min="0" value="{{ old('quantity', 1) }}" required>
                        </label>

                        <label>Replacement cost (PHP)
                            <input type="number" name="replacement_cost" min="0" step="0.01"
                                value="{{ old('replacement_cost', 0) }}">
                        </label>
                    </div>

                    <label>Status
                        <select name="status" required>
                            @foreach(['available', 'unavailable', 'damaged', 'retired'] as $status)
                                <option value="{{ $status }}" @selected(old('status', 'available') === $status)>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="sb-inventory-form-actions">
                        <a class="sb-inventory-secondary" href="{{ route('owner.accessories.index') }}">Cancel</a>

                        <button class="sb-inventory-primary" type="submit">Create accessory</button>
                    </div>

                </form>

            </section>

        </div>
    </div>

</x-app-layout>
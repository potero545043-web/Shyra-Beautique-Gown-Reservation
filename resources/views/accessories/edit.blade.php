<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · ACCESSORIES</span>

                    <h1>Edit <em>{{ $accessory->name }}.</em></h1>

                    <p>Update stock details, condition, and replacement value.</p>
                </div>

                <a class="sb-inventory-secondary sb-back-btn" href="{{ route('owner.accessories.index') }}">
                    Back to accessories
                </a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel sb-inventory-form-card">

                <form method="POST" action="{{ route('owner.accessories.update', $accessory) }}"
                    enctype="multipart/form-data">

                    @csrf
                    @method('PUT')

                    <h2>Accessory details</h2>

                    <label>Item name
                        <input name="name" value="{{ old('name', $accessory->name) }}" maxlength="255" required>
                    </label>

                    <label>Description
                        <textarea name="description"
                            rows="3">{{ old('description', $accessory->description) }}</textarea>
                    </label>

                    <label>Accessory image
                        <img id="accessory-preview" class="sb-image-preview" alt="{{ $accessory->name }}"
                            @if($accessory->image_url) src="{{ $accessory->image_url }}" @else hidden
                            @endif>

                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp">

                        <small class="sb-field-hint">
                            {{ $accessory->image ? 'Choose a file only if you want to replace the current image.' : 'JPG, PNG or WEBP, up to 2 MB.' }}
                        </small>
                    </label>

                    <div class="sb-form-row">
                        <label>Quantity in stock
                            <input type="number" name="quantity" min="0"
                                value="{{ old('quantity', $accessory->quantity) }}" required>
                        </label>

                        <label>Replacement cost (PHP)
                            <input type="number" name="replacement_cost" min="0" step="0.01"
                                value="{{ old('replacement_cost', $accessory->replacement_cost) }}">
                        </label>
                    </div>

                    <label>Status
                        <select name="status" required>
                            @foreach(['available', 'unavailable', 'damaged', 'retired'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $accessory->status) === $status)>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="sb-inventory-form-actions">
                        <a class="sb-inventory-secondary" href="{{ route('owner.accessories.index') }}">Cancel</a>

                        <button class="sb-inventory-primary" type="submit">Save accessory</button>
                    </div>

                </form>

            </section>

        </div>
    </div>

</x-app-layout>
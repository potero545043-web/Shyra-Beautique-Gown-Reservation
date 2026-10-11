<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · CATEGORIES</span>

                    <h1>Add a <em>category.</em></h1>

                    <p>Use categories to group similar gown styles for staff and customers.</p>
                </div>

                <a class="sb-inventory-secondary sb-back-btn" href="{{ route('owner.categories.index') }}">
                    Back to categories
                </a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel sb-inventory-form-card">

                <form method="POST" action="{{ route('owner.categories.store') }}">

                    @csrf

                    <h2>Category details</h2>

                    <p>Examples: Bridal, Evening, Prom, or Formal.</p>

                    <label>Category name
                        <input name="name" value="{{ old('name') }}" maxlength="255" placeholder="Evening gowns"
                            required>
                    </label>

                    <label>Description
                        <textarea name="description" rows="4"
                            placeholder="What kinds of gowns belong in this collection?">{{ old('description') }}</textarea>
                    </label>

                    <div class="sb-inventory-form-actions">
                        <a class="sb-inventory-secondary" href="{{ route('owner.categories.index') }}">Cancel</a>

                        <button class="sb-inventory-primary" type="submit">Create category</button>
                    </div>

                </form>

            </section>

        </div>
    </div>

</x-app-layout>
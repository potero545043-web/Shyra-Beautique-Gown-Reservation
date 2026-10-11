<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · ORGANIZATION</span>

                    <h1>Gown <em>categories.</em></h1>

                    <p>Group gowns into clear collections so staff and customers can browse them easily.</p>
                </div>
            </div>


            {{-- SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="sb-success">
                    {{ session('success') }}
                </div>
            @endif


            {{-- CATEGORIES PANEL --}}
            <section class="sb-panel sb-category-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER: title left, Add button right --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">CATEGORY LIST</span>

                            <h2>Categories</h2>

                            <p>
                                {{ $categories->total() }}
                                {{ \Illuminate\Support\Str::plural('category', $categories->total()) }}
                            </p>
                        </div>

                        <div class="sb-inventory-toolbar-actions">
                            <button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                                onclick="document.getElementById('category-create-dialog').showModal()">
                                <span aria-hidden="true">＋</span>
                                Add category
                            </button>
                        </div>

                    </div>


                    {{-- TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-category-table">

                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Gowns</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($categories as $category)

                                    <tr class="sb-clickable-row" data-href="{{ route('owner.categories.show', $category) }}"
                                        tabindex="0" role="link" aria-label="View category {{ $category->name }}">

                                        <td data-label="Category">
                                            <div class="sb-inventory-name">
                                                <span>
                                                    <b>{{ $category->name }}</b>
                                                    <small>Collection category</small>
                                                </span>
                                            </div>
                                        </td>


                                        {{-- DESCRIPTION --}}
                                        <td data-label="Description" class="sb-category-desc">
                                            <span>{{ $category->description ?: 'No description added' }}</span>
                                        </td>


                                        {{-- GOWNS --}}
                                        <td data-label="Gowns">
                                            <span class="sb-booking-count">{{ $category->gowns_count }}</span>
                                        </td>


                                        {{-- STATUS --}}
                                        <td data-label="Status">
                                            <span
                                                class="sb-inventory-status sb-gown-status {{ $category->is_active ? 'is-available' : 'is-disabled' }}">
                                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td data-label="Actions">

                                            <div class="sb-inventory-actions">

                                                <a href="{{ route('owner.categories.edit', $category) }}"
                                                    class="sb-action-edit">Edit</a>

                                                <form method="POST"
                                                    action="{{ route('owner.categories.toggle-status', $category) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="{{ $category->is_active ? 'sb-action-danger' : 'sb-action-success' }}"
                                                        type="submit">
                                                        {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5">
                                            <div class="sb-inventory-empty">
                                                <b>No categories yet</b>
                                                <p>Create categories to make the collection easier to browse.</p>
                                            </div>
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    @include('components.table-pagination', ['paginator' => $categories, 'itemLabel' => 'categories'])

                </div>

            </section>


            {{-- ADD CATEGORY DRAWER --}}
            <dialog class="sb-side-drawer sb-inventory-modal" id="category-create-dialog" aria-labelledby="category-create-title"
                onclick="if (event.target === this) this.close()">

                <div class="sb-side-drawer-head">

                    <div>
                        <span class="sb-kicker">INVENTORY · ORGANIZATION</span>

                        <h2 id="category-create-title">Add a category</h2>

                        <p>Create a collection group for the gown catalog.</p>
                    </div>

                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('category-create-dialog').close()">
                        &times;
                    </button>

                </div>


                <form method="POST" action="{{ route('owner.categories.store') }}" class="sb-side-drawer-form">

                    @csrf

                    <input type="hidden" name="_inventory_drawer" value="category">

                    @if($errors->any() && old('_inventory_drawer') === 'category')
                        <div class="sb-form-errors">{{ $errors->first() }}</div>
                    @endif

                    <label>Category name
                        <input name="name" value="{{ old('name') }}" maxlength="255" placeholder="Evening gowns"
                            required>
                        <x-input-error :messages="$errors->get('name')" />
                    </label>

                    <label>Description
                        <textarea name="description" rows="4"
                            placeholder="What kinds of gowns belong in this collection?">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" />
                    </label>

                    <div class="sb-side-drawer-actions">
                        <button class="sb-side-drawer-cancel" type="button"
                            onclick="document.getElementById('category-create-dialog').close()">
                            Cancel
                        </button>

                        <button class="sb-inventory-primary" type="submit">Create category</button>
                    </div>

                </form>

            </dialog>


            {{-- REOPEN DRAWER AFTER VALIDATION ERROR --}}
            @if(old('_inventory_drawer') === 'category')
                <script>
                    document.getElementById('category-create-dialog').showModal();
                </script>
            @endif

        </div>
    </div>

</x-app-layout>
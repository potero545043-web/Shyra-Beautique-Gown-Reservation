<x-app-layout>
    <div class="sb-page sb-inventory-page sb-category-detail-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · CATEGORY</span>
                    <h1>{{ $category->name }}</h1>
                    <p>{{ $category->description ?: 'No description has been added for this category.' }}</p>
                </div>
                <div class="sb-inventory-actions">
                    <a href="{{ route('owner.categories.edit', $category) }}">Edit category</a>
                    <a class="sb-back-btn" href="{{ route('owner.categories.index') }}">Back to categories</a>
                </div>
            </div>

            <section class="sb-panel">
                <div class="sb-inventory-content">
                    <div class="sb-panel-head">
                        <div>
                            <h2>Gowns in this category</h2>
                            <p>{{ $gowns->total() }} pieces currently assigned</p>
                        </div>
                    </div>

                    <div class="sb-inventory-table-wrap">
                        <table class="sb-inventory-table sb-gown-table sb-category-detail-table">
                            <thead>
                                <tr>
                                    <th>Gown</th>
                                    <th>Size</th>
                                    <th>Rental price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gowns as $gown)
                                    <tr class="sb-gown-row-link" data-href="{{ route('owner.gowns.show', $gown) }}"
                                        tabindex="0" role="link" aria-label="View details for {{ $gown->name }}">
                                        <td data-label="Gown">
                                            <div class="sb-inventory-name">
                                                <div class="sb-inventory-thumb">
                                                    @if($gown->image)
                                                        <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}" loading="lazy">
                                                    @else
                                                        <span aria-hidden="true">✿</span>
                                                    @endif
                                                </div>
                                                <span>
                                                    <b>{{ $gown->name }}</b>
                                                    <small>{{ $gown->gown_code }} · {{ $gown->color ?: 'Color not set' }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label="Size">{{ $gown->size ?: '—' }}</td>
                                        <td data-label="Rental price">
                                            <strong class="sb-rental-price">₱{{ number_format($gown->rental_price, 2) }}</strong>
                                        </td>
                                        <td data-label="Status">
                                            <span class="sb-inventory-status sb-status-{{ $gown->status }}">
                                                {{ str_replace('_', ' ', $gown->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="sb-inventory-empty">
                                                <b>No gowns in this category</b>
                                                <p>Edit a gown to assign it to this category.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @include('components.table-pagination', ['paginator' => $gowns, 'itemLabel' => 'gowns'])
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

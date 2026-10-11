<x-app-layout>
    <div class="sb-page sb-inventory-page sb-accessory-detail-page">
        <div class="sb-wrap">
            <div class="sb-heading sb-accessory-detail-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · ACCESSORIES</span>
                    <h1>{{ $accessory->name }}</h1>
                    <p>Accessory stock record and linked gown looks.</p>
                </div>
                <div class="sb-accessory-detail-actions">
                    <a class="sb-outline-btn sb-back-btn" href="{{ route('owner.accessories.index') }}">Back to inventory</a>
                    <a class="sb-action-edit" href="{{ route('owner.accessories.edit', $accessory) }}">Edit item</a>
                </div>
            </div>

            <section class="sb-panel sb-accessory-stock-panel" aria-labelledby="accessory-stock-title">
                <div class="sb-accessory-stock-overview">
                    <div class="sb-accessory-stock-identity">
                        <div class="sb-accessory-detail-image">
                            @if($accessory->image_url)
                                <img src="{{ $accessory->image_url }}" alt="{{ $accessory->name }}">
                            @else
                                <span aria-hidden="true">✧</span>
                            @endif
                        </div>
                        <h2 id="accessory-stock-title">Stock details</h2>
                        @if($accessory->description)
                            <p>{{ $accessory->description }}</p>
                        @endif
                    </div>

                    <dl class="sb-accessory-stock-facts">
                        <div>
                            <dt>Quantity</dt>
                            <dd>{{ $accessory->quantity }}</dd>
                        </div>
                        <div>
                            <dt>Replacement value</dt>
                            <dd>₱{{ number_format($accessory->replacement_cost, 2) }}</dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd>
                                <span class="sb-inventory-status sb-gown-status {{ $accessory->status === 'available' ? 'is-available' : (in_array($accessory->status, ['damaged', 'unavailable'], true) ? 'is-damaged' : 'is-disabled') }}">
                                    {{ ucfirst($accessory->status) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="sb-panel sb-accessory-linked-panel" aria-labelledby="accessory-linked-title">
                <div class="sb-accessory-linked-heading">
                    <div>
                        <span class="sb-kicker">STYLING</span>
                        <h2 id="accessory-linked-title">Linked gowns</h2>
                    </div>
                    <span class="sb-accessory-linked-count">
                        {{ $accessory->gowns->count() }} {{ \Illuminate\Support\Str::plural('gown', $accessory->gowns->count()) }}
                    </span>
                </div>

                @forelse($accessory->gowns as $gown)
                    <a class="sb-accessory-linked-gown" href="{{ route('owner.gowns.show', $gown) }}">
                        <div>
                            <strong>{{ $gown->name }}</strong>
                            <span>{{ $gown->gown_code }} · {{ $gown->category->name ?? 'Uncategorized' }}</span>
                        </div>
                        <span class="sb-accessory-linked-quantity">Quantity {{ $gown->pivot->quantity }}</span>
                    </a>
                @empty
                    <div class="sb-accessory-linked-empty">
                        <span aria-hidden="true">✧</span>
                        <p>No gowns are currently linked to this accessory.</p>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>

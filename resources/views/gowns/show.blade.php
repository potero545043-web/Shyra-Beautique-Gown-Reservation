<x-app-layout>
    <div class="sb-page sb-inventory-page sb-gown-detail-page">
        <div class="sb-wrap">
            <div class="sb-heading sb-gown-detail-heading">
                <div class="sb-gown-detail-title">
                    <h1>{{ $gown->name }}</h1>
                    <p>{{ $gown->gown_code }} · {{ $gown->color ?: 'Color not specified' }} · Size {{ $gown->size ?: '—' }}</p>
                </div>
                <div class="sb-gown-detail-actions">
                    <a class="sb-outline-btn sb-back-btn" href="{{ route('owner.gowns.index', $gown->archived_at ? ['archived' => 1] : []) }}">Back</a>
                    @if($gown->archived_at)
                        <form class="sb-gown-detail-action" method="POST" action="{{ route('owner.gowns.restore', $gown) }}"
                            data-confirm-title="Restore" data-confirm-accent="this gown?"
                            data-confirm-message="Are you sure you want to restore this gown? It will return to active inventory."
                            data-confirm-yes="Yes, restore" data-confirm-no="Keep archived">
                            @csrf
                            @method('PATCH')
                            <button class="sb-inventory-primary" type="submit">Restore gown</button>
                        </form>
                    @else
                        @if($gown->status !== 'reserved')
                            <form class="sb-gown-detail-action" method="POST" action="{{ route('owner.gowns.archive', $gown) }}"
                                data-confirm-title="Archive" data-confirm-accent="this gown?"
                                data-confirm-message="This gown will move to your archive. You can restore it at any time."
                                data-confirm-yes="Archive gown" data-confirm-no="Keep gown">
                                @csrf
                                <button class="sb-inventory-secondary" type="submit">Archive gown</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
            <div class="sb-columns sb-inventory-detail-grid">
                <section class="sb-panel">
                    <div class="sb-inventory-detail-photo">
                        <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}">
                    </div>
                    <div class="sb-inventory-content">
                        <span class="sb-inventory-status {{ $gown->archived_at ? 'is-archived' : 'sb-status-' . $gown->status }}">{{ $gown->archived_at ? 'Archived' : ucfirst(str_replace('_',' ',$gown->status)) }}</span>
                        <h2>{{ $gown->name }}</h2>
                        <p>{{ $gown->description ?: 'No description added.' }}</p>
                    </div>
                </section>
                <div class="sb-inventory-detail-side">
                    <section class="sb-panel sb-inventory-content">
                        <h2>Rental details</h2>
                        <div class="sb-inventory-facts">
                            <div><small>RENTAL PRICE</small><b>₱{{ number_format($gown->rental_price,2) }}</b></div>
                            <div><small>CONDITION</small><b>{{ ucfirst($gown->condition) }}</b></div>
                            <div><small>PURCHASE PRICE</small><b>{{ $gown->purchase_price ? '₱'.number_format($gown->purchase_price,2) : 'Not recorded' }}</b></div>
                            <div><small>MEASUREMENTS</small><b>{{ $gown->measurements ?: 'Not recorded' }}</b></div>
                        </div>
                    </section>
                    <section class="sb-panel sb-inventory-content">
                        <h2>Included accessories</h2>
                        <p class="sb-inventory-explainer">Accessories are separate stock items assigned to this gown for staff to prepare with the look.</p>
                        @forelse($gown->accessories as $accessory)
                            <div class="sb-team-row">
                                <div class="sb-team-copy">
                                    <b>{{ $accessory->name }}</b>
                                    <small>{{ $accessory->pivot->quantity }} included · {{ $accessory->quantity }} in stock</small>
                                </div>
                                <a class="sb-text-link" href="{{ route('owner.accessories.show',$accessory) }}">Details</a>
                            </div>
                        @empty
                            <div class="sb-empty">No accessories linked. Edit this gown to add styling pieces.</div>
                        @endforelse
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    @php
        $role = auth()->user()->role;
        $gownCount = $gowns->count();
    @endphp
    <div class="sb-page sb-cart-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">IN-STORE CART</span>
                    <h1>Walk-in <em>cart.</em></h1>
                    <p>Gather every gown the customer needs, then record them as one in-store reservation with a single
                        agreement and payment.</p>
                </div>
                <a class="sb-outline-btn sb-back-btn" href="{{ route($role . '.catalog') }}">Back to collection</a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($gowns->isEmpty())
                <div class="sb-panel sb-empty sb-cart-empty">
                    <span class="sb-cart-empty-icon">
                        <svg>
                            <use href="#sb-i-cart" />
                        </svg>
                    </span>
                    <h2>The in-store cart is empty</h2>
                    <p>Open the collection and use the cart button on each gown the customer wants. Then reserve all of
                        them together in one booking.</p>
                    <a class="sb-btn" href="{{ route($role . '.catalog') }}">Open the collection <span
                            aria-hidden="true">&rarr;</span></a>
                </div>
            @else
                <div class="sb-cart-layout">
                    <div class="sb-cart-items">
                        <div class="sb-cart-items-head">
                            <h2>{{ $gownCount }} gown{{ $gownCount === 1 ? '' : 's' }} for this walk-in</h2>
                            <form method="POST" action="{{ route($role . '.cart.clear') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sb-cart-clear">Clear cart</button>
                            </form>
                        </div>

                        @foreach($gowns as $gown)
                            <article class="sb-cart-row">
                                <a class="sb-cart-row-photo" style="background-image:url('{{ $gown->image_url }}')"
                                    href="{{ route($role . '.catalog.show', $gown) }}" aria-label="{{ $gown->name }}"></a>
                                <div class="sb-cart-row-info">
                                    <span class="sb-kicker">{{ strtoupper($gown->category->name ?? 'THE COLLECTION') }}</span>
                                    <h3>{{ $gown->name }}</h3>
                                    <small>{{ $gown->gown_code }} · Size {{ $gown->size ?? 'Various' }} ·
                                        {{ $gown->color ?? 'See photos' }}</small>
                                    <span
                                        class="sb-cart-row-availability is-{{ $gown->status }}">{{ ucfirst(str_replace('_', ' ', $gown->status)) }}</span>
                                </div>
                                <div class="sb-cart-row-price">
                                    <b>₱{{ number_format((float) $gown->rental_price, 2) }}</b>
                                    <small>/ rental</small>
                                </div>
                                <form method="POST" action="{{ route($role . '.cart.remove', $gown) }}"
                                    class="sb-cart-row-remove">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Remove {{ $gown->name }}" title="Remove">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </form>
                            </article>
                        @endforeach
                    </div>

                    <aside class="sb-cart-summary">
                        <h2>Booking summary</h2>
                        <div class="sb-cart-summary-rows">
                            <div><span>Gowns</span><b>{{ $gownCount }}</b></div>
                            <div><span>Rental period</span><b>Up to 3 days</b></div>
                            <div><span>Daily late fee</span><b>₱{{ number_format($lateFeePerDay, 2) }}</b></div>
                        </div>
                        <div class="sb-cart-summary-total">
                            <span>Rental total</span><b>₱{{ number_format($rentalTotal, 2) }}</b>
                        </div>
                        <a class="sb-btn sb-cart-checkout" href="{{ route($role . '.reserve') }}">
                            Reserve {{ $gownCount }} gown{{ $gownCount === 1 ? '' : 's' }} <span
                                aria-hidden="true">&rarr;</span>
                        </a>
                        <p class="sb-cart-summary-note">Next you will capture the customer details, government ID
                            custody, the signed agreement, and the cash payment.</p>
                    </aside>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
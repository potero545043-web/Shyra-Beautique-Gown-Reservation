<x-app-layout>
    <div class="sb-page sb-customers-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>

                    <h1>Customer <em>records.</em>
                    </h1>

                    <p>Customer contact details and reservation history.</p>
                </div>

            </div>

            <form method="GET" class="sb-filterbar" data-live-filter>
                <input type="hidden" name="per_page" value="{{ $customers->perPage() }}">
                <input name="q" value="{{ request('q') }}" placeholder="Search customer name, email, or phone">
                <button class="sb-btn">Search</button>

            </form>

            <section class="sb-panel">
                <div class="sb-table-wrap">
                    <table class="sb-table">
                        <thead>
                            <tr>
                                <th>
                                    CUSTOMER</th>
                                <th>CONTACT</th>
                                <th>STATUS</th>
                                <th>RESERVATIONS</th>
                                <th>RECENT BOOKING</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($customers as $customer)
                                <tr class="sb-clickable-row"
                                    data-href="{{ route(auth()->user()->role . '.customers.show', $customer) }}"
                                    tabindex="0" role="link"
                                    aria-label="View customer {{ $customer->full_name }}">
                                    <td>
                                        <strong>{{ $customer->full_name }}</strong>
                                        <small class="sb-cell-sub">View reservations and payments</small>
                                        <small class="sb-cell-sub">{{ $customer->customer_code }}</small>
                                    </td>

                                    <td>{{ $customer->contact_number }}
                                        <small class="sb-cell-sub">{{ $customer->email }}

                                        </small>
                                    </td>

                                    <td>
                                        <span class="sb-status sb-status-{{ $customer->status }}">{{ ucfirst($customer->status) }}

                                        </span>
                                    </td>
                                    <td>{{ $customer->reservations_count }}</td>
                                    <td>{{ $customer->reservations->sortByDesc('created_at')->first()?->reservation_code ?? '—' }}

                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="5" class="sb-empty">Customer profiles will appear as guests reserve a gown.
                                    </td>
                                </tr>

                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @include('components.table-pagination', ['paginator' => $customers, 'itemLabel' => 'customers'])
        </div>
    </div>
</x-app-layout>

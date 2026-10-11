<x-app-layout>

    <div class="min-h-screen bg-[#F7EFE4] text-[#2E2A26]">
        <!-- Decorative Background -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-[#B76E79]/5"></div>
            <div class="absolute top-[45%] -left-40 w-96 h-96 rounded-full bg-[#5C1A2B]/[0.025]"></div>
        </div>


        <div class="relative max-w-7xl mx-auto px-5 py-8 lg:px-8">

            <!-- ================================================= -->
            <!-- HEADER -->
            <!-- ================================================= -->

            <div class="sb-simple-overview-hero sb-customer-hero">
                <div class="sb-customer-hero-copy">
                    <span class="sb-kicker">OWNER OVERVIEW</span>
                    <h1>Good day, <em>{{ explode(' ', auth()->user()->name)[0] }}.</em></h1>
                    <p>Here's what's happening at Shyra Beautique today.</p>
                    <div class="sb-customer-hero-actions">
                        <a href="{{ route('owner.gowns.create') }}" class="sb-btn">
                            <span aria-hidden="true">+</span> Add New Gown
                        </a>
                    </div>
                </div>
            </div>
            <!--  STATS   -->

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">

                <!-- Total Gowns -->
                <a href="{{ route('owner.gowns.index') }}"
                    class="group block rounded-2xl bg-white border border-[#DCCBB2] p-5 shadow-sm no-underline transition duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5C1A2B]">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 shrink-0 rounded-2xl bg-[#F7EFE4] flex items-center justify-center text-[#5C1A2B] transition duration-300 group-hover:scale-105">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.7"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3a1.5 1.5 0 0 0-1.5 1.5c0 .7.4 1.3 1 1.6L9 8h6l-2.5-1.9c.6-.3 1-.9 1-1.6A1.5 1.5 0 0 0 12 3Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 8 4.6 18.2a1 1 0 0 0 .9 1.4h12.9a1 1 0 0 0 .9-1.4L15 8" />
                                <path stroke-linecap="round" d="M9 8h6" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-wide uppercase text-[#8B6F61]">Total Gowns</p>
                            <h2 class="mt-1 text-2xl font-bold text-[#5C1A2B]">{{ $gowns }}</h2>
                            <p class="mt-1 text-xs text-[#A99C8F]">Total inventory</p>
                        </div>
                    </div>
                </a>

                <!-- Available -->
                <a href="{{ route('owner.gowns.index', ['status' => 'available']) }}"
                    class="group block rounded-2xl bg-white border border-[#DCCBB2] p-5 shadow-sm no-underline transition duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5C1A2B]">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 shrink-0 rounded-2xl bg-[#F2E6D5] flex items-center justify-center text-[#9A7A42] transition duration-300 group-hover:scale-105">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.7"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7 10 17l-5-5" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-wide uppercase text-[#8B6F61]">Available Now</p>
                            <h2 class="mt-1 text-2xl font-bold text-[#5C1A2B]">{{ $available }}</h2>
                            <p class="mt-1 text-xs text-[#A99C8F]">Currently available</p>
                        </div>
                    </div>
                </a>

                <!-- Pending -->
                <a href="{{ route('owner.reservations', ['status' => 'pending']) }}"
                    class="group block rounded-2xl bg-white border border-[#DCCBB2] p-5 shadow-sm no-underline transition duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5C1A2B]">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 shrink-0 rounded-2xl bg-[#ECDCC5] flex items-center justify-center text-[#B77E45] transition duration-300 group-hover:scale-105">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.7"
                                viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" />
                                <path stroke-linecap="round" d="M12 7v5l3 2" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-wide uppercase text-[#8B6F61]">Pending Reservations
                            </p>
                            <h2 class="mt-1 text-2xl font-bold text-[#5C1A2B]">{{ $pending }}</h2>
                            <p class="mt-1 text-xs text-[#A99C8F]">Need your attention</p>
                        </div>
                    </div>
                </a>

                <!-- Active Rentals -->
                <a href="{{ route('owner.rentals') }}"
                    class="group block rounded-2xl bg-white border border-[#DCCBB2] p-5 shadow-sm no-underline transition duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5C1A2B]">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 shrink-0 rounded-2xl bg-[#F7EFE4] flex items-center justify-center text-[#6D2538] transition duration-300 group-hover:scale-105">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.7"
                                viewBox="0 0 24 24">
                                <rect x="4" y="5" width="16" height="15" rx="2" />
                                <path stroke-linecap="round" d="M8 3v4M16 3v4M4 10h16" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 14.5 2 2 4-4" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-wide uppercase text-[#8B6F61]">Active Rentals</p>
                            <h2 class="mt-1 text-2xl font-bold text-[#5C1A2B]">{{ $rentals }}</h2>
                            <p class="mt-1 text-xs text-[#A99C8F]">{{ $confirmed }} confirmed bookings</p>
                        </div>
                    </div>
                </a>

                <!-- Customers -->
                <a href="{{ route('owner.customers') }}"
                    class="group block rounded-2xl bg-white border border-[#DCCBB2] p-5 shadow-sm no-underline transition duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5C1A2B]">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 shrink-0 rounded-2xl bg-[#F2E6D5] flex items-center justify-center text-[#7A2940] transition duration-300 group-hover:scale-105">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.7"
                                viewBox="0 0 24 24">
                                <circle cx="9" cy="8" r="3" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 20a6 6 0 0 1 12 0" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 4 5" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-wide uppercase text-[#8B6F61]">Customer Accounts
                            </p>
                            <h2 class="mt-1 text-2xl font-bold text-[#5C1A2B]">{{ $customers }}</h2>
                            <p class="mt-1 text-xs text-[#A99C8F]">Registered customers</p>
                        </div>
                    </div>
                </a>

                <!-- Payments -->
                <a href="{{ route('owner.payments') }}"
                    class="group block rounded-2xl bg-[#5C1A2B] p-5 shadow-lg shadow-[#5C1A2B]/10 no-underline transition duration-300 hover:-translate-y-1 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#C9A46A]">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 shrink-0 rounded-2xl bg-white/10 flex items-center justify-center text-[#E5CFA5] transition duration-300 group-hover:scale-105">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.7"
                                viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path stroke-linecap="round" d="M3 10h18" />
                                <path stroke-linecap="round" d="M7 15h3" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-wide uppercase text-white/60">Payments Received</p>
                            <h2 class="mt-1 text-2xl font-bold text-white">₱{{ number_format($revenue, 0) }}</h2>
                            <p class="mt-1 text-xs text-white/50">All recorded payments</p>
                        </div>
                    </div>
                </a>

            </div>


            <!-- ================================================= -->
            <!-- CONTENT GRID -->
            <!-- ================================================= -->

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">


                <!-- ================================================= -->
                <!-- RECENT RESERVATIONS -->
                <!-- ================================================= -->

                <section class="xl:col-span-2 bg-white rounded-2xl border border-[#EEDDE3] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#F0E4E8]">

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                            <div>

                                <div class="flex items-center gap-2">

                                    <h2 class="font-display text-xl font-semibold text-[#5C1A2B]">
                                        Recent Reservations
                                    </h2>



                                </div>

                                <p class="text-xs text-[#95878D] mt-1">
                                    Most recently created reservations
                                </p>

                            </div>

                            <a href="{{ route('owner.payments') }}"
                                class="inline-flex w-fit px-3 py-1.5 rounded-full bg-[#F8EEF2] text-[#5C1A2B] text-[10px] font-semibold uppercase tracking-wide hover:bg-[#F0E4E8] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5C1A2B]">
                                View payments →
                            </a>

                        </div>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="w-full text-sm">

                            <thead>

                                <tr class="bg-[#FCF8FA] border-b border-[#F0E4E8]">

                                    <th
                                        class="text-left px-6 py-4 text-[10px] font-semibold tracking-wider text-[#95878D] uppercase">
                                        Reservation
                                    </th>

                                    <th
                                        class="text-left px-6 py-4 text-[10px] font-semibold tracking-wider text-[#95878D] uppercase">
                                        Customer
                                    </th>

                                    <th
                                        class="text-left px-6 py-4 text-[10px] font-semibold tracking-wider text-[#95878D] uppercase">
                                        Pickup Date
                                    </th>

                                    <th
                                        class="text-left px-6 py-4 text-[10px] font-semibold tracking-wider text-[#95878D] uppercase">
                                        Status
                                    </th>

                                    <th
                                        class="text-right px-6 py-4 text-[10px] font-semibold tracking-wider text-[#95878D] uppercase">
                                        Amount
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-[#F3E9ED]">

                                @forelse($reservations as $reservation)

                                    <tr class="sb-clickable-row hover:bg-[#FCF8FA] transition"
                                        data-href="{{ route('owner.reservations.show', $reservation) }}"
                                        tabindex="0" role="link"
                                        aria-label="View reservation {{ $reservation->reservation_code }}">

                                        <td class="px-6 py-4">

                                            <span class="font-semibold text-[#5C1A2B]">
                                                {{ $reservation->reservation_code }}
                                            </span>

                                        </td>


                                        <td class="px-6 py-4 text-[#5F555A]">

                                            <span>
                                                {{ $reservation->customer->full_name ?? 'Customer' }}
                                            </span>

                                        </td>


                                        <td class="px-6 py-4 text-[#6F6662] whitespace-nowrap">

                                            <span>
                                                {{ $reservation->pickup_date?->format('M d, Y') }}
                                            </span>

                                        </td>


                                        <td class="px-6 py-4">

                                            <span
                                                class="inline-flex px-2.5 py-1 rounded-full bg-[#F8EEF2] text-[#5C1A2B] text-xs font-medium capitalize sb-dash-status sb-dash-status-{{ $reservation->status }}">

                                                {{ str_replace('_', ' ', $reservation->status) }}

                                            </span>

                                        </td>


                                        <td class="px-6 py-4 text-right font-semibold text-[#5C1A2B] whitespace-nowrap">

                                            <span>
                                                ₱{{ number_format($reservation->grand_total, 0) }}
                                            </span>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="5" class="px-6 py-12 text-center">

                                            <div class="text-3xl text-[#C9A46A] mb-3">
                                                ✦
                                            </div>

                                            <p class="font-medium text-[#5C1A2B]">
                                                No reservations yet
                                            </p>

                                            <p class="text-xs text-[#A2959A] mt-1">
                                                New reservations will appear here.
                                            </p>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- ================================================= -->
                <!-- RECENT GOWNS -->
                <!-- ================================================= -->

                <section class="bg-white rounded-2xl border border-[#EEDDE3] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#F0E4E8]">

                        <div class="flex items-center justify-between gap-3">

                            <div>

                                <div class="flex items-center gap-2">

                                    <h2 class="font-display text-xl font-semibold text-[#5C1A2B]">
                                        Recently Added
                                    </h2>



                                </div>

                                <p class="text-xs text-[#95878D] mt-1">
                                    Latest gown additions
                                </p>

                            </div>

                            <a href="{{ route('owner.gowns.index') }}"
                                class="text-xs font-semibold text-[#B76E79] hover:text-[#5C1A2B] transition">
                                View all →
                            </a>

                        </div>

                    </div>


                    <div class="p-5">

                        @forelse($recentGowns as $gown)

                            <a href="{{ route('owner.gowns.show', $gown) }}"
                                aria-label="View {{ $gown->name }}"
                                class="group flex items-center gap-4 py-4 border-b border-[#F3E9ED] last:border-0 no-underline transition hover:bg-[#FCF8FA] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5C1A2B]">

                                <!-- Gown Icon -->
                                <!-- Gown Image -->
                                <div
                                    class="w-12 h-12 shrink-0 rounded-xl overflow-hidden bg-[#F8EEF2] flex items-center justify-center text-[#B76E79] text-xl transition group-hover:scale-105">

                                    @if($gown->image)
                                        <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}"
                                            class="w-full h-full object-cover" loading="lazy">
                                    @else
                                        <span>✿</span>
                                    @endif
                                </div>


                                <!-- Info -->
                                <div class="min-w-0 flex-1">

                                    <p class="font-semibold text-sm text-[#5C1A2B] truncate">
                                        {{ $gown->name }}
                                    </p>

                                    <p class="text-xs text-[#A2959A] mt-1">
                                        {{ $gown->gown_code }}
                                        ·
                                        {{ $gown->size ?? 'All sizes' }}
                                    </p>

                                </div>


                                <!-- Price -->
                                <div class="text-right">

                                    <p class="text-sm font-semibold text-[#5C1A2B] whitespace-nowrap">
                                        ₱{{ number_format($gown->rental_price, 0) }}
                                    </p>

                                    <p class="text-[10px] text-[#A2959A] mt-1">
                                        rental
                                    </p>

                                </div>

                            </a>

                        @empty

                            <div class="py-10 text-center">

                                <div class="text-3xl text-[#C9A46A] mb-3">
                                    ✿
                                </div>

                                <p class="font-medium text-sm text-[#5C1A2B]">
                                    No gowns yet
                                </p>

                                <p class="text-xs text-[#A2959A] mt-1">
                                    Add your first gown to get started.
                                </p>

                            </div>

                        @endforelse


                        <!-- Inventory Tip -->
                        <div class="mt-5 rounded-xl bg-[#FCF8FA] border border-[#F0E4E8] p-4">

                            <div class="flex gap-3">

                                <div class="text-[#C9A46A]">
                                    ✦
                                </div>

                                <div>

                                    <p class="text-xs font-semibold text-[#5C1A2B]">
                                        Inventory reminder
                                    </p>

                                    <p class="text-xs leading-relaxed text-[#8D7E84] mt-1">
                                        Update gown availability and condition after each rental.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

            </div>

        </div>

    </div>

</x-app-layout>

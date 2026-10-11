<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · ARCHIVE</span>
                    <h1>Archived <em>gowns.</em></h1>
                    <p>Gowns removed from active inventory. Restore any piece to make it rentable again.</p>
                </div>
                <a class="sb-inventory-secondary sb-back-btn" href="{{ route('owner.gowns.index') }}">Back to collection</a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="sb-form-errors" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel">
                <div class="sb-inventory-content">

                    <div class="sb-inventory-toolbar">
                        <div>
                            <span class="sb-kicker">ARCHIVED GOWNS</span>
                            <h2>Archive</h2>
                            <p>{{ $gowns->total() }} {{ \Illuminate\Support\Str::plural('gown', $gowns->total()) }}
                                archived</p>
                        </div>
                    </div>

                    {{-- FILTERS --}}
                    <form method="GET" class="sb-inventory-filter" data-live-filter>
                        <input type="hidden" name="per_page" value="{{ $gowns->perPage() }}">
                        <div class="sb-inventory-search">
                            <input type="search" name="q" value="{{ request('q') }}"
                                placeholder="Search archived gowns, code, or color" aria-label="Search archived gowns"
                                data-live-search>
                        </div>

                        <select name="category" aria-label="Filter by category" data-live-apply>
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                    </form>

                    @if($gowns->isEmpty())
                        <div class="sb-inventory-empty">
                            <div class="sb-empty-icon">🗃</div>
                            <b>No archived gowns</b>
                            <p>Gowns you archive from the collection will appear here.</p>
                        </div>
                    @else
                        <div class="sb-inventory-table-wrap">
                            <table class="sb-inventory-table sb-gown-table">
                                <thead>
                                    <tr>
                                        <th>Gown</th>
                                        <th>Category</th>
                                        <th>Size</th>
                                        <th>Rental price</th>
                                        <th>Archived</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gowns as $gown)
                                        <tr class="sb-gown-row-link" data-href="{{ route('owner.gowns.show', $gown) }}"
                                            tabindex="0" role="link" aria-label="View archived gown {{ $gown->name }}">
                                            <td data-label="Gown">
                                                <div class="sb-inventory-name">
                                                    <div class="sb-inventory-thumb">
                                                        @if($gown->image)
                                                            <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}"
                                                                loading="lazy">
                                                        @else
                                                            <span aria-hidden="true">✿</span>
                                                        @endif
                                                    </div>
                                                    <span>
                                                        <b>{{ $gown->name }}</b>
                                                        <small>{{ $gown->gown_code }} ·
                                                            {{ ucfirst($gown->color ?: 'Color not set') }}</small>
                                                    </span>
                                                </div>
                                            </td>
                                            <td data-label="Category">{{ $gown->category->name ?? 'Uncategorized' }}</td>
                                            <td data-label="Size">{{ $gown->size ?: '—' }}</td>
                                            <td data-label="Rental price"><strong
                                                    class="sb-rental-price">₱{{ number_format($gown->rental_price, 2) }}</strong>
                                            </td>
                                            <td data-label="Archived">
                                                <span
                                                    class="sb-inventory-status is-archived">{{ $gown->archived_at?->toFormattedDateString() ?? '—' }}</span>
                                            </td>
                                            <td data-label="Actions">
                                                <div class="sb-inventory-actions">
                                                    <form method="POST" action="{{ route('owner.gowns.restore', $gown) }}"
                                                        data-confirm-title="Restore"
                                                        data-confirm-accent="this gown?"
                                                        data-confirm-message="Are you sure you want to restore this gown? It will return to active inventory."
                                                        data-confirm-yes="Yes, restore"
                                                        data-confirm-no="Keep archived">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="sb-action-restore sb-action-success" type="submit">Restore</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @include('components.table-pagination', ['paginator' => $gowns, 'itemLabel' => 'gowns'])
                    @endif

                </div>
            </section>

            <section class="sb-panel" style="margin-top: 1.5rem;">
                <div class="sb-inventory-content">
                    <div class="sb-inventory-toolbar">
                        <div>
                            <span class="sb-kicker">DAMAGE REPORT ARCHIVE</span>
                            <h2>Archived damage reports</h2>
                            <p>{{ $archivedDamageReports->total() }}
                                {{ \Illuminate\Support\Str::plural('report', $archivedDamageReports->total()) }} archived</p>
                        </div>
                    </div>

                    @if($archivedDamageReports->isEmpty())
                        <div class="sb-inventory-empty">
                            <b>No archived damage reports</b>
                            <p>Damage reports archived from the care log will appear here.</p>
                        </div>
                    @else
                        <div class="sb-inventory-table-wrap">
                            <table class="sb-inventory-table sb-damage-archive-table">
                                <thead>
                                    <tr>
                                        <th>Gown</th>
                                        <th>Damage</th>
                                        <th>Severity</th>
                                        <th>Archived</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($archivedDamageReports as $damage)
                                        <tr class="sb-record-row" tabindex="0" aria-haspopup="dialog"
                                            aria-label="View archived damage report for {{ $damage->gown?->name ?? 'removed gown' }}"
                                            data-record-title="{{ $damage->gown?->name ?? 'Archived damage report' }}"
                                            data-record-image="{{ $damage->gown?->image_url ?? '' }}"
                                            data-record-details="{{ json_encode([
                                                'Gown code' => $damage->gown?->gown_code ?? 'Removed gown',
                                                'Damage type' => ucfirst($damage->damage_type),
                                                'Description' => $damage->description ?: 'No description recorded',
                                                'Severity' => ucfirst($damage->severity),
                                                'Discovered' => $damage->discovered_at?->format('M d, Y') ?? $damage->created_at?->format('M d, Y') ?? '—',
                                                'Archived' => $damage->archived_at?->format('M d, Y g:i A') ?? '—',
                                                'Estimated cost' => $damage->estimated_repair_cost === null ? 'Not estimated' : '₱' . number_format($damage->estimated_repair_cost, 2),
                                                'Final cost' => $damage->final_repair_cost === null ? 'Not recorded' : '₱' . number_format($damage->final_repair_cost, 2),
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">
                                            <td data-label="Gown">
                                                <div class="sb-inventory-name">
                                                    <div class="sb-inventory-thumb">
                                                        @if($damage->gown?->image)
                                                            <img src="{{ $damage->gown->image_url }}" alt="{{ $damage->gown->name }}" loading="lazy">
                                                        @else
                                                            <span aria-hidden="true">✿</span>
                                                        @endif
                                                    </div>
                                                    <span>
                                                        <b>{{ $damage->gown?->name ?? 'Removed gown' }}</b>
                                                        <small>{{ $damage->gown?->gown_code ?? '' }}</small>
                                                    </span>
                                                </div>
                                            </td>
                                            <td data-label="Damage">
                                                <b>{{ $damage->damage_type }}</b>
                                                <span>{{ $damage->description ?: 'No description recorded' }}</span>
                                            </td>
                                            <td data-label="Severity">
                                                <span class="sb-inventory-status">{{ ucfirst($damage->severity) }}</span>
                                            </td>
                                            <td data-label="Archived">{{ $damage->archived_at?->format('M d, Y') ?? '—' }}</td>
                                            <td data-label="Actions">
                                                <div class="sb-inventory-actions">
                                                <form method="POST" action="{{ route('owner.damages.restore', $damage) }}"
                                                    data-confirm-title="Restore damage report"
                                                    data-confirm-accent="this report?"
                                                    data-confirm-message="Restore this report to the active damage log?"
                                                    data-confirm-yes="Yes, restore"
                                                    data-confirm-no="Keep archived">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="sb-action-restore sb-action-success" type="submit">Restore</button>
                                                </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @include('components.record-details-dialog')
                        @include('components.table-pagination', ['paginator' => $archivedDamageReports, 'itemLabel' => 'damage reports'])
                    @endif
                </div>
            </section>

            <div class="sb-inventory-toolbar" style="justify-content: center; margin-top: 1.5rem;">
                <a class="sb-inventory-secondary" href="{{ route('owner.damages.archive') }}">
                    View archived damage reports
                </a>
            </div>

        </div>
    </div>

</x-app-layout>
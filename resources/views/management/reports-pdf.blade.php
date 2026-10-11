<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 17mm 15mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #292f36;
            font: 8pt "DejaVu Sans", sans-serif;
            line-height: 1.4;
        }

        .running-header {
            position: fixed;
            top: -11mm;
            right: 0;
            left: 0;
            padding-bottom: 2mm;
            border-bottom: 1px solid #cbd0d5;
            color: #4b5560;
            font-size: 7pt;
        }

        .running-header table,
        .running-header td {
            border: 0;
            padding: 0;
        }

        .running-header td:last-child {
            text-align: right;
        }

        .document-heading {
            width: 100%;
            margin-bottom: 5mm;
            border-bottom: 1.5px solid #5c1a2b;
        }

        .document-heading td {
            padding: 0 0 4mm;
            border: 0;
            vertical-align: middle;
        }

        .brand-logo-cell {
            width: 23mm;
            padding-right: 4mm !important;
        }

        .brand-logo {
            display: block;
            width: 18mm;
            height: 18mm;
            object-fit: contain;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            margin: 0 0 1mm;
            color: #3b1822;
            font: bold 16pt "DejaVu Serif", serif;
        }

        h2 {
            margin: 0 0 2mm;
            color: #3b1822;
            font: bold 11pt "DejaVu Sans", sans-serif;
        }

        h3 {
            margin: 3mm 0 1.5mm;
            color: #44403c;
            font: bold 8.5pt "DejaVu Sans", sans-serif;
        }

        .eyebrow {
            margin-bottom: 1mm;
            color: #626d78;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: .8pt;
            text-transform: uppercase;
        }

        .period {
            margin: 0 0 1mm;
            color: #4b5560;
            font-size: 9pt;
            font-weight: bold;
        }

        .date-range,
        .report-meta {
            margin: 0;
            color: #626d78;
            font-size: 7pt;
        }

        .report-meta {
            width: 46mm;
            text-align: right;
            line-height: 1.55;
        }

        .report-meta strong {
            color: #3b1822;
        }

        .section {
            margin: 0 0 4mm;
        }

        .section-heading {
            margin-bottom: 2mm;
            padding-bottom: 1.5mm;
            border-bottom: 1px solid #cbd0d5;
        }

        .section-heading p {
            margin: 0;
            color: #626d78;
            font-size: 7pt;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tr {
            page-break-inside: avoid;
        }

        .report-table th {
            padding: 1.8mm 2mm;
            border: 1px solid #44505d;
            background: #44505d;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            text-align: left;
            vertical-align: middle;
        }

        .report-table td {
            padding: 1.7mm 2mm;
            border: 1px solid #d8dde2;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .report-table tbody tr:nth-child(even) td {
            background: #f6f7f8;
        }

        .summary-table td:first-child,
        .summary-table th:first-child {
            width: 58%;
        }

        .numeric,
        .amount {
            text-align: right !important;
            white-space: nowrap;
        }

        .status {
            text-transform: capitalize;
        }

        .total-row td {
            background: #e9edf0 !important;
            border-top: 1px solid #aeb7c0;
            font-weight: bold;
        }

        .empty {
            padding: 4mm !important;
            color: #626d78;
            text-align: center;
        }

        .details {
            page-break-before: always;
        }

        .details .report-table {
            font-size: 7pt;
        }

        .details .report-table th {
            font-size: 6.5pt;
        }

        .details .report-table td {
            padding: 1.5mm 1.4mm;
        }

        .record-section {
            margin-bottom: 5mm;
        }

        .numeric,
        .amount {
            font-variant-numeric: tabular-nums;
        }

        .report-meta {
            color: #4b5560;
        }

        .report-meta strong {
            color: #3b1822;
        }

        .note {
            margin: 2mm 0 0;
            color: #626d78;
            font-size: 7pt;
        }
    </style>
</head>

<body>
    <div class="running-header">
        <table>
            <tr>
                <td><strong>{{ $shopName }}</strong></td>
                <td>Business report · {{ $period['label'] }}</td>
            </tr>
        </table>
    </div>

    <table class="document-heading">
        <tr>
            @if($logoDataUri)
                <td class="brand-logo-cell">
                    <img class="brand-logo" src="{{ $logoDataUri }}" alt="{{ $shopName }} logo">
                </td>
            @endif
            <td>
                <p class="eyebrow">Gown reservation and rental management</p>
                <h1>{{ $shopName }} — Business Report</h1>
                <p class="period">{{ $period['label'] }}</p>
                <p class="date-range">
                    Reporting period:
                    {{ \Carbon\Carbon::parse($period['start_date'])->format('F j, Y') }}
                    to {{ \Carbon\Carbon::parse($period['end_date'])->format('F j, Y') }}
                </p>
            </td>
            <td class="report-meta">
                <strong>Confidential · For business use</strong><br>
                Prepared: {{ $generatedAt->format('M j, Y · g:i A') }}<br>
                @if($contactEmail){{ $contactEmail }}<br>@endif
                Bangkerohan, Barangay 5-A, Davao City.
            </td>
        </tr>
    </table>

    <section class="section">
        <div class="section-heading">
            <h2>Management summary</h2>
            <p>Key operating totals for the selected reporting period and current inventory.</p>
        </div>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Measure</th>
                    <th class="numeric">Result</th>
                    <th>Basis</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Reservations created</td>
                    <td class="numeric">{{ number_format($reservationCount) }}</td>
                    <td>{{ $period['start_date'] }} to {{ $period['end_date'] }}</td>
                </tr>
                <tr>
                    <td>Active rentals</td>
                    <td class="numeric">{{ number_format($activeRentalCount) }}</td>
                    <td>Released or overdue</td>
                </tr>
                <tr>
                    <td>Verified payments</td>
                    <td class="amount">₱{{ number_format($paymentTotal, 2) }}</td>
                    <td>Verified during reporting period</td>
                </tr>
                <tr>
                    <td>Available gowns</td>
                    <td class="numeric">{{ number_format($availableCount) }}</td>
                    <td>Out of {{ number_format($gownCount) }} gowns · {{ number_format($availabilityRate, 1) }}%</td>
                </tr>
                <tr>
                    <td>Overdue rentals</td>
                    <td class="numeric">{{ number_format($overdueRentalCount) }}</td>
                    <td>Current rental workload</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="section">
        <div class="section-heading">
            <h2>Reservation report</h2>
            <p>Reservation volume by month and current reservation status.</p>
        </div>
        <h3>Reservations by month</h3>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="numeric">Reservations</th>
                    <th class="numeric">Share of period</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly as $month)
                    <tr>
                        <td>{{ $month['label'] }}</td>
                        <td class="numeric">{{ number_format($month['value']) }}</td>
                        <td class="numeric">
                            {{ $reservationCount > 0 ? number_format(($month['value'] / $reservationCount) * 100, 1) : '0.0' }}%
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total reservations</td>
                    <td class="numeric">{{ number_format($reservationCount) }}</td>
                    <td class="numeric">{{ $reservationCount > 0 ? '100.0' : '0.0' }}%</td>
                </tr>
            </tbody>
        </table>

        <h3>Reservations by status</h3>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="numeric">Reservations</th>
                    <th class="numeric">Share of period</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    'pending' => 'Pending',
                    'awaiting_payment' => 'Awaiting payment',
                    'confirmed' => 'Confirmed',
                    'ready_for_pickup' => 'Ready for pickup',
                    'released' => 'Released',
                    'overdue' => 'Overdue',
                    'returned' => 'Returned',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'rejected' => 'Rejected',
                ] as $status => $label)
                    @php($statusCount = (int) $reservationStatusCounts->get($status, 0))
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="numeric">{{ number_format($statusCount) }}</td>
                        <td class="numeric">
                            {{ $reservationCount > 0 ? number_format(($statusCount / $reservationCount) * 100, 1) : '0.0' }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section">
        <div class="section-heading">
            <h2>Inventory and sales report</h2>
            <p>Gown availability, reservation demand, and payment status.</p>
        </div>
        <h3>Gowns by inventory status</h3>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Inventory status</th>
                    <th class="numeric">Gowns</th>
                    <th class="numeric">Share of inventory</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    'available' => 'Available',
                    'reserved' => 'Reserved',
                    'for_cleaning' => 'For cleaning',
                    'under_maintenance' => 'Under maintenance',
                    'damaged' => 'Damaged',
                    'retired' => 'Retired',
                ] as $status => $label)
                    @php($statusCount = (int) $gownsByStatus->get($status, 0))
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="numeric">{{ number_format($statusCount) }}</td>
                        <td class="numeric">
                            {{ $gownCount > 0 ? number_format(($statusCount / $gownCount) * 100, 1) : '0.0' }}%
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total gowns</td>
                    <td class="numeric">{{ number_format($gownCount) }}</td>
                    <td class="numeric">{{ $gownCount > 0 ? '100.0' : '0.0' }}%</td>
                </tr>
            </tbody>
        </table>

        <h3>Most reserved gowns</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 7%">Rank</th>
                    <th style="width: 24%">Gown</th>
                    <th style="width: 18%">Code</th>
                    <th style="width: 23%">Category</th>
                    <th class="numeric" style="width: 14%">Reservations</th>
                    <th style="width: 14%">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($popular as $index => $gown)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $gown->name }}</td>
                        <td>{{ $gown->gown_code }}</td>
                        <td>{{ $gown->category?->name ?? 'Collection' }}</td>
                        <td class="numeric">{{ number_format($gown->reservation_items_count) }}</td>
                        <td class="status">{{ str_replace('_', ' ', $gown->status) }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="6">No gown reservations in this reporting period.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h3>Payments by status</h3>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Payment status</th>
                    <th class="numeric">Transactions</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['verified' => 'Verified', 'pending' => 'Pending', 'rejected' => 'Rejected', 'refunded' => 'Refunded'] as $status => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="numeric">{{ number_format($payments->where('status', $status)->count()) }}</td>
                        <td class="amount">₱{{ number_format($paymentStatusTotals->get($status, 0), 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total payments</td>
                    <td class="numeric">{{ number_format($paymentCount) }}</td>
                    <td class="amount">₱{{ number_format($payments->sum('amount'), 2) }}</td>
                </tr>
            </tbody>
        </table>
        <p class="note">Verified payment totals exclude payments that are pending review, rejected, or refunded.</p>
    </section>

    <section class="section details">
        <div class="section-heading">
            <h2>Detailed records</h2>
            <p>Reservation and payment transactions created during the selected reporting period.</p>
        </div>

        <div class="record-section">
            <h3>Reservations</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 14%">Reservation</th>
                        <th style="width: 17%">Customer</th>
                        <th style="width: 23%">Gown(s)</th>
                        <th style="width: 12%">Pickup</th>
                        <th style="width: 12%">Return</th>
                        <th class="amount" style="width: 12%">Total</th>
                        <th style="width: 10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->reservation_code }}</td>
                            <td>{{ $reservation->customer?->full_name ?? 'Guest' }}</td>
                            <td>{{ $reservation->items->map(fn($item) => $item->gown?->name)->filter()->join(', ') ?: 'Gown' }}</td>
                            <td>{{ $reservation->pickup_date?->format('M d, Y') }}</td>
                            <td>{{ $reservation->return_date?->format('M d, Y') }}</td>
                            <td class="amount">₱{{ number_format($reservation->grand_total, 2) }}</td>
                            <td class="status">{{ str_replace('_', ' ', $reservation->status) }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="7">No reservations were recorded in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="record-section">
            <h3>Payment transactions</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 13%">Date</th>
                        <th style="width: 18%">Customer</th>
                        <th style="width: 15%">Reservation</th>
                        <th style="width: 15%">Type</th>
                        <th style="width: 13%">Method</th>
                        <th class="amount" style="width: 14%">Amount</th>
                        <th style="width: 12%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at?->format('M d, Y') }}</td>
                            <td>{{ $payment->customer?->full_name ?? 'Customer' }}</td>
                            <td>{{ $payment->reservation?->reservation_code ?? '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td class="amount">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="status">{{ ucfirst($payment->status) }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="7">No payment transactions were recorded in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</body>

</html>

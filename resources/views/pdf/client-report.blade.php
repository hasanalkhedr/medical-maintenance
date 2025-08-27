<!DOCTYPE html>
<html>

<head>
    <title>Client Report - {{ $client->name }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format('truetype');
        }

        @page {
            margin: 5mm 10mm 15mm 10mm;
            size: A4;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #000;
            line-height: 1.1;
        }

        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            height: 15mm;
            margin: 0 15mm;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
            padding-top: 5px;
            color: gray;
        }

        .content {
            margin-bottom: 20mm;
        }

        .company-name {
            font-weight: normal;
            text-transform: uppercase;
            font-size: 11px;
            color: gray;
            margin-bottom: 10px;
        }

        .company-details {
            padding-top: 10px;
            font-size: 11px;
            line-height: 1.4;
        }

        .report-title {
            font-size: 30px;
            font-weight: bold;
            color: #c45911;
            margin: 5px 0 15px 0;
            text-transform: uppercase;
        }

        .report-info {
            font-size: 12px;
            text-align: right;
        }

        .to-section {
            margin: 5px 0;
            padding: 3px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 6px;
            border: 1px solid #000;
        }

        .invoice-table th {
            background: #2d74b5;
            color: white;
            text-align: left;
            font-weight: bold;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin: 5px 0;
        }

        .payment-table th,
        .payment-table td {
            padding: 4px;
            border: 1px solid #ddd;
        }

        .payment-table th {
            background: #e8f4ff;
            color: #2d74b5;
            text-align: left;
            font-weight: bold;
        }

        .totals-table {
            width: 50%;
            margin-left: auto;
            margin-top: 5px;
            border-collapse: collapse;
            font-size: 11px;
        }

        .totals-table td {
            padding: 5px;
            border: 1px solid #000;
        }

        .totals-table tr td:first-child {
            text-align: left;
        }

        .totals-table tr td:last-child {
            text-align: right;
        }

        .totals-table tr.total-row td {
            font-weight: bold;
            background: #e8f4ff;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-partial {
            background: #fff3cd;
            color: #856404;
        }

        .status-pending {
            background: #f8d7da;
            color: #721c24;
        }

        .page-break {
            page-break-after: always;
        }

        .thank-you {
            text-align: center;
            margin-top: 30px;
            font-style: italic;
            color: #2d74b5;
        }

        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 80px;
            color: #2d74b5;
            transform: rotate(-45deg);
            left: 100px;
            top: 300px;
            z-index: -1;
        }
    </style>
</head>

<body>
    <!-- Footer -->
    <div class="footer">
        MASARNA MEDICAL & LABORATORY EQUIPMENT REPAIRING L.L.C |
        DUBAI, AL MARARR2 building | Phone: +971585240096 | Email: gm@masarna.ae | www.masarna.ae | TRN: 104293486700003
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Header -->
        <div class="company-name">
            MASARNA MEDICAL & LABORATORY EQUIPMENT REPAIRING L.L.C
        </div>

        <table width="100%" style="margin-top:10px; margin-bottom:20px;">
            <tr>
                <!-- Left side -->
                <td width="55%" valign="top">
                    <table>
                        <tr>
                            <td valign="top" style="padding-right:10px;">
                                <img src="{{ public_path('images/logo-noname.png') }}" alt="Logo" height="60">
                                <div class="company-details">
                                    <div>DUBAI, AL MARARR2 building</div>
                                    <div>Phone : +971585240096</div>
                                    <div>gm@masarna.ae</div>
                                    <div>www.masarna.ae</div>
                                    <div><strong>TRN: 104293486700003</strong></div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Right side -->
                <td width="45%" valign="top" align="right">
                    <div class="report-title">CLIENT REPORT</div>
                    <div class="report-info">
                        <div><span style="color: rgb(45, 116, 181);font-weight: bold;">CLIENT</span> {{ $client->name }}
                        </div>
                        <div><span style="color: rgb(116, 45, 181);font-weight: bold;">DATE RANGE</span>
                            {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : 'Start' }}
                            -
                            {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : 'End' }}
                        </div>
                        <div><span style="color: rgb(45, 116, 181);font-weight: bold;">GENERATED</span>
                            {{ $generatedAt->format('d/m/Y H:i') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Client Information -->
        <div class="to-section">
            <p><strong>Client: </strong>{{ $client->name }}</p>
            <p>{{ $client->address }} | {{ $client->phone }} | {{ $client->email }}</p>
            <p>Contact: {{ $client->contact_person }} - {{ $client->contact_person_phone }}</p>
        </div>

        {{-- <!-- Summary Statistics -->
        <h3 style="color: #2d74b5; margin: 15px 0 10px 0;">Summary Statistics</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4>TOTAL INVOICES</h4>
                <div class="value">{{ $stats['total_invoices'] }}</div>
            </div>
            <div class="summary-item">
                <h4>TOTAL AMOUNT</h4>
                <div class="value">{{ number_format($stats['total_amount'], 2) }} AED</div>
            </div>
            <div class="summary-item">
                <h4>TOTAL PAID</h4>
                <div class="value">{{ number_format($stats['total_paid'], 2) }} AED</div>
            </div>
            <div class="summary-item">
                <h4>TOTAL BALANCE</h4>
                <div class="value">{{ number_format($stats['total_remaining'], 2) }} AED</div>
            </div>
        </div>

        <div style="text-align: center; margin: 10px 0;">
            <div style="font-weight: bold; color: #2d74b5;">Payment Progress: {{ number_format($stats['paid_percentage'], 1) }}% Paid</div>
            <div style="width: 100%; background: #e9ecef; height: 20px; border-radius: 10px; margin: 5px 0;">
                <div style="width: {{ min($stats['paid_percentage'], 100) }}%; background: #28a745; height: 100%; border-radius: 10px;"></div>
            </div>
        </div> --}}
        <!-- Summary Statistics -->
        <h3 style="color: #2d74b5; margin: 15px 0 10px 0; border-bottom: 2px solid #2d74b5; padding-bottom: 5px;">
            Summary Statistics</h3>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin: 15px 0; border-collapse: collapse;">
            <tr>
                <td width="25%" valign="top" style="padding: 5px;">
                    <div
                        style="border: 1px solid #2d74b5; padding: 10px; text-align: center; border-radius: 5px; background: #f8f9fa;">
                        <h4 style="margin: 0 0 5px 0; color: #2d74b5; font-size: 11px; font-weight: bold;">TOTAL
                            INVOICES</h4>
                        <div style="font-size: 16px; font-weight: bold; color: #c45911;">{{ $stats['total_invoices'] }}
                        </div>
                    </div>
                </td>
                <td width="25%" valign="top" style="padding: 5px;">
                    <div
                        style="border: 1px solid #2d74b5; padding: 10px; text-align: center; border-radius: 5px; background: #f8f9fa;">
                        <h4 style="margin: 0 0 5px 0; color: #2d74b5; font-size: 11px; font-weight: bold;">TOTAL AMOUNT
                        </h4>
                        <div style="font-size: 16px; font-weight: bold; color: #c45911;">
                            {{ number_format($stats['total_amount'], 2) }} AED</div>
                    </div>
                </td>
                <td width="25%" valign="top" style="padding: 5px;">
                    <div
                        style="border: 1px solid #2d74b5; padding: 10px; text-align: center; border-radius: 5px; background: #f8f9fa;">
                        <h4 style="margin: 0 0 5px 0; color: #2d74b5; font-size: 11px; font-weight: bold;">TOTAL PAID
                        </h4>
                        <div style="font-size: 16px; font-weight: bold; color: #c45911;">
                            {{ number_format($stats['total_paid'], 2) }} AED</div>
                    </div>
                </td>
                <td width="25%" valign="top" style="padding: 5px;">
                    <div
                        style="border: 1px solid #2d74b5; padding: 10px; text-align: center; border-radius: 5px; background: #f8f9fa;">
                        <h4 style="margin: 0 0 5px 0; color: #2d74b5; font-size: 11px; font-weight: bold;">TOTAL BALANCE
                        </h4>
                        <div style="font-size: 16px; font-weight: bold; color: #c45911;">
                            {{ number_format($stats['total_remaining'], 2) }} AED</div>
                    </div>
                </td>
            </tr>
        </table>
        <div style="text-align: center; margin: 10px 0;">
            <div style="font-weight: bold; color: #2d74b5;">Payment Progress:
                {{ number_format($stats['paid_percentage'], 1) }}% Paid</div>
            <div style="width: 100%; background: #e9ecef; height: 20px; border-radius: 10px; margin: 5px 0;">
                <div
                    style="width: {{ min($stats['paid_percentage'], 100) }}%; background: #28a745; height: 100%; border-radius: 10px;">
                </div>
            </div>
        </div>


        <!-- Invoices Details -->
        <h3 style="color: #2d74b5; margin: 20px 0 10px 0;">Invoices Details</h3>

        @foreach ($invoices as $index => $invoice)
            <div style="margin-bottom: 20px; page-break-inside: avoid;">
                <!-- Invoice Header -->
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th colspan="4" style="background: #c45911;">
                                INVOICE #{{ $invoice->invoice_number }} - {{ $invoice->title }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td width="25%"><strong>Issue Date:</strong> {{ $invoice->issue_date->format('d/m/Y') }}
                            </td>
                            <td width="25%"><strong>Due Date:</strong> {{ $invoice->due_date->format('d/m/Y') }}
                            </td>
                            <td width="25%"><strong>Total Amount:</strong>
                                {{ number_format($invoice->total_amount, 2) }} AED</td>
                            <td width="25%"><strong>Status:</strong>
                                <span
                                    class="status-badge
                                @if ($invoice->status === 'paid') status-paid
                                @elseif($invoice->status === 'partial') status-partial
                                @else status-pending @endif">
                                    {{ strtoupper($invoice->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Subtotal:</strong> {{ number_format($invoice->subtotal, 2) }} AED</td>
                            <td>
                                @if ($invoice->tax_amount > 0)
                                    <strong>VAT ({{ $invoice->tax_rate }}%):</strong>
                                    {{ number_format($invoice->tax_amount, 2) }} AED
                                @endif
                            </td>
                            <td>
                                @if ($invoice->discount_amount > 0)
                                    <strong>Discount ({{ $invoice->discount_rate }}%):</strong>
                                    {{ number_format($invoice->discount_amount, 2) }} AED
                                @endif
                            </td>
                            <td><strong>Contract:</strong> {{ $invoice->contract->contract_number ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Invoice Items -->
                @if ($invoice->items->count() > 0)
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th width="75%">Description</th>
                                <th width="25%" style="text-align:right;">Amount (AED)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->description }}</strong><br>
                                        <small>Qty: {{ $item->quantity }} × {{ number_format($item->unit_price, 2) }}
                                            AED</small>
                                    </td>
                                    <td style="text-align:right; vertical-align:top;">
                                        {{ number_format($item->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- Payment Summary -->
                <table class="totals-table">
                    <tr>
                        <td>Paid Amount:</td>
                        <td>{{ number_format($invoice->paid_amount, 2) }} AED</td>
                    </tr>
                    <tr>
                        <td>Remaining Balance:</td>
                        <td>{{ number_format($invoice->remaining_amount, 2) }} AED</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>PROGRESS:</strong></td>
                        <td><strong>{{ number_format($invoice->paid_percentage, 1) }}%</strong></td>
                    </tr>
                </table>

                <!-- Payments Details -->
                @if ($invoice->payments->count() > 0)
                    <h4 style="color: #2d74b5; margin: 10px 0 5px 0;">Payments ({{ $invoice->payments->count() }})</h4>
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th width="20%">Date</th>
                                <th width="15%" style="text-align:right;">Amount</th>
                                <th width="20%">Method</th>
                                <th width="25%">Reference</th>
                                <th width="20%">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td style="text-align:right;">{{ number_format($payment->amount, 2) }} AED</td>
                                    <td>{{ \App\Models\Payment::getPaymentMethods()[$payment->payment_method] ?? $payment->payment_method }}
                                    </td>
                                    <td>{{ $payment->reference ?? 'N/A' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($payment->notes, 20) ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: #666; font-style: italic; margin: 10px 0; text-align: center;">
                        No payments recorded for this invoice
                    </p>
                @endif

                @if (!$loop->last)
                    <hr style="border-top: 2px dashed #2d74b5; margin: 20px 0;">
                @endif
            </div>
        @endforeach

        <!-- Thank You -->
        <div class="thank-you">
            <p>REPORT GENERATED BY MASARNA MEDICAL SYSTEM</p>
            <p>For any inquiries, please contact: gm@masarna.ae</p>
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 10;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>

</html>

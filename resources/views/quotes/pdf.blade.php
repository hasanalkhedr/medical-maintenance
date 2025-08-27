<!DOCTYPE html>
<html>

<head>
    <title>Quotation #{{ $quote->quote_number }}</title>
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

        .quote-title {
            font-size: 30px;
            font-weight: bold;
            color: rgb(45, 116, 181);
            margin: 5px 0 15px 0;
            text-transform: uppercase;
        }

        .quote-info {
            font-size: 12px;
            text-align: right;
        }

        .to-section {
            margin: 5px 0;
            padding: 3px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .description-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .description-table th,
        .description-table td {
            padding: 6px;
            border: 1px solid #000;
        }

        .description-table th {
            background: #2d74b5;
            color: white;
            text-align: left;
            font-weight: bold;
        }

        .totals-table {
            width: 50%;
            margin-left: auto;
            margin-top: 5px;
            border-collapse: collapse;
            font-size: 11px;
            page-break-inside: avoid;
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

        .terms-section {
            margin-top: 20px;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .terms-section h4 {
            margin-bottom: 3px;
            font-weight: bold;
            color: #2d74b5;
        }

        .terms-section p {
            margin: 1px 0;
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

    <!-- Watermark -->
    @if($quote->status === 'draft')
    <div class="watermark">DRAFT</div>
    @elseif($quote->status === 'approved')
    <div class="watermark">APPROVED</div>
    @endif

    <!-- Content - Original Header -->
    <div class="content">
        <div class="company-name">
            MASARNA MEDICAL & LABORATORY EQUIPMENT REPAIRING L.L.C
        </div>

        <!-- Header with two columns - ORIGINAL STYLE -->
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
                    <div class="quote-title">QUOTATION</div>
                    <div class="quote-info">
                        <div><span style="color: rgb(45, 116, 181);font-weight: bold;">QUOTATION</span> # {{ $quote->quote_number }}</div>
                        <div><span style="color: rgb(116, 45, 181);font-weight: bold;">DATE</span> {{ $quote->issue_date->format('d/m/Y') }}</div>
                        <div><span style="color: rgb(45, 116, 181);font-weight: bold;">VALID UNTIL</span> {{ $quote->expiry_date->format('d/m/Y') }}</div>
                        <p></p>
                        <div><span style="color: rgb(45, 116, 181);font-weight: bold;">FOR</span> <strong>{{ $quote->title ?? 'Service' }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- To Section -->
        <div class="to-section">
            <p><strong>To: </strong>{{ $quote->medicalInstitution->name }}</p>
        </div>

        <!-- Description Table -->
        <table class="description-table">
            <thead>
                <tr>
                    <th width="75%">Description</th>
                    <th width="25%" style="text-align:right;">Amount (AED)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quote->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong><br>
                        <small>Qty: {{ $item->quantity }} × {{ number_format($item->unit_price, 2) }} AED</small>
                    </td>
                    <td style="text-align:right; vertical-align:top;">
                        {{ number_format($item->amount, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Table -->
        <table class="totals-table">
            @php
                $subtotal = $quote->subtotal;
                $discountAmount = $quote->discount_amount;
                $taxAmount = $quote->tax_amount;
                $totalAfterDiscount = $subtotal - $discountAmount;
                $grandTotal = $quote->total_amount;
            @endphp

            <tr>
                <td width="50%">Subtotal:</td>
                <td width="50%">{{ number_format($subtotal, 2) }} AED</td>
            </tr>

            @if ($discountAmount > 0)
            <tr>
                <td>Discount ({{ $quote->discount_rate }}%):</td>
                <td>-{{ number_format($discountAmount, 2) }} AED</td>
            </tr>
            <tr>
                <td>Total after Discount:</td>
                <td>{{ number_format($totalAfterDiscount, 2) }} AED</td>
            </tr>
            @endif

            @if ($taxAmount > 0)
            <tr>
                <td>VAT ({{ $quote->tax_rate }}%):</td>
                <td>+{{ number_format($taxAmount, 2) }} AED</td>
            </tr>
            @endif

            <tr class="total-row">
                <td><strong>GRAND TOTAL:</strong></td>
                <td><strong>{{ number_format($grandTotal, 2) }} AED</strong></td>
            </tr>
        </table>

        <!-- Terms Section -->
        <div class="terms-section">
            @if($quote->technical_terms)
            <h4>Technical Terms:</h4>
            <p>{!! nl2br(e($quote->technical_terms)) !!}</p>
            @endif

            @if($quote->payment_terms)
            <h4>Payment Terms:</h4>
            <p>{!! nl2br(e($quote->payment_terms)) !!}</p>
            @endif

            @if(!$quote->technical_terms && !$quote->payment_terms)
            <h4>Standard Terms:</h4>
            <p>• 3 months warranty on all services and parts</p>
            <p>• Payment due within 30 days upon completion of service</p>
            <p>• Prices are valid for 30 days from quotation date</p>
            @endif
        </div>

        <!-- Thank You -->
        <div class="thank-you">
            <p>THANK YOU FOR YOUR BUSINESS</p>
            <p>We look forward to serving you</p>
        </div>
    </div>
</body>

</html>

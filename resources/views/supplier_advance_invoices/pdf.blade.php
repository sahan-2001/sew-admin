<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Advance Invoice - {{ $invoiceDetails['id'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header, .footer {
            text-align: center;
        }
        .details, .supplier-details {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
        }
        .details th, .details td, .supplier-details th, .supplier-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th, .supplier-details th {
            background-color: #f2f2f2;
        }
        .payment-details th {
            text-align: center;
        }
        .payment-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-details th, .payment-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .payment-details th {
            background-color: #f2f2f2;
        }
        .payment-details tfoot th {
            text-align: right;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature div {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px dotted black;
            margin: 10px auto;
            width: 80%;
        }
        .qr-code {
            width: 48%;
            float: right;
            text-align: center;
            margin-top: 20px;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
        <h2>Supplier Advance Invoice</h2>
    </div>

    <div class="details">
        <h3>Invoice Details</h3>
        <table>
            <tr>
                <th>Invoice ID</th>
                <td>{{ $invoiceDetails['id'] }}</td>
            </tr>
            <tr>
                <th>Purchase Order ID</th>
                <td>{{ $invoiceDetails['purchase_order_id'] }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $invoiceDetails['status'] }}</td>
            </tr>
            <tr>
                <th>Payment Type</th>
                <td>{{ $invoiceDetails['payment_type'] }}</td>
            </tr>
            <tr>
                <th>Created Date</th>
                <td>{{ $invoiceDetails['created_at'] }}</td>
            </tr>
        </table>
    </div>

    <div class="qr-code">
        <h4>Scan to View Invoice</h4>
       <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" 
            width="100" height="100"
            alt="QR Code for Invoice {{ $invoiceDetails['id'] }}"
            style="width: 100px; height: 100px; border: 1px solid #eee;">
        <div style="margin-top: 10px;">
            <a href="{{ $qrCodeData }}" 
            style="font-size: 12px; color: #3490dc; text-decoration: none;">
            View Invoice Online
            </a>
        </div>
    </div>

    <div class="payment-details" style="clear: both;">
        <h3>Payment Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Paid Amount</th>
                    <th>Remaining Amount</th>
                    <th>Paid Date</th>
                    <th>Payment Method</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($invoiceDetails['paid_amount'], 2) }}</td>
                    <td>{{ number_format($invoiceDetails['remaining_amount'], 2) }}</td>
                    <td>{{ $invoiceDetails['paid_date'] }}</td>
                    <td>{{ $invoiceDetails['paid_via'] }}</td>
                </tr>
            </tbody>
        </table>

        @if($invoiceDetails['payment_type'] === 'percentage')
        <h3 style="margin-top: 20px;">Percentage Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Payment Percentage</th>
                    <th>Calculated Payment</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoiceDetails['payment_percentage'] }}%</td>
                    <td>{{ number_format($invoiceDetails['percent_calculated_payment'], 2) }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        @if($invoiceDetails['payment_type'] === 'fixed')
        <h3 style="margin-top: 20px;">Fixed Payment Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Fixed Payment</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoiceDetails['fix_payment_amount'] }}%</td>
                </tr>
            </tbody>
        </table>
        @endif
    </div>

    <div class="signature">
        <div style="flex: 1; text-align: left;">
            <p>Company Representative</p>
            <div class="signature-line"></div>
        </div>
        <div style="flex: 1; text-align: right;">
            <p>Supplier Representative</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>This is a computer generated document. No signature is required.</p>
    </div>
</body>
</html>
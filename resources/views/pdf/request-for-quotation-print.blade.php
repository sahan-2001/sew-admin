<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFQ {{ str_pad($rfqDetails['id'], 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header, .footer { text-align: center; }
        .details, .provider-details { margin-top: 20px; width: 100%; float: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .signature { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature div { width: 45%; text-align: center; }
        .signature-line { border-top: 1px dotted black; margin: 10px auto; width: 80%; }
        .qr-code { width: 100%; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $companyDetails['name'] }}</h1>
    <p>{{ $companyDetails['address'] }}</p>
    <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
</div>

<!-- Header -->
<div class="report_header">
    <h1>Request for Quotation (RFQ)</h1>
    <hr>
</div>


<!-- RFQ Details -->
<div class="provider-details">
    <h3>RFQ Information</h3>
    <table>
        <tr><th>Status</th><td>{{ ucfirst($rfqDetails['status']) }}</td></tr>
        <tr><th>RFQ ID</th><td>{{ str_pad($rfqDetails['id'], 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><th>Wanted Delivery Date</th><td>{{ $rfqDetails['wanted_delivery_date'] }}</td></tr>
        <tr><th>Valid Until</th><td>{{ $rfqDetails['valid_until'] ?? 'Not set' }}</td></tr>
        <tr><th>Created By</th><td>{{ $rfqDetails['created_by'] }}</td></tr>
        <tr><th>Created At</th><td>{{ $rfqDetails['created_at'] }}</td></tr>
    </table>
</div>

<br>

<!-- Supplier Details -->
<div class="provider-details">
    <h3>Supplier Information</h3>
    <table>
        <tr><th>Supplier ID</th><td>{{ $rfqDetails['supplier_id'] }}</td></tr>
        <tr><th>Supplier Name</th><td>{{ $rfqDetails['supplier_name'] }}</td></tr>
        <tr><th>Email</th><td>{{ $rfqDetails['supplier_email'] }}</td></tr>
        <tr><th>Phone</th><td>{{ $rfqDetails['supplier_phone'] }}</td></tr>
    </table>
</div>

<!-- RFQ Items -->
<div style="clear: both; margin-top: 30px;">
    <h2>RFQ Items</h2>
    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Item Name</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($items as $item)
                @php $total += $item->item_subtotal; @endphp
                <tr>
                    <td>{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                    <td>{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->item_subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="width: 40%; float: right; margin-top: 20px;">
        <table>
            <tr>
                <th class="text-right">Grand Total</th>
                <td class="text-right">Rs. {{ number_format($total, 2) }}</td>
            </tr>
        </table>
    </div>
    <div style="clear: both;"></div>
</div>

<!-- Signatures -->
<div class="signature">
    <div>
        <p>Authorized Signature</p>
        <div class="signature-line"></div>
    </div>
    <div>
        <p>Received By</p>
        <div class="signature-line"></div>
    </div>
</div>

<!-- QR Code -->
<div class="qr-code">
    <h4>Scan to View RFQ</h4>
    <img src="{{ $qrCodePath }}" width="100" height="100" style="border:1px solid #eee; display:block; margin:0 auto;">
    <div style="margin-top: 8px; font-size:12px;">
        <a href="{{ $qrCodeData }}" style="color:#3490dc; text-decoration:none;">View Online</a>
    </div>
</div>

<div class="footer">
    <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
</div>

</body>
</html>

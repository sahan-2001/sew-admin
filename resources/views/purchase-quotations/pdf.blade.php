<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Quotation #{{ $purchase_quotation->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; line-height: 1.4; }
        .header, .footer { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .signature { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature div { width: 45%; text-align: center; }
        .signature-line { border-top: 1px dotted black; margin: 10px auto; width: 80%; }
        .qr-code { text-align: center; margin-top: 20px; }
        .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 5px; }
        .progress-bar { height: 10px; border-radius: 5px; }
        .status-text { font-weight: bold; }
        .details { display: flex; justify-content: space-between; gap: 20px; }
        .details table { width: 48%; }
    </style>
</head>
<body>

<!-- Company Header -->
<div class="header">
    <h2>{{ $companyDetails['name'] }}</h2>
    <p>{{ $companyDetails['address'] }}</p>
    <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
    <hr>
</div>

<!-- Header -->
<div class="report_header">
    <h1>Purchase Quotation</h1>
    <hr>
</div>

<!-- Quotation & Supplier Details -->
<div class="details">
    <table>
        <tr><th>Quotation ID</th><td>{{ str_pad($purchase_quotation->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst($purchase_quotation->status) }}</td></tr>
        <tr><th>Wanted Date</th><td>{{ $purchase_quotation->wanted_delivery_date ?? '-' }}</td></tr>
        <tr><th>Promised Date</th><td>{{ $purchase_quotation->promised_delivery_date ?? '-' }}</td></tr>
        <tr><th>VAT Base</th><td>{{ ucfirst(str_replace('_',' ',$purchase_quotation->vat_base)) }}</td></tr>
        <tr><th>Special Notes</th><td>{{ $purchase_quotation->special_note ?? '-' }}</td></tr>
        @if($purchase_quotation->vat_base === 'supplier_vat')
        <tr><th>Supplier VAT Rate</th><td>{{ $purchase_quotation->supplier_vat_rate }}%</td></tr>
        <tr><th>Order VAT Amount</th><td>Rs. {{ number_format($purchase_quotation->vat_amount, 2) }}</td></tr>
        @endif
    </table>

    <table>
        <tr><th>Supplier ID</th><td>{{ str_pad($purchase_quotation->supplier?->supplier_id ?? 0, 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><th>Supplier Name</th><td>{{ $purchase_quotation->supplier?->name ?? 'N/A' }}</td></tr>
        <tr><th>Email</th><td>{{ $purchase_quotation->supplier?->email ?? 'N/A' }}</td></tr>
        <tr><th>Phone</th><td>{{ $purchase_quotation->supplier?->phone_1 ?? 'N/A' }}</td></tr>
    </table>
</div>

<!-- Items Table -->
<div class="section">
    <div class="section-title">Quotation Items</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th class="text-right">Price</th>
                <th class="text-right">Subtotal</th>
                @if($purchase_quotation->vat_base === 'item_vat')
                    <th class="text-right">VAT Amount</th>
                    <th class="text-right">Total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php $subtotal=0; $vatTotal=0; $grandTotal=0; @endphp
            @foreach($quotationItems as $index => $item)
            <tr>
                <td>{{ $index+1 }}</td>
                <td>{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                <td>{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->price,2) }}</td>
                <td class="text-right">{{ number_format($item->item_subtotal,2) }}</td>
                @if($purchase_quotation->vat_base === 'item_vat')
                    <td class="text-right">{{ number_format($item->item_vat_amount,2) }}</td>
                    <td class="text-right">{{ number_format($item->item_grand_total,2) }}</td>
                @endif
            </tr>
            @php
                $subtotal += $item->item_subtotal;
                $vatTotal += $item->item_vat_amount;
                $grandTotal += $item->item_grand_total;
            @endphp
            @endforeach
        </tbody>
    </table>
</div>

<!-- Totals -->
<div class="section" style="width: 40%; float:right;">
    <table>
        <tbody>
            <tr><th class="text-right">Subtotal</th><td class="text-right">Rs. {{ number_format($subtotal,2) }}</td></tr>
            @if($purchase_quotation->vat_base === 'item_vat')
                <tr><th class="text-right">VAT Total</th><td class="text-right">Rs. {{ number_format($vatTotal,2) }}</td></tr>
                <tr><th class="text-right">Grand Total</th><td class="text-right">Rs. {{ number_format($grandTotal,2) }}</td></tr>
            @else
                <tr><th class="text-right">VAT Amount</th><td class="text-right">Rs. {{ number_format($purchase_quotation->vat_amount,2) }}</td></tr>
                <tr><th class="text-right">Grand Total</th><td class="text-right">Rs. {{ number_format($purchase_quotation->grand_total,2) }}</td></tr>
            @endif
        </tbody>
    </table>
</div>
<div style="clear: both;"></div>

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
    <h4>Scan to View Quotation</h4>
    {!! file_get_contents($qrCodePath) !!}
</div>

<!-- Footer -->
<div class="footer">
    <hr>
    <p>Generated on {{ now()->format('Y-m-d H:i') }}</p>
</div>

</body>
</html>

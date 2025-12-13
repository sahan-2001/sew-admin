<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header, .footer {
            text-align: center;
        }
        .details, .provider-details {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
        }
        .details th, .details td, .provider-details th, .provider-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th, .provider-details th {
            background-color: #f2f2f2;
        }
        .items th {
            text-align: center;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .items th, .items td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items th {
            background-color: #f2f2f2;
        }
        .items tfoot th {
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
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
    </div>

    <div class="provider-details">
        <h2>Purchase Order Details</h2>
        <table>
            <tr>
                <th>Status</th>
                <td>{{ $purchaseOrderDetails['status'] }}</td>
            </tr>
            <tr>
                <th>Purchase Order ID</th>
                <td>{{ str_pad($purchaseOrderDetails['id'], 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Supplier ID</th>
                <td>{{ $purchaseOrderDetails['supplier_id'] }}</td>
            </tr>
            <tr>
                <th>Supplier Name</th>
                <td>{{ $purchaseOrderDetails['supplier_name'] }}</td>
            </tr>
            <tr>
                <th>Supplier Email</th>
                <td>{{ $purchaseOrderDetails['supplier_email'] }}</td>
            </tr>
            <tr>
                <th>Supplier Phone</th>
                <td>{{ $purchaseOrderDetails['supplier_phone'] }}</td>
            </tr>
            <tr>
                <th>Wanted Date</th>
                <td>{{ $purchaseOrderDetails['wanted_date'] }}</td>
            </tr>
            <tr>
                <th>Created Date</th>
                <td>{{ $purchaseOrderDetails['created_at'] }}</td>
            </tr>
        </table>
    </div>

    <div class="qr-code">
        <h4>Scan to View Purchase Order</h4>
        <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" 
            width="100" height="100"
            alt="QR Code for Purchase Order {{ $purchaseOrderDetails['id'] }}"
            style="width: 100px; height: 100px; border: 1px solid #eee;">
        <div style="margin-top: 10px;">
            <a href="{{ $qrCodeData }}" 
            style="font-size: 12px; color: #3490dc; text-decoration: none;">
            View Purchase Order Online
            </a>
        </div>
    </div>

    <div class="items" style="clear: both;">
        <h2>Purchase Order Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Inventory Item ID</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseOrderItems as $item)
                <tr>
                    <td>{{ $item->inventory_item_id }}</td>
                    <td>{{ $item->inventoryItem->item_code ?? 'N/A' }}</td>
                    <td>{{ $item->inventoryItem->name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>{{ number_format($item->quantity * $item->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right;">Grand Total</th>
                    <th>{{ number_format($grandTotal, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="signature">
        <div style="flex: 1; text-align: left;">
            <p>Authorized Signature</p>
            <div class="signature-line"></div>
        </div>
        <div style="flex: 1; text-align: right;">
            <p>Received By</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

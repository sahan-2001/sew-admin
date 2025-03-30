<!-- filepath: resources/views/purchase-orders/pdf.blade.php -->
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
        .details {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td{
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th {
            background-color: #f2f2f2;
        }
        .items th {
            text-align: center;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
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
            border-top: 1px solid black;
            margin: 10px auto;
            width: 80%;
        }
    </style>

</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
    </div>

    <div class="details">
        <h2>Purchase Order Details</h2>
        <table>
            <tr>
                <th>Status</th>
                <td>{{ $purchaseOrderDetails['status'] }}</td>
            </tr>
            
            <tr>
                <th>Purchase Order ID</th>
                <td>{{ $purchaseOrderDetails['id'] }}</td>
            </tr>
            <tr>
                <th>Provider Type</th>
                <td>{{ $purchaseOrderDetails['provider_type'] }}</td>
            </tr>
            <tr>
                <th>Provider ID</th>
                <td>{{ $purchaseOrderDetails['provider_id'] }}</td>
            </tr>
            <tr>
                <th>Provider Name</th>
                <td>{{ $purchaseOrderDetails['provider_name'] }}</td>
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

    <div class="items">
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

    <div class="qr-code">
        @if ($qrCodePath)
            <img src="{{ $qrCodePath }}" alt="QR Code" style="width: 150px; height: 150px;">
        @else
            <p>QR Code not available</p>
        @endif
    </div>

    
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

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
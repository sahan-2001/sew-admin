<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Order #{{ str_pad($orderDetails['id'], 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .details, .qr-code {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
        }
        .details th, .details td, .qr-code th, .qr-code td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th, .qr-code th {
            background-color: #f2f2f2;
        }
        .qr-code {
            float: right;
            text-align: center;
        }
        .qr-code img {
            width: 100px;
            height: 100px;
            border: 1px solid #eee;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        .bg-blue-50 {
            background-color: #ebf8ff;
        }
        .font-medium {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
    </div>

    <!-- Order Details -->
    <div class="details">
        <h2>Customer Order Details</h2>
        <table>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($orderDetails['status']) }}</td>
            </tr>
            <tr>
                <th>Order ID</th>
                <td>{{ str_pad($orderDetails['id'], 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Customer Name</th>
                <td>{{ $orderDetails['customer_name'] }}</td>
            </tr>
            <tr>
                <th>Delivery Date</th>
                <td>{{ $orderDetails['wanted_delivery_date'] }}</td>
            </tr>
            <tr>
                <th>Order Date</th>
                <td>{{ $orderDetails['created_at'] }}</td>
            </tr>
            <tr>
                <th>Special Notes</th>
                <td>{{ $orderDetails['special_notes'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- QR Code Section -->
    <div class="qr-code">
        <h4>Scan to View Customer Order</h4>
        <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" 
             alt="QR Code for Customer Order {{ $orderDetails['id'] }}">
        <div style="margin-top: 10px;">
            <a href="{{ $qrCodeData }}" 
               style="font-size: 12px; color: #3490dc; text-decoration: none;">
                View Order Online
            </a>
        </div>
    </div>

    <!-- Items Section -->
    <div class="items" style="clear: both;">
        <h2>Order Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Variation</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderItems as $item)
                    @if(!$item->is_variation)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>-</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->price, 2) }}</td>
                            <td>{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        @if($orderItems->where('is_variation', true)->count())
        <h2>Variation Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Parent Item</th>
                    <th>Variation</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderItems as $item)
                    @if($item->is_variation)
                        <tr class="bg-blue-50 font-medium">
                            <td colspan="5">{{ $item->item_name }}</td>
                        </tr>
                        @foreach($item->variationItems as $variation)
                        <tr>
                            <td></td>
                            <td>{{ $variation->variation_name }}</td>
                            <td>{{ $variation->quantity }}</td>
                            <td>{{ number_format($variation->price, 2) }}</td>
                            <td>{{ number_format($variation->total, 2) }}</td>
                        </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        @endif

        <table>
            <tfoot>
                <tr>
                    <th colspan="4" style="text-align: right;">Grand Total</th>
                    <th>{{ number_format($orderDetails['grand_total'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

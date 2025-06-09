<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header, .footer {
            text-align: center;
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
        .qr-code {
            float: right;
            text-align: center;
        }
        .qr-code img {
            width: 100px;
            height: 100px;
            border: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
    </div>

    <!-- First Section: Sample Order Details -->
    <div class="details">
        <h2>Sample Order Details</h2>
        <table>
            <tr>
                <th>Status</th>
                <td>{{ $sampleOrderDetails['status'] }}</td>
            </tr>
            <tr>
                <th>Sample Order ID</th>
                <td>{{ $sampleOrderDetails['id'] }}</td>
            </tr>
            <tr>
                <th>Customer Name</th>
                <td>{{ $sampleOrderDetails['customer_name'] }}</td>
            </tr>
            <tr>
                <th>Wanted Delivery Date</th>
                <td>{{ $sampleOrderDetails['wanted_delivery_date'] }}</td>
            </tr>
            <tr>
                <th>Created Date</th>
                <td>{{ $sampleOrderDetails['created_at'] }}</td>
            </tr>
        </table>
    </div>

    <!-- Second Section: QR Code -->
    <div class="qr-code">
        <h4>Scan to View Sample Order</h4>
        <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" 
            width="100" height="100"
            alt="QR Code for Sample Order {{ $sampleOrderDetails['id'] }}">
        <div style="margin-top: 10px;">
            <a href="{{ $qrCodeData }}" 
            style="font-size: 12px; color: #3490dc; text-decoration: none;">
            View Sample Order Online
            </a>
        </div>
    </div>

    <div class="items" style="clear: both;">
        <h2>Sample Order Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sampleOrderItems as $item)
                <tr>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>{{ number_format($item->quantity * $item->price, 2) }}</td>
                </tr>
                @if ($item->variations->isNotEmpty())
                    <tr>
                        <td colspan="4">
                            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th style="border: 1px solid #ddd; padding: 5px;">Variation Name</th>
                                        <th style="border: 1px solid #ddd; padding: 5px;">Quantity</th>
                                        <th style="border: 1px solid #ddd; padding: 5px;">Price</th>
                                        <th style="border: 1px solid #ddd; padding: 5px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($item->variations as $variation)
                                    <tr>
                                        <td style="border: 1px solid #ddd; padding: 5px;">{{ $variation->variation_name }}</td>
                                        <td style="border: 1px solid #ddd; padding: 5px;">{{ $variation->quantity }}</td>
                                        <td style="border: 1px solid #ddd; padding: 5px;">{{ number_format($variation->price, 2) }}</td>
                                        <td style="border: 1px solid #ddd; padding: 5px;">{{ number_format($variation->quantity * $variation->price, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="text-align: right;">Grand Total</th>
                    <th>{{ number_format($grandTotal, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arrival Report #{{ $registerArrival->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header, .footer {
            text-align: center;
        }
        .details, .arrival-details {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
        }
        .details th, .details td, 
        .arrival-details th, .arrival-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th, .arrival-details th {
            background-color: #f2f2f2;
        }
        .items {
            clear: both;
            margin-top: 30px;
        }
        .items h2 {
            margin-bottom: 15px;
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
            text-align: center;
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
        .page-break {
            page-break-after: always;
            clear: both;
        }
        .invoice-image {
            max-width: 200px;
            max-height: 150px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
    </div>

    <div class="arrival-details">
        <h2>Arrival Details</h2>
        <table>
            <tr>
                <th>Arrival ID</th>
                <td>#{{ str_pad($registerArrival->id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Purchase Order</th>
                <td>#{{ str_pad($registerArrival->purchase_order_id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Location</th>
                <td>{{ $registerArrival->location->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Received Date</th>
                <td>{{ $registerArrival->received_date }}</td>
            </tr>
            <tr>
                <th>Invoice Number</th>
                <td>{{ $registerArrival->invoice_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Notes</th>
                <td>{{ $registerArrival->note ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    @if($registerArrival->image_of_invoice)
    <div style="float: right; width: 48%; margin-top: 20px; text-align: center;">
        <h4>Invoice Image</h4>
        <img src="{{ storage_path('app/' . $registerArrival->image_of_invoice) }}" 
             class="invoice-image" 
             alt="Invoice Image">
    </div>
    @endif

    <div class="items" style="clear: both;">
        <h2>Arrival Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registerArrival->items as $item)
                <tr>
                    <td>{{ $item->item_id }}</td>
                    <td>{{ $item->inventoryItem->name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>{{ ucfirst($item->status) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
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
            <p>Received By</p>
            <div class="signature-line"></div>
        </div>
        <div style="flex: 1; text-align: right;">
            <p>Verified By</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
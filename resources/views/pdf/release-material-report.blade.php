<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Release Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header, .footer {
            text-align: center;
        }
        .details, .order-details {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
        }
        .details th, .details td, 
        .order-details th, .order-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th, .order-details th {
            background-color: #f2f2f2;
        }
        .section {
            clear: both;
            margin-top: 30px;
        }
        .section h2 {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .section table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .section th, .section td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .section th {
            background-color: #f2f2f2;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        .signature div {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid black;
            margin: 40px auto 10px;
            width: 80%;
        }
        .page-break {
            page-break-after: always;
            clear: both;
        }
        .notes {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #eee;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] ?? 'Company Name' }}</h1>
        <p>{{ $companyDetails['address'] ?? 'Company Address' }}</p>
        <p>Phone: {{ $companyDetails['phone'] ?? 'N/A' }} | Email: {{ $companyDetails['email'] ?? 'N/A' }}</p>
    </div>

    <div class="details">
        <h2>Release Details</h2>
        <table>
            <tr>
                <th>Release ID</th>
                <td>#{{ str_pad($releaseMaterial->id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($releaseMaterial->status) }}</td>
            </tr>
            <tr>
                <th>Cutting Station</th>
                <td>{{ $cuttingStation->name ?? 'N/A' }} (ID: {{ $cuttingStation->id ?? 'N/A' }})</td>
            </tr>
            <tr>
                <th>Created At</th>
                <td>{{ $releaseMaterial->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <th>Released By</th>
                <td>{{ $releaseMaterial->created_by ?? 'System' }}</td>
            </tr>
        </table>
    </div>

    <div class="order-details">
        <h2>Order Details</h2>
        <table>
            <tr>
                <th>Order Type</th>
                <td>{{ $orderDetails['type'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Order ID</th>
                <td>#{{ str_pad($releaseMaterial->order_id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Order Name</th>
                <td>{{ $orderDetails['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Customer</th>
                <td>{{ $orderDetails['customer'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Wanted Date</th>
                <td>{{ $orderDetails['wanted_date'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Released Materials</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Location</th>
                    <th>PO Reference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr>
                    <td>{{ $line->item->item_code ?? 'N/A' }}</td>
                    <td>{{ $line->item->name ?? 'N/A' }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>{{ number_format($line->cost, 2) }}</td>
                    <td>{{ number_format($line->quantity * $line->cost, 2) }}</td>
                    <td>{{ $line->location->name ?? 'N/A' }}</td>
                    <td>PO#{{ str_pad($line->stock->purchase_order_id ?? '0', 5, '0', STR_PAD_LEFT) }}</td>
                </tr>
                @endforeach
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="2">TOTAL</td>
                    <td>{{ $lines->sum('quantity') }}</td>
                    <td></td>
                    <td>{{ number_format($lines->sum(function($line) { return $line->quantity * $line->cost; }), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($releaseMaterial->notes)
    <div class="notes">
        <h3>Release Notes</h3>
        <p>{{ $releaseMaterial->notes }}</p>
    </div>
    @endif

    <div class="signature">
        <div>
            <p>Prepared By</p>
            <div class="signature-line"></div>
            <p>Name: _________________________</p>
            <p>Date: _________________________</p>
        </div>
        <div>
            <p>Received By</p>
            <div class="signature-line"></div>
            <p>Name: _________________________</p>
            <p>Date: _________________________</p>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>
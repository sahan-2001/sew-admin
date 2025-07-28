<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Material QC Report #{{ $materialQC->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 30px;
            color: #000;
        }
        h1 {
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        h2.section-title {
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #222;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #fafafa;
            box-shadow: 0 0 5px #eee;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            width: 30%;
            font-weight: 600;
            color: #333;
        }
        td {
            background-color: #fff;
        }
        .section {
            margin-bottom: 30px;
        }
        .company-info {
            margin-bottom: 40px;
            font-size: 13px;
            line-height: 1.4;
        }
        .company-info h2 {
            margin-bottom: 5px;
            font-size: 16px;
            color: #111;
        }
        .footer {
            font-size: 10px;
            color: #555;
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    @if(isset($companyDetails))
    <div class="company-info">
        <h2>{{ $companyDetails['name'] }}</h2>
        <div><strong>Address:</strong> {{ $companyDetails['address'] }}</div>
        <div><strong>Phone:</strong> {{ $companyDetails['phone'] }}</div>
        <div><strong>Email:</strong> {{ $companyDetails['email'] }}</div>
    </div>
    @endif

    <h1>Material QC Report #{{ $materialQC->id }}</h1>

    <div class="section">
        <h2 class="section-title">General Information</h2>
        <table>
            <tr>
                <th>Purchase Order</th>
                <td>{{ $materialQC->purchaseOrder?->id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Item ID</th>
                <td>{{ $materialQC->inventoryItem?->id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Item Name</th>
                <td>{{ $materialQC->inventoryItem?->name ?? 'N/A' }} (ID: {{ $materialQC->item_id }})</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($materialQC->status) }}</td>
            </tr>
            <tr>
                <th>Cost of Item</th>
                <td>{{ $materialQC->cost_of_item }}</td>
            </tr>
            <tr>
                <th>Register Arrival ID</th>
                <td>{{ $materialQC->registerArrival?->id ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Quantities</h2>
        <table>
            <tr>
                <th>Inspected Quantity</th>
                <td>{{ $materialQC->inspected_quantity }}</td>
            </tr>
            <tr>
                <th>Approved Quantity</th>
                <td>{{ $materialQC->approved_qty }}</td>
            </tr>
            <tr>
                <th>Returned Quantity</th>
                <td>{{ $materialQC->returned_qty }}</td>
            </tr>
            <tr>
                <th>Scrapped Quantity</th>
                <td>{{ $materialQC->scrapped_qty }}</td>
            </tr>
            <tr>
                <th>Additional Returned</th>
                <td>{{ $materialQC->add_returned }}</td>
            </tr>
            <tr>
                <th>Additional Scrap</th>
                <td>{{ $materialQC->add_scrap }}</td>
            </tr>
            <tr>
                <th>Total Returned</th>
                <td>{{ $materialQC->total_returned }}</td>
            </tr>
            <tr>
                <th>Total Scrap</th>
                <td>{{ $materialQC->total_scrap }}</td>
            </tr>
            <tr>
                <th>Available to Store</th>
                <td>{{ $materialQC->available_to_store }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Store Location</h2>
        <table>
            <tr>
                <th>Store Location ID</th>
                <td>{{ $materialQC->storeLocation?->id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Store Location Name</th>
                <td>{{ $materialQC->storeLocation?->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Inspected By</h2>
        <table>
            <tr>
                <th>Inspector ID</th>
                <td>{{ $materialQC->inspectedBy?->id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Inspector Name</th>
                <td>{{ $materialQC->inspectedBy?->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Report generated on {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>

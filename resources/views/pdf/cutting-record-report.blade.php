<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cutting Record Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header, .footer {
            text-align: center;
        }
        .details, .record-details {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
        }
        .details th, .details td, 
        .record-details th, .record-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th, .record-details th {
            background-color: #f2f2f2;
        }
        .section {
            clear: both;
            margin-top: 30px;
        }
        .section h2 {
            margin-bottom: 15px;
        }
        .section table {
            width: 100%;
            border-collapse: collapse;
        }
        .section th, .section td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .section th {
            background-color: #f2f2f2;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] ?? 'Company Name' }}</h1>
        <p>{{ $companyDetails['address'] ?? 'Company Address' }}</p>
        <p>Phone: {{ $companyDetails['phone'] ?? 'N/A' }} | Email: {{ $companyDetails['email'] ?? 'N/A' }}</p>
    </div>

    <div class="details">
        <h2>Cutting Record Details</h2>
        <table>
            <tr>
                <th>Cutting Record ID</th>
                <td>#{{ str_pad($cuttingRecord->id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Order Type</th>
                <td>{{ $cuttingRecord->order_type }}</td>
            </tr>
            <tr>
                <th>Order ID</th>
                <td>#{{ str_pad($cuttingRecord->order_id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Release Material ID</th>
                <td>{{ $cuttingRecord->release_material_id }}</td>
            </tr>
            <tr>
                <th>Cutting Station</th>
                <td>{{ $cuttingRecord->cuttingStation->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Operation Date</th>
                <td>{{ $cuttingRecord->operation_date }}</td>
            </tr>
            <tr>
                <th>Operated Time From</th>
                <td>{{ $cuttingRecord->operated_time_from }}</td>
            </tr>
            <tr>
                <th>Operated Time To</th>
                <td>{{ $cuttingRecord->operated_time_to }}</td>
            </tr>
            <tr>
                <th>Created At</th>
                <td>{{ $cuttingRecord->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        </table>
    </div>



    <div class="section">
        <h2>Order Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Type</th>
                    <th>Item ID</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderItems as $item)
                <tr>
                    <td>{{ $item->item_type }}</td>
                    <td>{{ str_pad($item->item_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $item->quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Order Variations</h2>
        <table>
            <thead>
                <tr>
                    <th>Order Item ID</th>
                    <th>Variation Type</th>
                    <th>Variation ID</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderVariations as $variation)
                <tr>
                    <td>{{ str_pad($variation->order_item_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $variation->variation_type }}</td>
                    <td>{{ str_pad($variation->variation_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $variation->quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Cutting Employees</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Pieces Cut</th>
                    <th>Supervisor ID</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr>
                    <td>{{ str_pad($employee->employee_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $employee->pieces_cut }}</td>
                    <td>{{ str_pad($employee->supervisor_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $employee->notes }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <h2>Inventory Waste Item Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Amount</th>
                    <th>Unit</th>
                    <th>Location ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($wasteRecords as $waste)
                <tr>
                    <td>{{ str_pad($waste->item_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $waste->amount }}</td>
                    <td>{{ $waste->unit }}</td>
                    <td>{{ str_pad($waste->location_id, 5, '0', STR_PAD_LEFT) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Non-Inventory Waste Item Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Amount</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nonInventoryWaste as $waste)
                <tr>
                    <td>{{ str_pad($waste->item_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $waste->amount }}</td>
                    <td>{{ $waste->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Cutting Outputs - By Products</h2>
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Amount</th>
                    <th>Unit</th>
                    <th>Location ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byProductRecords as $product)
                <tr>
                    <td>{{ str_pad($product->item_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $product->amount }}</td>
                    <td>{{ $product->unit }}</td>
                    <td>{{ str_pad($product->location_id, 5, '0', STR_PAD_LEFT) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Cutting Quality Control Records</h2>
        <table>
            <thead>
                <tr>
                    <th>QC User ID</th>
                    <th>Inspected Qty</th>
                    <th>Accepted Qty</th>
                    <th>Rejected Qty</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($qualityControls as $qc)
                <tr>
                    <td>{{ str_pad($qc->qc_user_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $qc->inspected_quantity }}</td>
                    <td>{{ $qc->accepted_quantity }}</td>
                    <td>{{ $qc->inspected_quantity - $qc->accepted_quantity }}</td>
                    <td>{{ $qc->notes }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="signature">
        <div style="flex: 1; text-align: left;">
            <p>Supervisor Signature</p>
            <div class="signature-line"></div>
        </div>
        <div style="flex: 1; text-align: right;">
            <p>QC Manager Signature</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
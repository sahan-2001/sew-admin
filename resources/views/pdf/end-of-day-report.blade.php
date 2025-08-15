<!DOCTYPE html>
<html>
<head>
    <title>End of Day Report #{{ $report->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            font-weight: bold;
            text-align: right;
        }
        .signature-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            text-align: center;
        }
        .section-title {
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .header, .footer {
            text-align: center;
        }
    </style>
</head>
<body>
    
    <div class="header">
        <h1>{{ $company['name'] }}</h1>
        <p>{{ $company['address'] }}</p>
        <p>{{ $company['contact'] }}</p>
    </div>

    <h1>End od Day Production Summary Report</h1>

    <h2>Report Details</h2>
    <table>
        <tr>
            <th>Report ID</th>
            <td>EOD-{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <th>Operation Date</th>
            <td>{{ \Carbon\Carbon::parse($report->operated_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <th>Created At</th>
            <td>{{ $report->created_at->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <th>Created By (User ID)</th>
            <td>{{ $report->created_by ?? 'System' }}</td>
        </tr>
        <tr>
            <th>Total Performance Records</th>
            <td>{{ $performanceRecords->count() }}</td>
        </tr>
        <tr>
            <th>Total Temporary Operations</th>
            <td>{{ $temporaryOperations->count() }}</td>
        </tr>
    </table>

    @if($performanceRecords->count() > 0)
        <div class="section-title">Performance Records</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Performance ID</th>
                    <th>Assigned Operation ID</th>
                    <th>Assigned Operation Line ID</th>
                    <th>Time From</th>
                    <th>Time To</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($performanceRecords as $index => $operation)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>PR-{{ str_pad($operation->enter_performance_record_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>ADO-{{ str_pad($operation->assign_daily_operation_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>ADOL-{{ str_pad($operation->operation_line_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $operation->performanceRecord->operated_time_from ?? 'N/A' }}</td>
                    <td>{{ $operation->performanceRecord->operated_time_to ?? 'N/A' }}</td>
                    <td>{{ $operation->performanceRecord->status ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($temporaryOperations->count() > 0)
        <div class="section-title">Temporary Operations</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Temporary Operation ID</th>
                    <th>Order Type</th>
                    <th>Order ID</th>
                    <th>Production Line</th>
                    <th>Workstation</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($temporaryOperations as $index => $operation)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>TO-{{ str_pad($operation->temporary_operation_id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $operation->temporaryOperation->order_type ?? 'N/A' }}</td>
                    <td>{{ $operation->temporaryOperation->order_id ?? 'N/A' }}</td>
                    <td>{{ $operation->temporaryOperation->productionLine->name ?? 'N/A' }}</td>
                    <td>{{ $operation->temporaryOperation->workstation->name ?? 'N/A' }}</td>
                    <td>{{ $operation->temporaryOperation->status ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="signature-area">
        <div class="signature-line">Verified By</div>
        <div class="signature-line">Approved By</div>
    </div>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} | {{ config('app.name') }}
    </div>
</body>
</html>
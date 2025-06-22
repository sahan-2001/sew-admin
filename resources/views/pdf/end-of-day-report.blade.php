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
        /* Optional: Keep signature area styling */
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
    </style>
</head>
<body>
    <h1>END OF DAY PRODUCTION REPORT</h1>
    <p><strong>Company:</strong> {{ $company['name'] }}</p>
    <p><strong>Address:</strong> {{ $company['address'] }}</p>
    <p><strong>Contact:</strong> {{ $company['contact'] }}</p>

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
            <th>Total no of Operations</th>
            <td>{{ $report->recorded_operations_count }}</td>
        </tr>
    </table>

    <h2>Recorded Operations</h2>
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
            @foreach($operations as $index => $operation)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>PR-{{ str_pad($operation->enter_performance_record_id, 5, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $operation->assign_daily_operation_id}}</td>
                <td>{{ $operation->operation_line_id}}</td>
                <td>{{ $operation->performanceRecord->operated_time_from ?? 'N/A' }}</td>
                <td>{{ $operation->performanceRecord->operated_time_to ?? 'N/A' }}</td>
                <td>{{ $operation->performanceRecord->status ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-area">
        <div class="signature-line">Verified By</div>
        <div class="signature-line">Approved By</div>
    </div>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} | {{ config('app.name') }}
    </div>
</body>
</html>

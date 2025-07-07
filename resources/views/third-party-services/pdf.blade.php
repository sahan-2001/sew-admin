<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Third Party Service Report - {{ str_pad($service->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
            color: #333;
        }
        h1, h2, h3 {
            margin: 0;
            padding: 0;
            font-weight: normal;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f7f7f7;
        }
        .label-cell {
            width: 40%;
            font-weight: bold;
            background: #f9f9f9;
        }
        .value-cell {
            width: 60%;
        }
        .section {
            margin-bottom: 30px;
        }
        /* Add any additional styling you want here */
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
        <h2>Third Party Service Report</h2>
        <p><strong>Service ID:</strong> {{ str_pad($service->id, 5, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="section">
        <h3>Service Details</h3>
        <table>
            <tr>
                <td class="label-cell">Supplier Name</td>
                <td class="value-cell">{{ $service->supplier->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Service Name</td>
                <td class="value-cell">{{ $service->name }}</td>
            </tr>
            <tr>
                <td class="label-cell">Service Total (LKR)</td>
                <td class="value-cell">{{ number_format($service->service_total, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Paid Amount (LKR)</td>
                <td class="value-cell">{{ number_format($service->paid, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Remaining Balance (LKR)</td>
                <td class="value-cell">{{ number_format($service->remaining_balance, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Status</td>
                <td class="value-cell">{{ ucfirst($service->status) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Service Processes</h3>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Related Table</th>
                    <th>Related Record ID</th>
                    <th>Unit</th>
                    <th>Amount</th>
                    <th>Used Amount</th>
                    <th>Remaining Amount</th>
                    <th>Unit Rate (LKR)</th>
                    <th>Total (LKR)</th>
                    <th>Payable Balance (LKR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($service->processes as $process)
                    <tr>
                        <td>{{ $process->description }}</td>
                        <td>{{ $process->related_table }}</td>
                        <td>{{ $process->related_record_id }}</td>
                        <td>{{ $process->unit_of_measurement }}</td>
                        <td>{{ number_format($process->amount, 2) }}</td>
                        <td>{{ number_format($process->used_amount, 2) }}</td>
                        <td>{{ number_format($process->remaining_amount, 2) }}</td>
                        <td>{{ number_format($process->unit_rate, 2) }}</td>
                        <td>{{ number_format($process->total, 2) }}</td>
                        <td>{{ number_format($process->payable_balance, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;">No processes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Payment History</h3>
        <table>
            <thead>
                <tr>
                    <th>Paid Amount (LKR)</th>
                    <th>Remaining Balance (LKR)</th>
                    <th>Paid Via</th>
                    <th>Reference</th>
                    <th>Remarks</th>
                    <th>Paid At</th>
                    <th>Paid By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ number_format($payment->paid_amount, 2) }}</td>
                        <td>{{ number_format($payment->remaining_balance, 2) }}</td>
                        <td>{{ $payment->paid_via }}</td>
                        <td>{{ $payment->reference ?? '-' }}</td>
                        <td>{{ $payment->remarks ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $payment->created_by }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">No payments recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>No signature section needed as this is a generated report by the system</p>
    </div>
</body>
</html>

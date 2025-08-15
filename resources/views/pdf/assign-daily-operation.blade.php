<!DOCTYPE html>
<html>
<head>
    <title>Daily Operation #{{ $operation->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .company-contact {
            font-size: 12px;
            color: #666;
        }
        .report-title {
            text-align: center;
            margin: 20px 0;
            font-size: 20px;
            font-weight: bold;
        }
        .operation-info {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .time-metrics {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }
        .time-metric {
            width: 23%;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .time-metric-label {
            font-weight: bold;
        }
        .signature-area {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin: 10px 0;
        }
        .footer {
            margin-top: 50px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        .section-title {
            margin: 30px 0 10px;
            font-size: 16px;
            font-weight: bold;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $companyDetails['name'] }}</div>
        <div class="company-address">{{ $companyDetails['address'] }}</div>
        <div class="company-contact">{{ $companyDetails['contact'] }}</div>
    </div>

    <div class="report-title">DAILY OPERATION REPORT</div>

    <div class="operation-info">
        <table>
            <tr>
                <th width="30%">Operation ID</th>
                <td>OP-{{ str_pad($operation->id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Order Type</th>
                <td>{{ $operation->order_type }}</td>
            </tr>
            <tr>
                <th>Order ID</th>
                <td>{{ $operation->order_id }}</td>
            </tr>
            <tr>
                <th>Operation Date</th>
                <td>{{ $operation->operation_date}}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($operation->status) }}</td>
            </tr>
            <tr>
                <th>Created By</th>
                <td>{{ $operation->created_by }}</td>
            </tr>
            <tr>
                <th>Created At</th>
                <td>{{ $operation->created_at->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <th>Total Operation Lines</th>
                <td>{{ $operation->lines->count() }}</td>
            </tr>
        </table>
    </div>

    @foreach($operation->lines as $line)

    <h3 style="margin-top: 40px; margin-bottom: 10px; color: #2a2a2a;">
        Line Section: {{ str_pad($line->id, 5, '0', STR_PAD_LEFT) }}
    </h3>

    <table>
        <tr>
            <th>Line ID</th>
            <td>{{ $line->id }}</td>
        </tr>
        <tr>
            <th>Production Line</th>
            <td>{{ $line->productionLine->id ?? 'N/A' }} - {{ $line->productionLine->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Workstation</th>
            <td>{{ $line->workstation->id ?? 'N/A' }} - {{ $line->workstation->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Operation</th>
            <td>{{ $line->operation->id ?? 'N/A' }} - {{ $line->operation->description ?? 'N/A' }}</td>
        </tr>
    </table>

    @if($line->assignedEmployees->count() > 0)
        <p><strong>Assigned Employees:</strong> {{ $line->assignedEmployees->count() }}</p>
        <table>
            <thead>
                <tr>
                    <th colspan="3">Assigned Employees</th>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Employee ID</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line->assignedEmployees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $employee->id }}</td>
                    <td>{{ $employee->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><em>No assigned employees.</em></p>
    @endif

    @if($line->assignedSupervisors->count() > 0)
        <p><strong>Assigned Supervisors:</strong> {{ $line->assignedSupervisors->count() }}</p>
        <table>
            <thead>
                <tr>
                    <th colspan="3">Assigned Supervisors</th>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Supervisor ID</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line->assignedSupervisors as $index => $supervisor)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $supervisor->id }}</td>
                    <td>{{ $supervisor->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><em>No assigned supervisors.</em></p>
    @endif

    @if($line->assignedProductionMachines->count() > 0)
        <p><strong>Assigned Machines:</strong> {{ $line->assignedProductionMachines->count() }}</p>
        <table>
            <thead>
                <tr>
                    <th colspan="3">Assigned Machines</th>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Machine Name</th>
                    <th>Machine Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line->assignedProductionMachines as $index => $machine)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $machine->machine_name ?? 'N/A' }}</td>
                    <td>{{ $machine->machine_code ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><em>No assigned production machines.</em></p>
    @endif

    @if($line->assignedThirdPartyServices->count() > 0)
        <p><strong>Assigned Third-Party Services:</strong> {{ $line->assignedThirdPartyServices->count() }}</p>
        <table>
            <thead>
                <tr>
                    <th colspan="4">Assigned Third-Party Services</th>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Service ID</th>
                    <th>Service Name</th>
                    <th>Provider Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line->assignedThirdPartyServices as $index => $service)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $service->id }}</td>
                    <td>{{ $service->service_name ?? 'N/A' }}</td>
                    <td>{{ $service->provider->name ?? 'N/A' }}</td> {{-- Assumes 'provider' relation in model --}}
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><em>No assigned third-party services.</em></p>
    @endif


    @if($operation->labels->count() > 0)
        <div class="section-title">Associated Labels for Line {{ str_pad($line->id, 5, '0', STR_PAD_LEFT) }}</div>
        <p><strong>Total Labels:</strong> {{ $operation->labels->count() }}</p>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cutting Label ID</th>
                    <th>Barcode ID</th>
                    <th>Current Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($operation->labels as $index => $label)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $label->id }}</td>
                    <td>{{ $label->barcode_id }}</td>
                    <td>{{ ucfirst($label->status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Separator --}}
    <hr style="margin: 40px 0; border: 1px dashed #ccc;">

    @endforeach

    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>Prepared By</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>Approved By</div>
        </div>
    </div>


    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} | {{ config('app.name') }}
    </div>
</body>
</html>

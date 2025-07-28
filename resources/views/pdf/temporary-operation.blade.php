<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Temporary Operation #{{ $operation->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 30px;
            color: #000;
        }

        h1, h2, h3 {
            margin: 0 0 8px;
        }

        .section {
            margin-bottom: 25px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .info-table th, .info-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
        }

        .info-table th {
            background-color: #f0f0f0;
        }

        .title-bar {
            background-color: #444;
            color: #fff;
            padding: 8px 12px;
            margin-bottom: 10px;
        }

        .small-text {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>

    {{-- Company Header --}}
    @if(isset($companyDetails))
        <div class="section">
            <h2>{{ $companyDetails['name'] }}</h2>
            <div><strong>Address:</strong> {{ $companyDetails['address'] }}</div>
            <div><strong>Phone:</strong> {{ $companyDetails['phone'] }}</div>
            <div><strong>Email:</strong> {{ $companyDetails['email'] }}</div>
        </div>
    @endif

    {{-- Document Title --}}
    <div class="section">
        <div class="title-bar">
            <h1>Temporary Operation Report #{{ $operation->id }}</h1>
        </div>
    </div>

    {{-- Operation Info --}}
    <div class="section">
        <h3>Operation Information</h3>
        <table class="info-table">
            <tr><th>Description</th><td>{{ $operation->description }}</td></tr>
            <tr><th>Status</th><td>{{ ucfirst($operation->status) }}</td></tr>
            <tr><th>Operation Date</th><td>{{ $operation->operation_date }}</td></tr>
            <tr><th>Production Line</th><td>{{ $operation->productionLine?->id }} - {{ $operation->productionLine?->name ?? 'N/A' }}</td></tr>
            <tr><th>Workstation</th><td>{{ $operation->workstation?->id }} - {{ $operation->workstation?->name ?? 'N/A' }}</td></tr>
        </table>
    </div>

    {{-- Machine & Labor Times --}}
    <div class="section">
        <h3>Machine & Labor Performance Times</h3>
        <table class="info-table">
            <tr><th>Machine Setup Time</th><td>{{ $operation->machine_setup_time }}</td></tr>
            <tr><th>Machine Run Time</th><td>{{ $operation->machine_run_time }}</td></tr>
            <tr><th>Labor Setup Time</th><td>{{ $operation->labor_setup_time }}</td></tr>
            <tr><th>Labor Run Time</th><td>{{ $operation->labor_run_time }}</td></tr>
        </table>
    </div>

    {{-- Employees --}}
    @if($operation->employees->count())
        <div class="section">
            <h3>Assigned Employees</h3>
            <table class="info-table">
                <tr><th>ID</th><th>Name</th></tr>
                @foreach($operation->employees as $employee)
                    <tr><td>{{ $employee->id }}</td><td>{{ $employee->name }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- Supervisors --}}
    @if($operation->supervisors->count())
        <div class="section">
            <h3>Supervisors</h3>
            <table class="info-table">
                <tr><th>ID</th><th>Name</th></tr>
                @foreach($operation->supervisors as $supervisor)
                    <tr><td>{{ $supervisor->id }}</td><td>{{ $supervisor->name }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- Machines --}}
    @if($operation->productionMachines->count())
        <div class="section">
            <h3>Automated Production Machines for Temporary Operation</h3>
            <table class="info-table">
                <tr><th>ID</th><th>Name</th></tr>
                @foreach($operation->productionMachines as $machine)
                    <tr><td>{{ $machine->id }}</td><td>{{ $machine->name }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- Services --}}
    @if($operation->services->count())
        <div class="section">
            <h3>Associated Third Party Services</h3>
            <table class="info-table">
                <tr><th>ID</th><th>Name</th></tr>
                @foreach($operation->services as $service)
                    <tr><td>{{ $service->id }}</td><td>{{ $service->name }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="small-text">
        Generated on {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Production Machine Report - {{ str_pad($machineDetails['id'], 5, '0', STR_PAD_LEFT) }}</title>
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
        .company-details p {
            margin: 2px 0;
        }
        .section {
            margin-bottom: 30px;
            clear: both;
            width: 100%; /* full width */
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
        /* Remove two-columns float and width */
        .two-columns {
            width: 100% !important;
            float: none !important;
            margin-right: 0 !important;
        }
        .signature {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature div {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px dotted black;
            margin-top: 40px;
            margin-bottom: 10px;
        }
        .clear {
            clear: both;
        }
        .label-cell {
            width: 40%;
            font-weight: bold;
            background: #f9f9f9;
        }
        .value-cell {
            width: 60%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
        <h2>Production Machine Report</h2>
        <p><strong>Machine ID:</strong> {{ str_pad($machineDetails['id'], 5, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="section two-columns">
        <h3>Basic Details</h3>
        <table>
            <tr>
                <td class="label-cell">Name</td>
                <td class="value-cell">{{ $machineDetails['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Description</td>
                <td class="value-cell">{{ $machineDetails['description'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Purchased Date</td>
                <td class="value-cell">{{ optional(\Carbon\Carbon::parse($machineDetails['purchased_date']))->format('Y-m-d') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Start Working Date</td>
                <td class="value-cell">{{ optional(\Carbon\Carbon::parse($machineDetails['start_working_date']))->format('Y-m-d') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Expected Lifetime (Years)</td>
                <td class="value-cell">{{ $machineDetails['expected_lifetime'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section two-columns">
        <h3>Cost & Depreciation</h3>
        <table>
            <tr>
                <td class="label-cell">Purchased Cost (LKR)</td>
                <td class="value-cell">{{ number_format($machineDetails['purchased_cost'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Additional Cost (LKR)</td>
                <td class="value-cell">{{ number_format($machineDetails['additional_cost'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Additional Cost Description</td>
                <td class="value-cell">{{ $machineDetails['additional_cost_description'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Total Initial Cost (LKR)</td>
                <td class="value-cell">{{ number_format($machineDetails['total_initial_cost'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Depreciation Rate</td>
                <td class="value-cell">{{ $machineDetails['depreciation_rate'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Depreciation Calculated From</td>
                <td class="value-cell">{{ ucfirst(str_replace('_', ' ', $machineDetails['depreciation_calculated_from'] ?? 'N/A')) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Last Depreciation Calculated Date</td>
                <td class="value-cell">{{ optional(\Carbon\Carbon::parse($machineDetails['last_depreciation_calculated_date']))->format('Y-m-d') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Last Depreciation Amount (LKR)</td>
                <td class="value-cell">{{ number_format($machineDetails['depreciation_last'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Cumulative Depreciation (LKR)</td>
                <td class="value-cell">{{ number_format($machineDetails['cumulative_depreciation'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Net Present Value (LKR)</td>
                <td class="value-cell">{{ number_format($machineDetails['net_present_value'] ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="clear"></div>

    <div style="text-align: center; font-size: 10px; color: #666; margin-top: 40px;">
        No signature section needed as this is a generated report by the system.
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

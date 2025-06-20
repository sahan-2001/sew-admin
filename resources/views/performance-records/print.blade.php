{{-- filepath: resources/views/performance-records/print.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performance Record Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 25mm 15mm 20mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            margin-bottom: 0;
        }
        .company-info {
            font-size: 13px;
            margin-bottom: 10px;
        }
        h2 {
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 18px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 7px 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            font-size: 13px;
        }
        .signature div {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px dotted #222;
            margin: 18px auto 0 auto;
            width: 80%;
            height: 1px;
        }
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] ?? 'Company Name' }}</h1>
        <div class="company-info">
            {{ $companyDetails['address'] ?? '' }}<br>
            Phone: {{ $companyDetails['phone'] ?? '' }} | Email: {{ $companyDetails['email'] ?? '' }}
        </div>
        <strong>Performance Record Report - Internal Document</strong>
    </div>

    <h2>Performance Record Details</h2>
    <table>
        <tr><th>Performance Record ID</th><td>{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><th>Status</th><td>{{ $record->status }}</td></tr>
        <tr><th>Assigned Operation ID</th><td>{{ $record->assign_daily_operation_id }}</td></tr>
        <tr><th>Assigned Operation Line ID</th><td>{{ $record->assign_daily_operation_line_id }}</td></tr>
        <tr><th>Operation Date</th><td>{{ $record->operation_date }}</td></tr>
        <tr><th>Operated Time From</th><td>{{ $record->operated_time_from }}</td></tr>
        <tr><th>Operated Time To</th><td>{{ $record->operated_time_to }}</td></tr>
        <tr><th>Actual Machine Setup Time</th><td>{{ $record->actual_machine_setup_time }}</td></tr>
        <tr><th>Actual Machine Run Time</th><td>{{ $record->actual_machine_run_time }}</td></tr>
        <tr><th>Actual Employee Setup Time</th><td>{{ $record->actual_employee_setup_time }}</td></tr>
        <tr><th>Actual Employee Run Time</th><td>{{ $record->actual_employee_run_time }}</td></tr>
        <tr><th>Created By</th><td>{{ $record->created_by }}</td></tr>
    </table>

    <h2>Employee Performances</h2>
    <table>
        <tr>
            <th>Employee ID</th>
            <th>Production</th>
            <th>Downtime</th>
        </tr>
        @foreach($record->employeePerformances as $emp)
            <tr>
                <td>{{ $emp->employee_id }}</td>
                <td>{{ $emp->emp_production }}</td>
                <td>{{ $emp->emp_downtime }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Machine Performances</h2>
    <table>
        <tr>
            <th>Machine ID</th>
            <th>Downtime</th>
            <th>Notes</th>
        </tr>
        @foreach($record->machinePerformances as $machine)
            <tr>
                <td>{{ $machine->machine_id }}</td>
                <td>{{ $machine->machine_downtime }}</td>
                <td>{{ $machine->machine_notes }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Supervisor Performances</h2>
    <table>
        <tr>
            <th>Supervisor ID</th>
            <th>Accepted Qty</th>
            <th>Rejected Qty</th>
            <th>Supervisored Qty</th>
            <th>Downtime</th>
            <th>Notes</th>
        </tr>
        @foreach($record->supervisorPerformances as $sup)
            <tr>
                <td>{{ $sup->supervisor_id }}</td>
                <td>{{ $sup->accepted_qty }}</td>
                <td>{{ $sup->rejected_qty }}</td>
                <td>{{ $sup->supervisored_qty }}</td>
                <td>{{ $sup->sup_downtime }}</td>
                <td>{{ $sup->sup_notes }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Service Performances</h2>
    <table>
        <tr>
            <th>Service ID</th>
            <th>Service Process ID</th>
            <th>Used Amount</th>
            <th>Unit Rate</th>
            <th>Total Cost</th>
        </tr>
        @foreach($record->servicePerformances as $service)
            <tr>
                <td>{{ $service->service_id }}</td>
                <td>{{ $service->service_process_id }}</td>
                <td>{{ $service->used_amount }}</td>
                <td>{{ $service->unit_rate }}</td>
                <td>{{ $service->total_cost }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Inventory Waste Performances</h2>
    <table>
        <tr>
            <th>Waste</th>
            <th>UOM</th>
            <th>Item ID</th>
            <th>Location ID</th>
        </tr>
        @foreach($record->invWastePerformances as $waste)
            <tr>
                <td>{{ $waste->waste }}</td>
                <td>{{ $waste->uom }}</td>
                <td>{{ $waste->item_id }}</td>
                <td>{{ $waste->location_id }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Non-Inventory Waste Performances</h2>
    <table>
        <tr>
            <th>Amount</th>
            <th>Item ID</th>
            <th>UOM</th>
        </tr>
        @foreach($record->nonInvPerformances as $noninv)
            <tr>
                <td>{{ $noninv->amount }}</td>
                <td>{{ $noninv->item_id }}</td>
                <td>{{ $noninv->uom }}</td>
            </tr>
        @endforeach
    </table>

    <h2>By Products Performances</h2>
    <table>
        <tr>
            <th>Amount</th>
            <th>Item ID</th>
            <th>Location ID</th>
            <th>UOM</th>
        </tr>
        @foreach($record->byProductsPerformances as $byprod)
            <tr>
                <td>{{ $byprod->amount }}</td>
                <td>{{ $byprod->item_id }}</td>
                <td>{{ $byprod->location_id }}</td>
                <td>{{ $byprod->uom }}</td>
            </tr>
        @endforeach
    </table>

    <h2>QC Performances</h2>
    <table>
        <tr>
            <th>Passed Items</th>
            <th>Failed Items</th>
            <th>Action For failed items</th>
            <th>Cutting Station ID</th>
            <th>Operation Line ID</th>
        </tr>
        @foreach($record->qcPerformances as $qc)
            <tr>
                <td>{{ $qc->no_of_passed_items }}</td>
                <td>{{ $qc->no_of_failed_items }}</td>
                <td>{{ $qc->action_type }}</td>
                <td>{{ $qc->cutting_station_id ?? '' }}</td>
                <td>{{ $qc->assign_operation_line_id ?? '' }}</td>
            </tr>
        @endforeach
    </table>

    <div style="page-break-before: always;"></div>

    <h2>Employee Label Performances</h2>
    <table>
        <tr>
            <th>Cutting Label ID</th>
            <th>Barcode ID</th>
            <th>Employee ID</th>
        </tr>
        @foreach($record->employeeLabelPerformances as $elabel)
            <tr>
                <td>{{ $elabel->cutting_label_id }}</td>
                <td>{{ $elabel->label->barcode_id ?? '-' }}</td>
                <td>{{ $elabel->employee_id ?? '-' }}</td>
            </tr>
        @endforeach
    </table>

    <div style="page-break-before: always;"></div>

    <h2>Machine Label Performances</h2>
    <table>
        <tr>
            <th>Cutting Label ID</th>
            <th>Barcode ID</th>
            <th>Machine ID</th>
        </tr>
        @foreach($record->machineLabelPerformances as $mlabel)
            <tr>
                <td>{{ $mlabel->cutting_label_id }}</td>
                <td>{{ $mlabel->label->barcode_id ?? '-' }}</td>
                <td>{{ $mlabel->machine_id ?? '-' }}</td>
            </tr>
        @endforeach
    </table>

    <div style="page-break-before: always;"></div>

    <h2>QC Label Performances</h2>
    <table>
        <tr>
            <th>Cutting Label ID</th>
            <th>Barcode ID</th>
            <th>Result</th>
        </tr>
        @foreach($record->qcLabelPerformances as $qlabel)
            <tr>
                <td>{{ $qlabel->cutting_label_id }}</td>
                <td>{{ $qlabel->label->barcode_id ?? '-' }}</td>
                <td>{{ $qlabel->result }}</td>
            </tr>
        @endforeach
    </table>

    <div class="signature">
        <div>
            <p>Authorized Signature</p>
            <div class="signature-line"></div>
        </div>
        <div>
            <p>Received By</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
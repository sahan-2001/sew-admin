<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Record #{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes blink {
            50% { opacity: 0.5; }
        }
        .animate-blink {
            animation: blink 1s infinite;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
            .collapsible-content {
                display: block !important;
            }
        }
        .section-card {
            @apply bg-white rounded-lg shadow-sm p-6 mb-6 border border-gray-100;
            transition: all 0.3s ease;
        }
        .section-card:hover {
            @apply shadow-md;
        }
        .section-title {
            @apply text-xl font-semibold mb-4 pb-2 border-b border-gray-200 flex items-center justify-between;
        }
        .info-grid {
            @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4;
        }
        .info-item {
            @apply flex items-start;
        }
        .info-label {
            @apply font-medium text-gray-600 w-1/3 md:w-1/4;
        }
        .info-value {
            @apply flex-1 text-gray-800;
        }
        .table-container {
            @apply overflow-x-auto;
        }
        .data-table {
            @apply w-full border-collapse mt-4;
        }
        .data-table th {
            @apply bg-gray-50 text-left px-4 py-3 border-b border-gray-200 font-semibold text-gray-600;
        }
        .data-table td {
            @apply px-4 py-3 border-b border-gray-200 text-gray-700;
        }
        .data-table tr:last-child td {
            @apply border-b-0;
        }
        .collapsible-header {
            @apply cursor-pointer flex items-center justify-between;
        }
        .collapsible-content {
            @apply overflow-hidden transition-all duration-300 ease-in-out;
        }
        .badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        .badge-blue {
            @apply bg-blue-100 text-blue-800;
        }
        .badge-green {
            @apply bg-green-100 text-green-800;
        }
        .badge-red {
            @apply bg-red-100 text-red-800;
        }
        .badge-yellow {
            @apply bg-yellow-100 text-yellow-800;
        }
        .badge-gray {
            @apply bg-gray-100 text-gray-800;
        }
    </style>
</head>
<body class="bg-gray-50 p-4 md:p-8">
    <!-- Header and Action Buttons -->
    <div class="no-print flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Performance Record Details</h1>
            <p class="text-gray-600">ID: {{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }} | Created: {{ $record->created_at->format('M d, Y H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="window.close()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition flex items-center gap-2">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>

    <!-- Status Indicator -->
    <div class="section-card">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-lg font-semibold animate-blink whitespace-nowrap">
                    Status: 
                </span>
                @php
                    $status = $record->status;
                    $badgeClass = match($status) {
                        'paused' => 'badge-gray',
                        'planned' => 'badge-blue',
                        'released' => 'badge-yellow',
                        'partially completed' => 'badge-yellow',
                        'completed' => 'badge-green',
                        default => 'badge-gray'
                    };
                @endphp
                <span class="badge {{ $badgeClass }} text-sm">
                    {{ ucfirst($status) }}
                </span>
            </div>

            @php
                $progressValue = match($status) {
                    'paused' => 0,
                    'planned' => 25,
                    'released' => 50,
                    'partially completed' => 75,
                    'completed' => 100,
                    default => 0
                };

                $color = match($status) {
                    'paused' => 'bg-gray-300',
                    'planned' => 'bg-blue-500',
                    'released' => 'bg-yellow-500',
                    'partially completed' => 'bg-orange-500',
                    'completed' => 'bg-green-500',
                    default => 'bg-gray-400'
                };
            @endphp

            <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 ease-in-out {{ $color }}" 
                     style="width: {{ $progressValue }}%;">
                </div>
            </div>
            <span class="text-lg font-semibold">{{ $progressValue }}%</span>
        </div>
    </div>

    <!-- Basic Information Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-info-circle mr-2 text-blue-500"></i> Basic Information</span>
        </h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Record ID:</span>
                <span class="info-value font-mono">{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="badge {{ $badgeClass }}">
                        {{ ucfirst($status) }}
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Assigned Operation:</span>
                <span class="info-value">{{ $record->assign_daily_operation_id }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Operation Line:</span>
                <span class="info-value">{{ $record->assign_daily_operation_line_id }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Operation Date:</span>
                <span class="info-value">{{ $record->operation_date }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Time Range:</span>
                <span class="info-value">
                    <i class="far fa-clock text-gray-400 mr-1"></i>
                    {{ $record->operated_time_from }} - {{ $record->operated_time_to }}
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Machine Setup:</span>
                <span class="info-value">{{ $record->actual_machine_setup_time }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Machine Run:</span>
                <span class="info-value">{{ $record->actual_machine_run_time }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Employee Setup:</span>
                <span class="info-value">{{ $record->actual_employee_setup_time }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Employee Run:</span>
                <span class="info-value">{{ $record->actual_employee_run_time }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Created By:</span>
                <span class="info-value">{{ $record->created_by }}</span>
            </div>
        </div>
    </div>

    <!-- Employee Performances Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-users mr-2 text-blue-500"></i> Employee Performances</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->employeePerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Production</th>
                        <th>Downtime</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->employeePerformances as $emp)
                        <tr>
                            <td class="font-medium">{{ $emp->employee_id }}</td>
                            <td>{{ $emp->emp_production }}</td>
                            <td>{{ $emp->emp_downtime }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No employee performance records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Machine Performances Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-cogs mr-2 text-blue-500"></i> Machine Performances</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->machinePerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Machine ID</th>
                        <th>Downtime</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->machinePerformances as $machine)
                        <tr>
                            <td class="font-medium">{{ $machine->machine_id }}</td>
                            <td>{{ $machine->machine_downtime }}</td>
                            <td>{{ $machine->machine_notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No machine performance records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Supervisor Performances Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-user-shield mr-2 text-blue-500"></i> Supervisor Performances</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->supervisorPerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supervisor ID</th>
                        <th>Accepted Qty</th>
                        <th>Rejected Qty</th>
                        <th>Supervisored Qty</th>
                        <th>Downtime</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->supervisorPerformances as $sup)
                        <tr>
                            <td class="font-medium">{{ $sup->supervisor_id }}</td>
                            <td class="text-green-600">{{ $sup->accepted_qty }}</td>
                            <td class="text-red-600">{{ $sup->rejected_qty }}</td>
                            <td>{{ $sup->supervisored_qty }}</td>
                            <td>{{ $sup->sup_downtime }}</td>
                            <td>{{ $sup->sup_notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No supervisor performance records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Service Performances Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-concierge-bell mr-2 text-blue-500"></i> Service Performances</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->servicePerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Service ID</th>
                        <th>Process ID</th>
                        <th>Used Amount</th>
                        <th>Unit Rate</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->servicePerformances as $service)
                        <tr>
                            <td class="font-medium">{{ $service->service_id }}</td>
                            <td>{{ $service->service_process_id }}</td>
                            <td>{{ $service->used_amount }}</td>
                            <td>{{ $service->unit_rate }}</td>
                            <td class="font-medium">{{ $service->total_cost }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No service performance records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inventory Waste Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-trash-alt mr-2 text-blue-500"></i> Inventory Waste</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->invWastePerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waste</th>
                        <th>UOM</th>
                        <th>Item ID</th>
                        <th>Location ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->invWastePerformances as $waste)
                        <tr>
                            <td class="font-medium">{{ $waste->waste }}</td>
                            <td>{{ $waste->uom }}</td>
                            <td>{{ $waste->item_id }}</td>
                            <td>{{ $waste->location_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No inventory waste records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Non-Inventory Waste Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-ban mr-2 text-blue-500"></i> Non-Inventory Waste</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->nonInvPerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Amount</th>
                        <th>Item ID</th>
                        <th>UOM</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->nonInvPerformances as $noninv)
                        <tr>
                            <td class="font-medium">{{ $noninv->amount }}</td>
                            <td>{{ $noninv->item_id }}</td>
                            <td>{{ $noninv->uom }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No non-inventory waste records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- By Products Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-boxes mr-2 text-blue-500"></i> By Products</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->byProductsPerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Amount</th>
                        <th>Item ID</th>
                        <th>Location ID</th>
                        <th>UOM</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->byProductsPerformances as $byprod)
                        <tr>
                            <td class="font-medium">{{ $byprod->amount }}</td>
                            <td>{{ $byprod->item_id }}</td>
                            <td>{{ $byprod->location_id }}</td>
                            <td>{{ $byprod->uom }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No by-products records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- QC Performances Section -->
    <div class="section-card">
        <h2 class="section-title">
            <span><i class="fas fa-clipboard-check mr-2 text-blue-500"></i> QC Performances</span>
            <span class="text-sm font-normal text-gray-500">{{ $record->qcPerformances->count() }} records</span>
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Passed Items</th>
                        <th>Failed Items</th>
                        <th>Action</th>
                        <th>Cutting Station</th>
                        <th>Operation Line</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->qcPerformances as $qc)
                        <tr>
                            <td class="text-green-600 font-medium">{{ $qc->no_of_passed_items }}</td>
                            <td class="text-red-600 font-medium">{{ $qc->no_of_failed_items }}</td>
                            <td>{{ $qc->action_type }}</td>
                            <td>{{ $qc->cutting_station_id ?? '-' }}</td>
                            <td>{{ $qc->assign_operation_line_id ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> No QC performance records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Label Performances Sections (Collapsible) -->
    <div class="section-card">
        <h2 class="section-title collapsible-header" onclick="toggleCollapse('label-section')">
            <span><i class="fas fa-tags mr-2 text-blue-500"></i> Label Performances</span>
            <span class="text-sm font-normal text-gray-500">
                <i class="fas fa-chevron-down transition-transform" id="label-section-icon"></i>
            </span>
        </h2>
        
        <div id="label-section" class="collapsible-content" style="display: none;">
            <!-- Employee Labels -->
            <div class="mb-8">
                <h3 class="font-semibold text-lg mb-3 flex items-center justify-between collapsible-header" onclick="toggleCollapse('employee-labels')">
                    <span><i class="fas fa-user-tag mr-2 text-blue-400"></i> Employee Labels</span>
                    <span class="text-sm font-normal text-gray-500">
                        {{ $record->employeeLabelPerformances->count() }} records
                        <i class="fas fa-chevron-down ml-2 transition-transform" id="employee-labels-icon"></i>
                    </span>
                </h3>
                <div id="employee-labels" class="collapsible-content" style="display: none;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Label ID</th>
                                    <th>Barcode ID</th>
                                    <th>Employee ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($record->employeeLabelPerformances as $elabel)
                                    <tr>
                                        <td class="font-medium">{{ $elabel->cutting_label_id }}</td>
                                        <td>{{ $elabel->label->barcode_id ?? '-' }}</td>
                                        <td>{{ $elabel->employee_id ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-gray-500">
                                            <i class="fas fa-info-circle mr-2"></i> No employee label records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Machine Labels -->
            <div class="mb-8">
                <h3 class="font-semibold text-lg mb-3 flex items-center justify-between collapsible-header" onclick="toggleCollapse('machine-labels')">
                    <span><i class="fas fa-tag mr-2 text-blue-400"></i> Machine Labels</span>
                    <span class="text-sm font-normal text-gray-500">
                        {{ $record->machineLabelPerformances->count() }} records
                        <i class="fas fa-chevron-down ml-2 transition-transform" id="machine-labels-icon"></i>
                    </span>
                </h3>
                <div id="machine-labels" class="collapsible-content" style="display: none;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Label ID</th>
                                    <th>Barcode ID</th>
                                    <th>Machine ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($record->machineLabelPerformances as $mlabel)
                                    <tr>
                                        <td class="font-medium">{{ $mlabel->cutting_label_id }}</td>
                                        <td>{{ $mlabel->label->barcode_id ?? '-' }}</td>
                                        <td>{{ $mlabel->machine_id ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-gray-500">
                                            <i class="fas fa-info-circle mr-2"></i> No machine label records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- QC Labels -->
            <div>
                <h3 class="font-semibold text-lg mb-3 flex items-center justify-between collapsible-header" onclick="toggleCollapse('qc-labels')">
                    <span><i class="fas fa-check-circle mr-2 text-blue-400"></i> QC Labels</span>
                    <span class="text-sm font-normal text-gray-500">
                        {{ $record->qcLabelPerformances->count() }} records
                        <i class="fas fa-chevron-down ml-2 transition-transform" id="qc-labels-icon"></i>
                    </span>
                </h3>
                <div id="qc-labels" class="collapsible-content" style="display: none;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Label ID</th>
                                    <th>Barcode ID</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($record->qcLabelPerformances as $qlabel)
                                    <tr>
                                        <td class="font-medium">{{ $qlabel->cutting_label_id }}</td>
                                        <td>{{ $qlabel->label->barcode_id ?? '-' }}</td>
                                        <td>
                                            @if($qlabel->result === 'passed')
                                                <span class="badge badge-green">Passed</span>
                                            @elseif($qlabel->result === 'failed')
                                                <span class="badge badge-red">Failed</span>
                                            @else
                                                <span class="badge badge-gray">{{ $qlabel->result ?? 'Pending' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-gray-500">
                                            <i class="fas fa-info-circle mr-2"></i> No QC label records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-resize the popup window to fit content
        window.addEventListener('load', function() {
            const width = Math.min(1200, document.body.scrollWidth + 50);
            const height = Math.min(900, document.body.scrollHeight + 100);
            
            window.resizeTo(width, height);
            window.moveTo(
                (screen.width - width) / 2,
                (screen.height - height) / 2
            );
        });

        // Collapsible sections functionality
        function toggleCollapse(sectionId) {
            const section = document.getElementById(sectionId);
            const icon = document.getElementById(`${sectionId}-icon`);
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                section.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // You can add tooltip initialization here if needed
        });
    </script>
</body>
</html>
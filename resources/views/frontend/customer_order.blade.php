<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Order #{{ $customerOrder->order_id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --accent: #f59e0b;
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
        }

        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: var(--dark-text); }

        .card { box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.12); }

        .header { background: linear-gradient(120deg, var(--primary), var(--primary-dark)); color: white; padding: 24px; position: relative; overflow: hidden; }
        .header::after { content: ''; position: absolute; top: -50%; right: -50%; width: 100%; height: 200%; background: rgba(255,255,255,0.1); transform: rotate(30deg); }

        .progress-track { display: flex; align-items: center; justify-content: space-between; position: relative; margin: 30px 0; padding: 0 20px; }
        .progress-step { display: flex; flex-direction: column; align-items: center; z-index: 2; }
        .step-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: white; border: 2px solid #e2e8f0; margin-bottom: 8px; transition: all 0.3s ease; }
        .step-active .step-icon { background-color: var(--accent); color: white; border-color: var(--accent); }
        .step-label { font-size: 0.75rem; font-weight: 500; text-align: center; color: #94a3b8; }
        .step-active .step-label { color: var(--accent); font-weight: 600; }
        .progress-track::before { content: ''; position: absolute; top: 20px; left: 0; right: 0; height: 2px; background-color: #e2e8f0; z-index: 1; }
        .progress-bar { position: absolute; top: 20px; left: 0; height: 2px; background-color: var(--accent); z-index: 1; transition: width 0.5s ease; }

        .info-card { background-color: var(--light-bg); border-radius: 10px; padding: 20px; border-left: 4px solid var(--primary); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .detail-item { display: flex; flex-direction: column; margin-bottom: 15px; }
        .detail-label { font-size: 0.85rem; color: var(--secondary); margin-bottom: 4px; }
        .detail-value { font-weight: 500; color: var(--dark-text); }

        table { border-collapse: separate; border-spacing: 0; width: 100%; }
        th { background-color: #f1f5f9; color: var(--secondary); font-weight: 600; text-align: left; padding: 14px 16px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8fafc; }

        .grand-total { background: linear-gradient(to right, var(--primary), var(--primary-dark)); color: white; border-radius: 10px; padding: 20px; margin-top: 30px; text-align: right; font-size: 1.25rem; }
        .qr-container { background: linear-gradient(to bottom, #f8fafc, #f1f5f9); border-radius: 12px; padding: 25px; text-align: center; border: 1px solid #e2e8f0; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4">

@php
    $customerOrderUrl = url("/customer-order/{$customerOrder->order_id}/{$customerOrder->random_code}");
    $allStatuses = [
        'planned' => ['icon' => 'clock', 'label' => 'Planned'],
        'released' => ['icon' => 'box', 'label' => 'Released'],
        'material_released' => ['icon' => 'cubes', 'label' => 'Material Released'],
        'cut' => ['icon' => 'scissors', 'label' => 'Cut'],
        'started' => ['icon' => 'play', 'label' => 'Started'],
        'completed' => ['icon' => 'check-circle', 'label' => 'Completed'],
        'final_qc' => ['icon' => 'clipboard-check', 'label' => 'Final QC'],
        'delivered' => ['icon' => 'truck', 'label' => 'Delivered'],
        'invoiced' => ['icon' => 'file-invoice', 'label' => 'Invoiced'],
        'closed' => ['icon' => 'lock', 'label' => 'Closed'],
    ];

    $currentStatus = $customerOrder->status;
    $statusSteps = $allStatuses;

    // Determine current index in the progress
    $statusKeys = array_keys($statusSteps);
    $statusIndex = array_search($currentStatus, $statusKeys);
    if ($statusIndex === false) $statusIndex = 0;

    // Progress bar width
    $progressValue = ($statusIndex / (count($statusSteps) - 1)) * 100;
@endphp

<div class="max-w-5xl mx-auto bg-white rounded-lg card">

    <!-- Header -->
    <div class="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center relative z-10">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">CUSTOMER ORDER</h1>
                <p class="text-blue-100 mt-2">{{ $companyDetails['name'] }}</p>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="inline-flex items-center px-4 py-2 rounded-lg border border-white text-white font-semibold bg-transparent">
                    <i class="fa-solid fa-{{ $statusSteps[$customerOrder->status]['icon'] }} mr-2"></i>
                    {{ ucfirst($customerOrder->status) }}
                </div>
            </div>
        </div>

        <!-- Progress Tracker -->
        <div class="progress-track relative z-10">
            <div class="progress-bar" style="width: {{ $progressValue }}%;"></div>
            @foreach($statusSteps as $key => $step)
                <div class="progress-step {{ $statusIndex >= array_search($key, array_keys($statusSteps)) ? 'step-active' : '' }}">
                    <div class="step-icon"><i class="fas fa-{{ $step['icon'] }}"></i></div>
                    <span class="step-label">{{ $step['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-6 md:p-8">

        <!-- Customer & Order Info -->
        <div class="info-card mb-8">
            <div class="detail-grid">
                <div>
                    <div class="detail-item">
                        <span class="detail-label">ORDER ID</span>
                        <span class="detail-value">CO-{{ str_pad($customerOrder->order_id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Customer Name</span>
                        <span class="detail-value">{{ $customerOrder->customer->name ?? 'N/A' }}</span>
                    </div>
                    @if($customerOrder->wanted_delivery_date)
                    <div class="detail-item">
                        <span class="detail-label">Requested Delivery Date</span>
                        <span class="detail-value">{{ $customerOrder->wanted_delivery_date }}</span>
                    </div>
                    @endif
                </div>
                <div>
                    <div class="detail-item">
                        <span class="detail-label">Created At</span>
                        <span class="detail-value">{{ $customerOrder->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($customerOrder->special_notes)
                    <div class="detail-item">
                        <span class="detail-label">Special Notes</span>
                        <span class="detail-value">{{ $customerOrder->special_notes }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Order Items Table -->
        <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center">
            <i class="fas fa-list-ul mr-3 text-blue-500"></i> Order Items
        </h2>

        <div class="overflow-x-auto rounded-lg border border-gray-200 mb-6">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customerOrder->orderItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="font-medium">{{ $item->item_name }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->price, 2) }}</td>
                        <td class="text-right font-medium">{{ number_format($item->calculated_total, 2) }}</td>
                    </tr>
                    @if($item->is_variation && $item->variationItems->count())
                        @foreach($item->variationItems as $variation)
                        <tr class="bg-gray-50">
                            <td></td>
                            <td class="pl-6">{{ $variation->variation_name }}</td>
                            <td class="text-center">{{ $variation->quantity }}</td>
                            <td class="text-right">{{ number_format($variation->price, 2) }}</td>
                            <td class="text-right font-medium">{{ number_format($variation->total, 2) }}</td>
                        </tr>
                        @endforeach
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Payment Summary + QR -->
        @php
            $orderTotal = $customerOrder->orderItems->sum(fn($i) => $i->calculated_total);
        @endphp
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

            <!-- QR -->
            <div class="qr-container">
                <h4 class="text-lg font-semibold mb-4 text-gray-800 flex items-center justify-center">
                    <i class="fas fa-qrcode mr-2 text-blue-500"></i> Track your order
                </h4>
                <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode($customerOrderUrl) }}&size=200x200" 
                    alt="QR Code" 
                    class="mx-auto w-40 h-40 border border-gray-300 rounded-md shadow-sm">
                <p class="mt-4 text-orange-600 font-medium">CO-{{ str_pad($customerOrder->order_id, 5, '0', STR_PAD_LEFT) }}</p>
            </div>

            <!-- Summary -->
            @php
                $remainingBalance = $customerOrder->remaining_balance ?? 0;
                $alreadyPaid = $orderTotal - $remainingBalance;
            @endphp

            <div class="grid grid-cols-1 gap-4">

                <div class="bg-blue-600 text-white rounded-lg p-4 text-right font-semibold shadow">
                    Order Total: Rs. {{ number_format($orderTotal, 2) }}
                </div>
                <div class="bg-green-500 text-white rounded-lg p-4 text-right font-semibold shadow">
                    Already Paid: Rs. {{ number_format($alreadyPaid, 2) }}
                </div>
                <div class="bg-red-500 text-white rounded-lg p-4 text-right font-semibold shadow">
                    Remaining Balance: Rs. {{ number_format($remainingBalance, 2) }}
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ url('/') }}" 
                    class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg shadow transition">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Homepage
                    </a>

                    <a href="{{ route('customer-orders.pdf', $customerOrder->order_id) }}" 
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow transition">
                        <i class="fas fa-file-pdf mr-2"></i> Download PDF
                    </a>
                </div>

            </div>

        </div>

    </div>
</div>
</body>
</html>

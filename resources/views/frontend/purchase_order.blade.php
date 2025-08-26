<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --accent: #f59e0b; /* progress bar color */
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
        }

        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: var(--dark-text); }

        .card { box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.12); }

        .header { background: linear-gradient(120deg, var(--primary), var(--primary-dark)); color: white; padding: 24px; position: relative; overflow: hidden; }
        .header::after { content: ''; position: absolute; top: -50%; right: -50%; width: 100%; height: 200%; background: rgba(255,255,255,0.1); transform: rotate(30deg); }

        .status-badge { display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }

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
    $purchaseOrderUrl = url("/purchase-order/{$purchaseOrder->id}/{$purchaseOrder->random_code}");
    $statusSteps = [
        'paused' => ['icon' => 'pause-circle', 'label' => 'Paused'],
        'planned' => ['icon' => 'calendar', 'label' => 'Planned'],
        'released' => ['icon' => 'paper-plane', 'label' => 'Released'],
        'partially_arrived' => ['icon' => 'truck-loading', 'label' => 'Partially Arrived'],
        'arrived' => ['icon' => 'truck', 'label' => 'Arrived'],
        'completed' => ['icon' => 'check-circle', 'label' => 'Completed']
    ];
    $statusIndex = array_search($purchaseOrder->status, array_keys($statusSteps));
    if ($statusIndex === false) $statusIndex = 0;
    $progressValue = match($purchaseOrder->status) {
        'paused' => 0,
        'planned' => 25,
        'released' => 50,
        'partially arrived' => 65,
        'arrived' => 75,
        'completed' => 100,
        default => 0
    };
@endphp

<div class="max-w-5xl mx-auto bg-white rounded-lg card">

    <!-- Company Header -->
    <div class="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center relative z-10">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">PURCHASE ORDER</h1>
                <p class="text-blue-100 mt-2">{{ $companyDetails['name'] }}</p>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="inline-flex items-center px-4 py-2 rounded-lg border border-white text-white font-semibold bg-transparent">
                    <i class="fa-solid fa-{{ $statusSteps[$purchaseOrder->status]['icon'] }} mr-2"></i>
                    {{ ucfirst($purchaseOrder->status) }}
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

        <!-- Order Details -->
        <div class="info-card mb-8">
            <div class="detail-grid">
                <div>
                    <div class="detail-item">
                        <span class="detail-label">ORDER ID</span>
                        <span class="detail-value">#{{ str_pad($purchaseOrder->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">PROVIDER TYPE</span>
                        <span class="detail-value">{{ $purchaseOrder['provider_type'] }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">PROVIDER</span>
                        <span class="detail-value">{{ $providerDetails['name'] }}</span>
                    </div>
                </div>
                <div>
                    <div class="detail-item">
                        <span class="detail-label">WANTED DATE</span>
                        <span class="detail-value">{{ $purchaseOrder->wanted_date }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">CREATED AT</span>
                        <span class="detail-value">{{ $purchaseOrder->created_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>
            </div>

            @if(!empty($purchaseOrder->note))
            <div class="detail-item mt-4">
                <span class="detail-label">Notes</span>
                <span class="detail-value whitespace-pre-line">{{ $purchaseOrder->special_note }}</span>
            </div>
            @endif
        </div>

        <!-- Notes Section -->
        @if(!empty($purchaseOrder->note))
        <div class="info-card mb-8">
            <h2 class="text-xl font-semibold mb-3 text-gray-800 flex items-center">
                <i class="fas fa-sticky-note mr-3 text-yellow-500"></i> Notes
            </h2>
            <p class="text-gray-700 leading-relaxed">{{ $purchaseOrder->note }}</p>
        </div>
        @endif
        
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
                        <th>Code</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="font-medium">{{ $item->inventoryItem->name ?? 'N/A' }}</td>
                        <td class="text-gray-600">{{ $item->inventoryItem->item_code ?? 'N/A' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">Rs. {{ number_format($item->price, 2) }}</td>
                        <td class="text-right font-medium">Rs. {{ number_format($item->quantity * $item->price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


        <!-- QR Code + Payment Summary -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

            <!-- QR Code Block -->
            <div class="qr-container">
                <h4 class="text-lg font-semibold mb-4 text-gray-800 flex items-center justify-center">
                    <i class="fas fa-qrcode mr-2 text-blue-500"></i> Track your order
                </h4>
                <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode($purchaseOrderUrl) }}&size=200x200" 
                    alt="QR Code" 
                    class="mx-auto w-40 h-40 border border-gray-300 rounded-md shadow-sm">
                <p class="mt-4 text-orange-600 font-medium">
                    PO#{{ str_pad($purchaseOrder->id, 5, '0', STR_PAD_LEFT) }}
                </p>
            </div>

            <!-- Payment Summary Section -->
            @php
                $grandTotal = $purchaseOrder->items->sum(fn($item) => $item->quantity * $item->price);
                $remainingBalance = $purchaseOrder->remaining_balance ?? 0; 
                $alreadyPaid = $grandTotal - $remainingBalance;
            @endphp

            <div class="grid grid-cols-1 gap-4">
                <!-- Grand Total -->
                <div class="bg-blue-600 text-white rounded-lg p-4 text-right font-semibold shadow">
                    Grand Total: Rs. {{ number_format($grandTotal, 2) }}
                </div>

                <!-- Already Paid -->
                <div class="bg-green-500 text-white rounded-lg p-4 text-right font-semibold shadow">
                    Already Paid: Rs. {{ number_format($alreadyPaid, 2) }}
                </div>

                <!-- Remaining Balance -->
                <div class="bg-red-500 text-white rounded-lg p-4 text-right font-semibold shadow">
                    Remaining Balance: Rs. {{ number_format($remainingBalance, 2) }}
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <!-- Back to Homepage -->
                    <a href="http://127.0.0.1:8000/" 
                    class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg shadow transition">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Homepage
                    </a>

                    <!-- Download PDF -->
                    <a href="{{ route('purchase-order.pdf', $purchaseOrder->id) }}" 
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

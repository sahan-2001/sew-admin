<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @keyframes blink {
            50% { opacity: 0.5; }
        }
        .animate-blink {
            animation: blink 1s infinite;
            font-weight: bold;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 40px auto 0;
        }
        .card {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #1A202C;
            color: white;
            padding: 20px;
        }
        .header h1 {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .header p {
            font-size: 1rem;
        }
        .footer {
            font-size: 0.875rem;
            color: #6B7280;
            padding: 10px 0;
        }
        .status-bar {
            background-color: #E2E8F0;
            border-radius: 8px;
            padding: 10px;
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #4299E1;
        }
        .progress-value {
            font-weight: bold;
            font-size: 1rem;
            color: #4A5568;
        }
        .qr-container {
            background-color: #F7FAFC;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        table th {
            background-color: #EDF2F7;
            color: #2D3748;
        }
        table td {
            color: #4A5568;
        }
    </style>
</head>
<body class="bg-gray-100">

    @php
        // Build the full purchase order URL with id and random_code
        $purchaseOrderUrl = url("/purchase-order/{$purchaseOrder->id}/{$purchaseOrder->random_code}");
    @endphp

    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-lg p-8 my-8 card">
        <!-- Company Header -->
        <div class="header text-center mb-8">
            <h1 class="text-2xl md:text-3xl font-bold">{{ $companyDetails['name'] }}</h1>
            <p class="text-sm md:text-base">{{ $companyDetails['address'] }}</p>
            <p class="text-sm md:text-base">Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
        </div>

        <!-- Status Section -->
        <div class="status-bar flex items-center gap-6 mb-8 p-4 rounded-md">
            <div class="text-lg font-semibold text-gray-800 animate-blink">
                {{ ucfirst($purchaseOrder->status) }}
            </div>

            <div class="w-full bg-gray-200 rounded-md overflow-hidden">
                @php
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
                <div class="progress-bar" style="width: {{ $progressValue }}%;"></div>
            </div>
            <div class="progress-value">{{ $progressValue }}%</div>
        </div>

        <!-- Order Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <p><strong>Order ID:</strong> #{{ str_pad($purchaseOrder->id, 5, '0', STR_PAD_LEFT) }}</p>
                <p><strong>Provider:</strong> {{ $purchaseOrder->provider_name }}</p>
                <p><strong>Provider Email:</strong> {{ $purchaseOrder->provider->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p><strong>Status:</strong> {{ $purchaseOrder->status }}</p>
                <p><strong>Wanted Date:</strong> {{ $purchaseOrder->wanted_date }}</p>
                <p><strong>Created At:</strong> {{ $purchaseOrder->created_at->format('Y-m-d H:i:s') }}</p>
            </div>
        </div>

        <!-- Order Items Table -->
        <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-800">Order Items</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-4 border text-left">Item</th>
                            <th class="p-4 border text-left">Code</th>
                            <th class="p-4 border text-center">Qty</th>
                            <th class="p-4 border text-right">Price</th>
                            <th class="p-4 border text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td class="p-4 border">{{ $item->inventoryItem->name ?? 'N/A' }}</td>
                            <td class="p-4 border">{{ $item->inventoryItem->item_code ?? 'N/A' }}</td>
                            <td class="p-4 border text-center">{{ $item->quantity }}</td>
                            <td class="p-4 border text-right">{{ number_format($item->price, 2) }}</td>
                            <td class="p-4 border text-right">{{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grand Total Section -->
        <div class="text-right text-xl font-bold mt-6">
            @php
                $grandTotal = $purchaseOrder->items->sum(fn($item) => $item->quantity * $item->price);
            @endphp
            <p>Grand Total: {{ number_format($grandTotal, 2) }}</p>
        </div>

        <!-- QR Code Section -->
        <div class="qr-container mb-8">
            <h4 class="text-xl md:text-2xl font-medium mb-2 text-gray-800">Track your order</h4>
            <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode($purchaseOrderUrl) }}&amp;size=200x200" 
                 alt="QR Code" 
                 class="mx-auto w-32 h-32 md:w-40 md:h-40 border border-gray-300 rounded-md shadow-md">
            <p class="mt-2 text-blue-600">PO#{{ $purchaseOrder->id }}</p>
        </div>

        <!-- Footer -->
        <div class="footer text-center">
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
            <a href="{{ route('welcome') }}" class="text-blue-600 hover:underline mt-4 block">Go to Welcome Page</a>
        </div>
    </div>

</body>
</html>

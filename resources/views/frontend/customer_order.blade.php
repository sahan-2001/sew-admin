<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Order #{{ $customerOrder->order_id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @keyframes blink { 50% { opacity: 0.5; } }
        .animate-blink { animation: blink 1s infinite; }
        .item-row { transition: background-color 0.2s; }
        .item-row:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="bg-gray-50">

<div class="max-w-5xl mx-auto bg-white rounded-lg shadow-md overflow-hidden my-8">
    <!-- Header Section -->
    <div class="bg-gray-800 text-white px-6 py-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">{{ $companyDetails['name'] }}</h1>
                <p class="text-sm opacity-90">{{ $companyDetails['address'] }}</p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-semibold">Customer Order</h2>
                <p class="text-sm">CO-{{ str_pad($customerOrder->order_id, 5, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="bg-blue-50 px-6 py-3 border-b border-gray-200">
        <div class="flex items-center gap-4">
            <span class="font-semibold {{ in_array($customerOrder->status, ['pending', 'accepted']) ? 'text-blue-600 animate-blink' : '' }}">
                Status: {{ ucfirst(str_replace('_', ' ', $customerOrder->status)) }}
            </span>
            <div class="flex-1 bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progressValue }}%"></div>
            </div>
            <span class="text-sm font-medium">{{ $progressValue }}%</span>
        </div>
    </div>

    <!-- Order Metadata -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
        <div>
            <h3 class="font-semibold text-lg mb-2">Customer Details</h3>
            <p class="mb-1"><span class="font-medium">Name:</span> {{ $customerOrder->customer->name ?? 'N/A' }}</p>
        </div>
        <div>
            <h3 class="font-semibold text-lg mb-2">Order Details</h3>
            <p class="mb-1"><span class="font-medium">Requested Delivery Date:</span> 
                {{ $customerOrder->wanted_delivery_date ?: 'Not specified' }}
            </p>
            <p class="mb-1"><span class="font-medium">Created:</span> 
                {{ $customerOrder->created_at->format('M d, Y H:i') }}
            </p>
        </div>
    </div>

    <!-- Special Notes -->
    @if($customerOrder->special_notes)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 mx-6 mb-6 p-4">
        <h3 class="font-bold text-yellow-800 mb-1">Special Instructions</h3>
        <p class="text-yellow-700 whitespace-pre-line">{{ $customerOrder->special_notes }}</p>
    </div>
    @endif

    <!-- Items Section -->
    <div class="px-6 pb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Order Items</h2>
        
        @foreach($customerOrder->orderItems as $item)
        <div class="item-row mb-6 border rounded-lg overflow-hidden">
            <!-- Main Item -->
            <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
                <div>
                    <h3 class="font-medium">{{ $item->item_name }}</h3>
                    @if($item->note)
                    <p class="text-sm text-gray-600 mt-1">{{ $item->note }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <span class="font-medium">{{ $item->quantity }} × {{ number_format($item->price, 2) }}</span>
                    <div class="text-lg font-semibold">{{ number_format($item->calculated_total, 2) }}</div>
                </div>
            </div>

            <!-- Variations -->
            @if($item->is_variation && $item->variationItems->count() > 0)
            <div class="divide-y">
                @foreach($item->variationItems as $variation)
                <div class="px-4 py-3 flex justify-between items-center">
                    <div>
                        <h4 class="text-sm font-medium">{{ $variation->variation_name }}</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-sm">{{ $variation->quantity }} × {{ number_format($variation->price, 2) }}</span>
                        <div class="text-sm font-medium">{{ number_format($variation->total, 2) }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach

        <!-- Order Total -->
        <div class="mt-6 pt-4 border-t text-right">
            <h3 class="text-xl font-bold">Order Total: {{ number_format($orderTotal, 2) }}</h3>
        </div>
    </div>

    <!-- Tracking Section -->
    <div class="bg-gray-50 px-6 py-4 border-t">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div class="mb-4 md:mb-0">
                <h3 class="text-lg font-medium mb-2">Track This Order</h3>
                <div class="text-blue-600 text-sm break-all">
                    {{ url("/customer-orders/{$customerOrder->order_id}/{$customerOrder->random_code}") }}
                </div>
            </div>
            <div class="bg-white p-3 rounded border">
                <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode(url("/customer-order/{$customerOrder->order_id}/{$customerOrder->random_code}")) }}&size=150x150" 
                     alt="QR Code" 
                     class="w-24 h-24">
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-gray-100 px-6 py-3 text-center text-xs text-gray-500">
        <p>Document generated on {{ now()->format('M d, Y \a\t H:i') }}</p>
    </div>
</div>

</body>
</html>

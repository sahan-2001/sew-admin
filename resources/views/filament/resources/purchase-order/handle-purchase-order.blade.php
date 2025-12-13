<x-filament::page>

    <br>
    <br>
    
    <div class="flex items-center gap-4 w-full md:w-1/2">
        
        <!-- Blinking Status Text (Left) -->
        <div class="text-lg font-semibold text-gray-700 animate-blink whitespace-nowrap">
            {{ ucfirst($record->status) }}
        </div>

        <!-- Filled Progress Bar -->
        <div class="w-full bg-gray-200 rounded-md overflow-hidden">
            @php
                $status = $record->status;
                $progressValue = match($status) {
                    'paused' => 0,
                    'planned' => 10,
                    'released' => 30,
                    'partially arrived' => 50,
                    'arrived' => 65,
                    'inspected' => 80,
                    'invoiced' => 90,
                    'closed' => 100,
                    default => 0
                };

                $color = match($status) {
                    'paused' => '#e5e7eb',
                    'planned' => '#3b82f6',
                    'released' => '#f59e0b',
                    'partially arrived' => '#fbbf24',
                    'arrived' => '#ef4444',
                    'inspected' => '#10b981',
                    'invoiced' => '#6b7280',
                    'closed' => '#111827',
                    default => '#e5e7eb'
                };
            @endphp

            <div class="h-3 rounded-md transition-all duration-500 ease-in-out"
                style="width: {{ $progressValue }}%; background-color: {{ $color }};">
            </div>
        </div>

        <!-- Progress Percentage -->
        <div class="text-lg font-semibold text-gray-700">
            {{ $progressValue }}%
        </div>

    </div>

    <br>

    <!-- Order Details (2 Columns) -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <p><strong>Order ID:</strong> {{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</p>
            <p><strong>Supplier:</strong> {{ $record->supplier?->name ?? 'N/A' }}</p>
            <p><strong>Supplier Email:</strong> {{ $record->supplier?->email ?? 'N/A' }}</p>
            <p><strong>Supplier Phone:</strong> {{ $record->supplier?->phone_1 ?? 'N/A' }}</p>
        </div>

        <div>
            <p><strong>Status:</strong> {{ $record->status }}</p>
            <p><strong>Wanted Date:</strong> {{ $record->wanted_date }}</p>
            <p><strong>Special Notes:</strong> {{ $record->special_note }}</p>
        </div>
    </div>

    <!-- Order Items -->
    <h2 class="text-xl font-semibold mt-6">Order Items</h2>
    <table class="w-full border-collapse border border-gray-300 mt-2">
        <thead>
            <tr class="bg-blue-100">
                <th class="border border-gray-300 p-2">Item Code</th>
                <th class="border border-gray-300 p-2">Item Name</th>
                <th class="border border-gray-300 p-2">Quantity</th>
                <th class="border border-gray-300 p-2 text-right">Price</th>
                <th class="border border-gray-300 p-2 text-right">Total</th>
                <th class="border border-gray-300 p-2 text-right">Remaining Quantity</th>
                <th class="border border-gray-300 p-2 text-right">Arrived Quantity</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach ($record->items as $item)
                <tr>
                    <td class="border border-gray-300 p-2">{{ $item->inventoryItem->item_code }}</td>
                    <td class="border border-gray-300 p-2">{{ $item->inventoryItem->name }}</td>
                    <td class="border border-gray-300 p-2">{{ $item->quantity }}</td>
                    <td class="border border-gray-300 p-2 text-right">{{ $item->price }}</td>
                    <td class="border border-gray-300 p-2 text-right">{{ $item->quantity * $item->price }}</td>
                    <td class="border border-gray-300 p-2 text-right">{{ $item->remaining_quantity }}</td>
                    <td class="border border-gray-300 p-2 text-right">{{ $item->arrived_quantity }}</td>
                </tr>
                @php $grandTotal += $item->quantity * $item->price; @endphp
            @endforeach
        </tbody>
    </table>

    <div class="text-right font-bold mt-6">
        <p>Grand Total: {{ $grandTotal }}</p>
    </div>

    <style>
        .animate-blink {
            animation: blink 1s infinite;
            font-weight: bold;
        }

        @keyframes blink {
            50% { opacity: 0.5; }
        }
    </style>

</x-filament::page>

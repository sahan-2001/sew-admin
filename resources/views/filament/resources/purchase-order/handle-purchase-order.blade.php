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
                    'paused' => '#e5e7eb',          // light gray
                    'planned' => '#3b82f6',         // blue
                    'released' => '#f59e0b',        // amber
                    'partially arrived' => '#fbbf24', // light amber
                    'arrived' => '#ef4444',         // red
                    'inspected' => '#10b981',       // green
                    'invoiced' => '#6b7280',        // gray
                    'closed' => '#111827',          // dark
                    default => '#e5e7eb'            // fallback gray
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
            <p><strong>Provider:</strong> {{ $record->provider_name }}</p>
            <p><strong>Provider Email:</strong> {{ $record->provider_email }}</p>
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
        .progress-bar-container {
            height: 10px;
            background-color: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.5s ease-in-out;
        }

        /* Status Labels */
        .status-label {
            display: inline-block;
            width: 25%;
            text-align: center;
        }

        /* Highlight active status label */
        .status-label.text-blue-600 {
            font-weight: bold;
            color: #3b82f6; /* Blue color */
        }

        .status-label.text-green-600 {
            font-weight: bold;
            color: #10b981; /* Green color */
        }

        .status-label.text-red-600 {
            font-weight: bold;
            color: #ef4444; /* Red color */
        }

        .status-label.text-yellow-600 {
            font-weight: bold;
            color: #f59e0b; /* Yellow color */
        }

        /* Blinking Status */
        @keyframes blink {
            50% {
                opacity: 0.5;
            }
        }

        .animate-blink {
            animation: blink 1s infinite;
            font-weight: bold;
        }
    </style>

</x-filament::page>
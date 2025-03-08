<x-filament::page>

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
                    'planned' => 25,
                    'released' => 50,
                    'rejected' => 75,
                    'accepted' => 100,
                    default => 0
                };

                $color = match($status) {
                    'planned' => '#3b82f6',
                    'released' => '#f59e0b',
                    'rejected' => '#ef4444',
                    'accepted' => '#10b981',
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
            <p><strong>Order ID:</strong> {{ $record->order_id }}</p>
            <p><strong>Customer:</strong> {{ $record->customer->name }}</p>
            <p><strong>Order Name:</strong> {{ $record->name }}</p>
        </div>

        <div>
            <p><strong>Status:</strong> {{ $record->status }}</p>
            <p><strong>Wanted Delivery Date:</strong> {{ $record->wanted_delivery_date }}</p>
            <p><strong>Special Notes:</strong> {{ $record->special_notes }}</p>
        </div>
    </div>

    <!-- Order Items -->
    <h2 class="text-xl font-semibold mt-6">Order Items</h2>
    <table class="w-full border-collapse border border-gray-300 mt-2">
        <thead>
            <tr class="bg-blue-100">
                <th class="border border-gray-300 p-2">Item Name</th>
                <th class="border border-gray-300 p-2">Quantity</th>
                <th class="border border-gray-300 p-2 text-right">Price</th>
                <th class="border border-gray-300 p-2 text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach ($record->items as $item)
                @if (!$item->is_variation)
                    <tr>
                        <td class="border border-gray-300 p-2">{{ $item->item_name }}</td>
                        <td class="border border-gray-300 p-2">{{ $item->quantity }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ $item->price }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ $item->total }}</td>
                    </tr>
                    @php $grandTotal += $item->total; @endphp
                @endif
            @endforeach
        </tbody>
    </table>

    <h2 class="text-xl font-semibold mt-6">Variation Items</h2>
    <table class="w-full border-collapse border border-gray-300 mt-2">
        <thead>
            <tr class="bg-blue-100">
                <th class="border border-gray-300 p-2">Parent Item Name</th>
                <th class="border border-gray-300 p-2">Variation Name</th>
                <th class="border border-gray-300 p-2">Quantity</th>
                <th class="border border-gray-300 p-2 text-right">Price</th>
                <th class="border border-gray-300 p-2 text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($record->items as $item)
                @if ($item->is_variation)
                    <tr class="bg-blue-50 font-semibold">
                        <td colspan="5" class="border border-gray-300 p-2">{{ $item->item_name }}</td>
                    </tr>
                    @foreach ($item->variations as $variation)
                        <tr>
                            <td class="border border-gray-300 p-2"></td>
                            <td class="border border-gray-300 p-2">{{ $variation->variation_name }}</td>
                            <td class="border border-gray-300 p-2">{{ $variation->quantity }}</td>
                            <td class="border border-gray-300 p-2 text-right">{{ $variation->price }}</td>
                            <td class="border border-gray-300 p-2 text-right">{{ $variation->total }}</td>
                        </tr>
                        @php $grandTotal += $variation->total; @endphp
                    @endforeach
                @endif
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

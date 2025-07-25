<x-filament::page>
    @php
        $statusConfig = [
            'paused' => ['progress' => 0, 'color' => '#ef4444'],
            'pending' => ['progress' => 10, 'color' => '#6b7280'],
            'released' => ['progress' => 30, 'color' => '#3b82f6'],
            'cut' => ['progress' => 50, 'color' => '#f59e0b'],
            'started' => ['progress' => 60, 'color' => '#fbbf24'],
            'completed' => ['progress' => 70, 'color' => '#10b981'],
            'delivered' => ['progress' => 80, 'color' => '#34d399'],
            'invoiced' => ['progress' => 90, 'color' => '#111827'],
            'closed' => ['progress' => 100, 'color' => '#6b7280']
        ];

        $currentStatus = $record->status ?? 'pending';
        $currentConfig = $statusConfig[$currentStatus] ?? $statusConfig['pending'];
        $progressValue = $currentConfig['progress'];
        $color = $currentConfig['color'];
        
        // Proper date handling
        $wantedDate = $record->wanted_delivery_date;
        $formattedWantedDate = null;
        
        if ($wantedDate instanceof \Carbon\Carbon || $wantedDate instanceof \DateTime) {
            $formattedWantedDate = $wantedDate->format('M d, Y');
        } elseif (is_string($wantedDate)) {
            try {
                $formattedWantedDate = \Carbon\Carbon::parse($wantedDate)->format('M d, Y');
            } catch (\Exception $e) {
                $formattedWantedDate = $wantedDate; // Fallback to raw string if parsing fails
            }
        }
    @endphp

    <div class="space-y-6">
        <!-- Status Progress Bar -->
        <div class="flex items-center gap-4 w-full md:w-1/2">
            <div class="text-lg font-semibold text-gray-700 animate-blink whitespace-nowrap">
                {{ ucfirst(str_replace('_', ' ', $currentStatus)) }}
            </div>
            
            <div class="w-full bg-gray-200 rounded-md overflow-hidden">
                <div class="h-3 rounded-md transition-all duration-500 ease-in-out"
                    style="width: {{ $progressValue }}%; background-color: {{ $color }};">
                </div>
            </div>
            
            <div class="text-lg font-semibold text-gray-700">
                {{ $progressValue }}%
            </div>
        </div>

        <br>
        <br>

        <!-- Order Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <p><strong>Order ID:</strong> {{ str_pad($record->order_id, 5, '0', STR_PAD_LEFT) }}</p>
                <p><strong>Customer:</strong> {{ $record->customer->name ?? 'N/A' }}</p>
                <p><strong>Customer Email:</strong> {{ $record->customer->email ?? 'N/A' }}</p>
            </div>
            
            <div class="space-y-2">
                <p><strong>Status:</strong> <span style="color: {{ $color }}">{{ ucfirst(str_replace('_', ' ', $currentStatus)) }}</span></p>
                <p><strong>Delivery Date:</strong> {{ $formattedWantedDate ?? 'N/A' }}</p>
                <p><strong>Special Notes:</strong> {{ $record->special_notes ?? 'None' }}</p>
            </div>
        </div>

        <!-- Order Items -->
        <div class="space-y-4">
            <h2 class="text-xl font-semibold">Order Items</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-blue-100">
                            <th class="border border-gray-300 p-2">Item Name</th>
                            <th class="border border-gray-300 p-2">Variation</th>
                            <th class="border border-gray-300 p-2">Quantity</th>
                            <th class="border border-gray-300 p-2 text-right">Price</th>
                            <th class="border border-gray-300 p-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotal = 0; @endphp
                        @foreach ($record->orderItems as $item)
                            @if(!$item->is_variation)
                                <tr>
                                    <td class="border border-gray-300 p-2">{{ $item->item_name }}</td>
                                    <td class="border border-gray-300 p-2">-</td>
                                    <td class="border border-gray-300 p-2">{{ $item->quantity }}</td>
                                    <td class="border border-gray-300 p-2 text-right">{{ number_format($item->price, 2) }}</td>
                                    <td class="border border-gray-300 p-2 text-right">{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @php $grandTotal += $item->total; @endphp
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Variation Items -->
        @if($record->orderItems->where('is_variation', true)->count())
            <div class="space-y-4">
                <h2 class="text-xl font-semibold">Variation Items</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-blue-100">
                                <th class="border border-gray-300 p-2">Parent Item</th>
                                <th class="border border-gray-300 p-2">Variation</th>
                                <th class="border border-gray-300 p-2">Quantity</th>
                                <th class="border border-gray-300 p-2 text-right">Price</th>
                                <th class="border border-gray-300 p-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($record->orderItems as $item)
                                @if($item->is_variation)
                                    <tr class="bg-blue-50 font-medium">
                                        <td colspan="5" class="border border-gray-300 p-2">{{ $item->item_name }}</td>
                                    </tr>
                                    @foreach($item->variationItems as $variation)
                                        <tr>
                                            <td class="border border-gray-300 p-2"></td>
                                            <td class="border border-gray-300 p-2">{{ $variation->variation_name }}</td>
                                            <td class="border border-gray-300 p-2">{{ $variation->quantity }}</td>
                                            <td class="border border-gray-300 p-2 text-right">{{ number_format($variation->price, 2) }}</td>
                                            <td class="border border-gray-300 p-2 text-right">{{ number_format($variation->total, 2) }}</td>
                                        </tr>
                                        @php $grandTotal += $variation->total; @endphp
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Grand Total -->
        <div class="text-right font-bold">
            <p>Grand Total: {{ number_format($grandTotal, 2) }}</p>
        </div>
    </div>

    <style>
        @keyframes blink {
            50% { opacity: 0.5; }
        }
        .animate-blink {
            animation: blink 1.5s infinite;
            color: {{ $color }};
        }
    </style>
</x-filament::page>
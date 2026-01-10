<x-filament::page>
    <!-- Header with Status -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight dark:text-white">
                Request for Quotation #{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}
            </h1>
            <p class="text-gray-500 dark:text-gray-400">
                RFQ Code: {{ $record->random_code }}
            </p>
        </div>
        
        <!-- Status Badge -->
        <div class="px-4 py-2 rounded-full font-semibold 
            @switch($record->status)
                @case('draft')
                    bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                    @break
                @case('sent')
                    bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @break
                @case('under_review')
                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @break
                @case('closed')
                    bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @break
                @case('cancelled')
                    bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @break
                @default
                    bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
            @endswitch">
            {{ ucfirst(str_replace('_', ' ', $record->status)) }}
        </div>
    </div>

    <!-- RFQ Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">
                RFQ Information
            </h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">RFQ ID:</span>
                    <span class="font-medium dark:text-white">{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Created By:</span>
                    <span class="dark:text-white">{{ $record->user?->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Created Date:</span>
                    <span class="dark:text-white">{{ $record->created_at->format('d M Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Last Updated:</span>
                    <span class="dark:text-white">{{ $record->updated_at->format('d M Y H:i') }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">
                Supplier & Dates
            </h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Supplier:</span>
                    <span class="dark:text-white">{{ $record->supplier->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Supplier ID:</span>
                    <span class="dark:text-white">{{ str_pad($record->supplier_id ?? 0, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Wanted Delivery:</span>
                    <span class="dark:text-white">{{ $record->wanted_delivery_date ? \Carbon\Carbon::parse($record->wanted_delivery_date)->format('d M Y') : 'Not set' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Valid Until:</span>
                    <span class="dark:text-white">{{ $record->valid_until ? \Carbon\Carbon::parse($record->valid_until)->format('d M Y') : 'Not set' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Special Notes -->
    @if($record->special_note)
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2 dark:text-white">
            Special Notes
        </h2>
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
            <p class="text-gray-800 dark:text-gray-200">{{ $record->special_note }}</p>
        </div>
    </div>
    @endif

    <!-- RFQ Items Section - FULL WIDTH -->
    <div class="mb-8 w-full">
        <h2 class="text-lg font-semibold mb-4 dark:text-white">
            Requested Items
        </h2>
        
        <!-- Full Width Table Container -->
        <div class="w-full overflow-x-auto rounded-lg shadow">
            <table class="w-full bg-white dark:bg-gray-800 min-w-full">
                <!-- Table Header -->
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Item Code
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Item Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Quantity
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Unit Price (Rs.)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Subtotal (Rs.)
                        </th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $totalQuantity = 0;
                        $totalAmount = 0;
                    @endphp
                    
                    @forelse($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <!-- Item Code -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $item->inventoryItem?->item_code ?? 'N/A' }}
                        </td>
                        
                        <!-- Item Name -->
                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-white">
                            {{ $item->inventoryItem?->name ?? 'N/A' }}
                        </td>
                        
                        <!-- Quantity -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                            {{ number_format($item->quantity, 0) }}
                            @php $totalQuantity += $item->quantity; @endphp
                        </td>
                        
                        <!-- Unit Price -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                            {{ number_format($item->price, 2) }}
                        </td>
                        
                        <!-- Subtotal -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white text-right">
                            {{ number_format($item->item_subtotal, 2) }}
                            @php $totalAmount += $item->item_subtotal; @endphp
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p>No items found in this RFQ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                    
                    <!-- Summary Row (if items exist) -->
                    @if($items->count() > 0)
                    <tr class="bg-gray-50 dark:bg-gray-700 font-semibold">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            Total Items: {{ $items->count() }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            Summary
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-right">
                            {{ number_format($totalQuantity, 0) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-right">
                            -
                        </td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white text-right">
                            {{ number_format($totalAmount, 2) }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Totals Section -->
    <div class="w-full bg-gray-50 dark:bg-gray-800 p-6 rounded-lg shadow">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Summary Column -->
            <div>
                <h3 class="text-lg font-semibold mb-4 dark:text-white">RFQ Summary</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Total Items:</span>
                        <span class="font-medium dark:text-white">{{ $items->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Total Quantity:</span>
                        <span class="font-medium dark:text-white">{{ number_format($totalQuantity, 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Status:</span>
                        <span class="font-medium dark:text-white">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Financial Column -->
            <div>
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Financial Summary</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Items Subtotal:</span>
                        <span class="font-medium dark:text-white">Rs. {{ number_format($record->order_subtotal, 2) }}</span>
                    </div>
                    <div class="border-t dark:border-gray-700 pt-2 mt-2">
                        <div class="flex justify-between">
                            <span class="text-lg font-semibold dark:text-white">Total RFQ Value:</span>
                            <span class="text-lg font-bold dark:text-white">
                                Rs. {{ number_format($record->order_subtotal, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Progress Bar -->
    <div class="mt-8 w-full">
        <h3 class="text-lg font-semibold mb-4 dark:text-white">RFQ Status Progress</h3>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            @php
                $statuses = [
                    'draft' => ['label' => 'Draft', 'color' => 'bg-gray-500 dark:bg-gray-600'],
                    'sent' => ['label' => 'Sent to Supplier', 'color' => 'bg-blue-500 dark:bg-blue-600'],
                    'under_review' => ['label' => 'Under Review', 'color' => 'bg-yellow-500 dark:bg-yellow-600'],
                    'closed' => ['label' => 'Closed', 'color' => 'bg-green-500 dark:bg-green-600'],
                    'cancelled' => ['label' => 'Cancelled', 'color' => 'bg-red-500 dark:bg-red-600'],
                ];
                
                $currentStatus = $record->status;
                $statusIndex = array_keys($statuses);
                $currentIndex = array_search($currentStatus, $statusIndex);
                $progressPercent = ($currentIndex / (count($statuses) - 1)) * 100;
            @endphp
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-6">
                <div class="h-3 rounded-full transition-all duration-500 ease-in-out 
                    {{ $statuses[$currentStatus]['color'] }}"
                    style="width: {{ $progressPercent }}%">
                </div>
            </div>
            
            <!-- Status Indicators -->
            <div class="flex justify-between">
                @foreach($statuses as $key => $status)
                    <div class="flex flex-col items-center relative">
                        <!-- Connector Line (except for last item) -->
                        @if(!$loop->last)
                            <div class="absolute top-3 left-1/2 w-full h-0.5 bg-gray-300 dark:bg-gray-600 -z-10"></div>
                        @endif
                        
                        <!-- Status Circle -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2
                            {{ $key === $currentStatus ? $status['color'] : 'bg-gray-300 dark:bg-gray-600' }}
                            {{ $key === $currentStatus ? 'ring-4 ring-opacity-30' : '' }}
                            {{ $key === $currentStatus ? 'ring-' . explode('-', $status['color'])[1] . '-300 dark:ring-' . explode('-', $status['color'])[1] . '-500' : '' }}">
                            @if($key === $currentStatus)
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @elseif(array_search($key, $statusIndex) < $currentIndex)
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @endif
                        </div>
                        
                        <!-- Status Label -->
                        <span class="text-xs font-medium text-center {{ $key === $currentStatus ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $status['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .animate-blink {
            animation: blink 1s infinite;
            font-weight: bold;
        }

        @keyframes blink {
            50% { opacity: 0.5; }
        }
        
        /* Custom table styling for dark mode */
        .dark table {
            color: #ffffff !important;
        }
        
        .dark table thead th {
            color: #d1d5db !important;
        }
        
        .dark table tbody td {
            color: #ffffff !important;
        }
        
        .dark table tbody tr:hover {
            background-color: rgba(55, 65, 81, 0.5) !important;
        }
        
        /* Full width table responsiveness */
        @media (max-width: 768px) {
            .w-full.overflow-x-auto {
                margin-left: -1rem;
                margin-right: -1rem;
                width: calc(100% + 2rem);
            }
            
            table {
                min-width: 800px;
            }
            
            .px-6 {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
</x-filament::page>
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
                @case('approved')
                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @break
                @case('completed')
                    bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
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



    {{-- RFQ INFO & SUPPLIER --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        {{-- RFQ Info --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">RFQ Information</h2>
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">RFQ ID:</span><span class="font-medium dark:text-white">{{ str_pad($record->id,5,'0',STR_PAD_LEFT) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Created By:</span><span class="dark:text-white">{{ $record->user?->name ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Created Date:</span><span class="dark:text-white">{{ $record->created_at->format('d M Y H:i') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Last Updated:</span><span class="dark:text-white">{{ $record->updated_at->format('d M Y H:i') }}</span></div>
            </div>
        </div>

        {{-- Supplier Info --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">Supplier & Dates</h2>
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Supplier Name:</span><span class="dark:text-white">{{ $record->supplier->name ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Supplier ID:</span><span class="dark:text-white">{{ str_pad($record->supplier_id ?? 0,5,'0',STR_PAD_LEFT) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Wanted Delivery:</span><span class="dark:text-white">{{ $record->wanted_delivery_date ? \Carbon\Carbon::parse($record->wanted_delivery_date)->format('d M Y') : 'Not set' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Valid Until:</span><span class="dark:text-white">{{ $record->valid_until ? \Carbon\Carbon::parse($record->valid_until)->format('d M Y') : 'Not set' }}</span></div>
            </div>
        </div>
    </div>

    {{-- SPECIAL NOTES --}}
    @if($record->special_note)
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-2 dark:text-white">Special Notes</h2>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                <p class="text-gray-800 dark:text-gray-200">{{ $record->special_note }}</p>
            </div>
        </div>
    @endif

    {{-- PAYMENT / DELIVERY / CURRENCY --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <strong class="text-gray-700 dark:text-gray-200">Payment Term:</strong><br>
            <span class="text-gray-800 dark:text-gray-100">{{ $record->paymentTerm ? $record->paymentTerm->name . ' | ' . $record->paymentTerm->description : 'N/A' }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <strong class="text-gray-700 dark:text-gray-200">Delivery Term:</strong><br>
            <span class="text-gray-800 dark:text-gray-100">{{ $record->deliveryTerm ? $record->deliveryTerm->name . ' | ' . $record->deliveryTerm->description : 'N/A' }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <strong class="text-gray-700 dark:text-gray-200">Delivery Method:</strong><br>
            <span class="text-gray-800 dark:text-gray-100">{{ $record->deliveryMethod ? $record->deliveryMethod->name . ' | ' . $record->deliveryMethod->description : 'N/A' }}</span>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <strong class="text-gray-700 dark:text-gray-200">Currency:</strong><br>
            <span class="text-gray-800 dark:text-gray-100">{{ $record->currency ? $record->currency->code . ' | ' . $record->currency->name : 'N/A' }}</span>
        </div>
    </div>

    {{-- REQUESTED ITEMS TABLE --}}
    <div class="mb-8 w-full">
        <h2 class="text-lg font-semibold mb-4 dark:text-white">Requested Items</h2>
        <div class="w-full overflow-x-auto rounded-lg shadow">
            <table class="w-full bg-white dark:bg-gray-800 min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Item Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Item Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap text-right">Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-white">{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">{{ number_format($item->quantity,0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No items found in this RFQ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- STATUS PROGRESS BAR --}}
    <div class="mt-8 w-full">
        <h3 class="text-lg font-semibold mb-4 dark:text-white">RFQ Status Progress</h3>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            @php
                $statuses = [
                    'draft' => ['label'=>'Draft','color'=>'bg-gray-500 dark:bg-gray-600'],
                    'sent' => ['label'=>'Sent to Supplier','color'=>'bg-blue-500 dark:bg-blue-600'],
                    'approved' => ['label'=>'Under Review','color'=>'bg-yellow-500 dark:bg-yellow-600'],
                    'completed' => ['label'=>'Completed','color'=>'bg-purple-500 dark:bg-purple-600'],
                    'closed' => ['label'=>'Closed','color'=>'bg-green-500 dark:bg-green-600'],
                    'cancelled' => ['label'=>'Cancelled','color'=>'bg-red-500 dark:bg-red-600'],
                ];
                $currentIndex = array_search($record->status,array_keys($statuses));
                $progressPercent = ($currentIndex/(count($statuses)-1))*100;
            @endphp

            {{-- Progress Bar --}}
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-6">
                <div class="h-3 rounded-full transition-all duration-500 ease-in-out {{ $statuses[$record->status]['color'] }}" style="width: {{ $progressPercent }}%"></div>
            </div>

            {{-- Status Indicators --}}
            <div class="flex justify-between">
                @foreach($statuses as $key => $status)
                    <div class="flex flex-col items-center relative">
                        @if(!$loop->last)
                            <div class="absolute top-3 left-1/2 w-full h-0.5 bg-gray-300 dark:bg-gray-600 -z-10"></div>
                        @endif
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2
                            {{ $key === $record->status ? $status['color'].' ring-4 ring-opacity-30' : 'bg-gray-300 dark:bg-gray-600' }}">
                            @if(array_search($key,array_keys($statuses)) <= $currentIndex)
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @endif
                        </div>
                        <span class="text-xs font-medium text-center {{ $key === $record->status ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-500 dark:text-gray-400' }}">{{ $status['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- STYLES --}}
    <style>
        @media (max-width: 768px) {
            table { min-width: 600px; }
        }
    </style>
</x-filament::page>

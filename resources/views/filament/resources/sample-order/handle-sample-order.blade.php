<x-filament::page>
    
    <!-- Progress Bar with Status Circle -->
    <div class="mt-4 relative">
        <!-- Progress Bar -->
        <div class="progress-bar-container w-full bg-gray-200 rounded-full overflow-hidden">
            <div class="progress-bar h-2 rounded-full bg-blue-600" style="width: {{ $record->status == 'planned' ? '25%' : ($record->status == 'in_progress' ? '50%' : ($record->status == 'completed' ? '100%' : '0%')) }}"></div>
        </div>

        <!-- Status Circle -->
        <div class="status-circle absolute top-1/2 transform -translate-y-1/2" 
             style="left: {{ $record->status == 'planned' ? '25%' : ($record->status == 'in_progress' ? '50%' : ($record->status == 'completed' ? '100%' : '0%')) }}; 
                    width: {{ $record->status == 'completed' ? '30px' : '20px' }}; 
                    height: {{ $record->status == 'completed' ? '30px' : '20px' }}; 
                    background-color: {{ $record->status == 'completed' ? '#f59e0b' : '#f59e0b' }}; 
                    border-radius: 50%;">
        </div>
    </div>

    <!-- Status Labels -->
    <div class="flex justify-between mt-2 text-xs font-medium text-gray-600">
        <span class="status-label {{ $record->status == 'planned' ? 'text-blue-600' : '' }}">Planned</span>
        <span class="status-label {{ $record->status == 'in_progress' ? 'text-blue-600' : '' }}">In Progress</span>
        <span class="status-label {{ $record->status == 'completed' ? 'text-blue-600' : '' }}">Completed</span>
    </div>

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

    <!-- Convert to a Customer Order Button -->
    <div class="mt-6 text-right">
    <x-filament::button color="danger" icon="heroicon-o-x-circle" wire:click="releaseOrder">
    Release Order
</x-filament::button>
    </div>

    <div>
    <!-- Order Status -->
    <div>
        <h3>{{ $record->status }}</h3>

        <!-- Release Button -->
        <div class="mt-4 text-right">
            <x-filament::button color="danger" icon="heroicon-o-x-circle" wire:click="releaseOrder">
                Release Order
            </x-filament::button>
        </div>

        <!-- Success Message -->
        @if (session()->has('message'))
            <div class="mt-4 text-green-600">
                {{ session('message') }}
            </div>
        @endif
    </div>
</div>


    <style>
        /* Progress Bar Container */
        .progress-bar-container {
            height: 6px;
            background-color: #e5e7eb; /* Light gray background */
            border-radius: 9999px; /* Fully rounded */
            overflow: hidden;
        }

        /* Progress Bar Style */
        .progress-bar {
            transition: width 0.5s ease-in-out; /* Smooth transition */
        }

        /* Status Circle */
        .status-circle {
            transition: width 0.5s ease-in-out, height 0.5s ease-in-out;
        }

        /* Status Labels */
        .status-label {
            display: inline-block;
            width: 33.33%;
            text-align: center;
        }

        /* Highlight active status label */
        .status-label.text-blue-600 {
            font-weight: bold;
            color: #3b82f6; /* Blue color */
        }

        /* Set the close button color to yellow */
        .filament-modal .modal-close {
            background-color: #f59e0b !important; /* Yellow background */
            color: white !important; /* White icon color */
        }

        /* Optional: Add hover effect for the close button */
        .filament-modal .modal-close:hover {
            background-color: #fbbf24 !important; /* Slightly darker yellow on hover */
        }
    </style>

</x-filament::page>

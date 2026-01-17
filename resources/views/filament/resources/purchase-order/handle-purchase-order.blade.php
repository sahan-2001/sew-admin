<x-filament::page>

    <!-- Status & Progress -->
    <div class="flex items-center gap-4 w-full md:w-1/2 mt-4">
        <div class="text-lg font-semibold text-gray-700 animate-blink whitespace-nowrap">
            {{ ucfirst($record->status) }}
        </div>

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

        <div class="text-lg font-semibold text-gray-700">
            {{ $progressValue }}%
        </div>
    </div>

    <!-- Order Details -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <p><strong>Order ID:</strong> {{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</p>
            <p><strong>Supplier ID:</strong> {{ str_pad($record->supplier?->supplier_id ?? 0, 5, '0', STR_PAD_LEFT) }}</p>
            <p><strong>Purchase Quotation ID:</strong> 
                @if($record->purchaseQuotation)
                    {{ str_pad($record->purchaseQuotation->id, 5, '0', STR_PAD_LEFT) }}
                @else
                    N/A
                @endif
            </p>
            <p><strong>Currency:</strong> {{ $record->currency?->code ?? 'N/A' }}</p>
            <p><strong>Delivery Term:</strong> {{ $record->cudelivery_term?->name ?? 'N/A' }}</p>
            <p><strong>Supplier:</strong> {{ $record->supplier?->name ?? 'N/A' }}</p>
            <p><strong>Email:</strong> {{ $record->supplier?->email ?? 'N/A' }}</p>
            <p><strong>Phone:</strong> {{ $record->supplier?->phone_1 ?? 'N/A' }}</p>
        </div>

        <div>
            <p><strong>Status:</strong> {{ ucfirst($record->status) }}</p>
            <p><strong>Wanted Date:</strong> {{ $record->wanted_delivery_date }}</p>
            <p><strong>VAT Base:</strong> {{ $record->vat_base }}</p>
            <p><strong>Special Notes:</strong> {{ $record->special_note ?? '-' }}</p>

            @if($record->vat_base === 'supplier_vat')
                <p><strong>Supplier VAT Rate:</strong> {{ $record->supplier_vat_rate }}%</p>
                <p><strong>Order VAT Amount:</strong> Rs. {{ number_format($record->vat_amount, 2) }}</p>
            @endif
        </div>
    </div>

    <!-- Order Items Table -->
    <h2 class="text-xl font-semibold mt-6 mb-2">Order Items</h2>
    <table class="w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-blue-100">
                <th class="border border-gray-300 p-2">Item Code</th>
                <th class="border border-gray-300 p-2">Item Name</th>
                <th class="border border-gray-300 p-2">Quantity</th>
                <th class="border border-gray-300 p-2 text-right">Price (Rs.)</th>
                <th class="border border-gray-300 p-2 text-right">Subtotal (Rs.)</th>
                @if($record->vat_base === 'item_vat')
                    <th class="border border-gray-300 p-2 text-right">VAT Amount (Rs.)</th>
                    <th class="border border-gray-300 p-2 text-right">Grand Total (Rs.)</th>
                @endif
                <th class="border border-gray-300 p-2 text-right">Remaining Qty</th>
                <th class="border border-gray-300 p-2 text-right">Arrived Qty</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $orderSubtotal = 0; 
                $orderVat = 0; 
                $orderGrandTotal = 0; 
            @endphp

            @foreach ($record->items as $item)
                <tr>
                    <td class="border border-gray-300 p-2">{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                    <td class="border border-gray-300 p-2">{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                    <td class="border border-gray-300 p-2">{{ $item->quantity }}</td>
                    <td class="border border-gray-300 p-2 text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="border border-gray-300 p-2 text-right">{{ number_format($item->item_subtotal, 2) }}</td>
                    
                    @if($record->vat_base === 'item_vat')
                        <td class="border border-gray-300 p-2 text-right">{{ number_format($item->item_vat_amount, 2) }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ number_format($item->item_grand_total, 2) }}</td>
                    @endif

                    <td class="border border-gray-300 p-2 text-right">{{ $item->remaining_quantity ?? 0 }}</td>
                    <td class="border border-gray-300 p-2 text-right">{{ $item->arrived_quantity ?? 0 }}</td>
                </tr>

                @php
                    $orderSubtotal += $item->item_subtotal;
                    $orderVat += $item->item_vat_amount;
                    $orderGrandTotal += $item->item_grand_total;
                @endphp
            @endforeach
        </tbody>
    </table>

    <!-- PO Totals -->
    <div class="text-right font-bold mt-4">
        <p>Order Subtotal: Rs. {{ number_format($orderSubtotal, 2) }}</p>
        @if($record->vat_base === 'item_vat')
            <p>Total VAT: Rs. {{ number_format($orderVat, 2) }}</p>
        @else
            <p>Total VAT: Rs. {{ number_format($record->vat_amount, 2) }}</p>
        @endif
        <p>Grand Total: Rs. {{ number_format($record->grand_total, 2) }}</p>
    </div>

    @if($supplierAdvanceInvoices->isNotEmpty())
    <x-filament::section
        heading="Supplier Advance Invoices"
        icon="heroicon-o-banknotes"
        class="mt-6"
    >
        <div class="overflow-x-auto">
            <table class="w-full text-sm border rounded-lg">
                <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">Invoice #</th>
                        <th class="px-3 py-2 text-left">Supplier</th>
                        <th class="px-3 py-2 text-right">Grand Total</th>
                        <th class="px-3 py-2 text-right">Paid</th>
                        <th class="px-3 py-2 text-right">Remaining</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-center">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supplierAdvanceInvoices as $invoice)
                        <tr class="border-t">
                            <td class="px-3 py-2">
                                {{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $invoice->supplier?->name }}
                            </td>

                            <td class="px-3 py-2 text-right">
                                {{ number_format($invoice->grand_total, 2) }}
                            </td>

                            <td class="px-3 py-2 text-right text-green-600">
                                {{ number_format($invoice->paid_amount, 2) }}
                            </td>

                            <td class="px-3 py-2 text-right text-red-600">
                                {{ number_format($invoice->remaining_amount, 2) }}
                            </td>

                            <td class="px-3 py-2 text-center">
                                <x-filament::badge
                                    :color="match($invoice->status) {
                                        'pending' => 'warning',
                                        'partial' => 'info',
                                        'paid' => 'success',
                                        default => 'gray',
                                    }"
                                >
                                    {{ ucfirst($invoice->status) }}
                                </x-filament::badge>
                            </td>

                            <td class="px-3 py-2 text-center">
                                {{ optional($invoice->paid_date)->format('Y-m-d') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
@endif


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

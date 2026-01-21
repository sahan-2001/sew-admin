<x-filament::page>

    <!-- Status & Progress -->
    <x-filament::card class="mb-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-lg font-semibold text-gray-700 animate-blink">
                Status: {{ ucfirst($record->status) }}
            </div>

            <div class="flex-1 bg-gray-200 rounded-md overflow-hidden h-3 mt-2 md:mt-0">
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
    </x-filament::card>

    <!-- Order Info -->
    <x-filament::section heading="Order Details" icon="heroicon-o-document-check" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
                <p><strong>Order ID:</strong> {{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</p>
                <p><strong>Supplier ID:</strong> {{ str_pad($record->supplier?->supplier_id ?? 0, 5, '0', STR_PAD_LEFT) }}</p>
                <p><strong>Purchase Quotation ID:</strong> 
                    @if($record->purchaseQuotation)
                        {{ str_pad($record->purchaseQuotation->id, 5, '0', STR_PAD_LEFT) }}
                    @else N/A @endif
                </p>
                <p><strong>Currency:</strong> {{ $record->currency?->code ?? 'N/A' }}</p>
                <p><strong>Delivery Term:</strong> {{ $record->delivery_term?->name ?? 'N/A' }}</p>
                <p><strong>Payment Term:</strong> {{ $record->payment_term?->name ?? 'N/A' }}</p>
            </div>

            <div class="space-y-1">
                <p><strong>Supplier:</strong> {{ $record->supplier?->name ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $record->supplier?->email ?? 'N/A' }}</p>
                <p><strong>Phone:</strong> {{ $record->supplier?->phone_1 ?? 'N/A' }}</p>
                <p><strong>Wanted Delivery Date:</strong> {{ $record->wanted_delivery_date }}</p>
                <p><strong>Promised Delivery Date:</strong> {{ $record->promised_delivery_date }}</p>
                <p><strong>VAT Base:</strong> {{ $record->vat_base }}</p>
                @if($record->vat_base === 'supplier_vat')
                    <p><strong>Supplier VAT Rate:</strong> {{ $record->supplier_vat_rate }}%</p>
                    <p><strong>Order VAT Amount:</strong> Rs. {{ number_format($record->vat_amount, 2) }}</p>
                @endif
                <p><strong>Special Notes:</strong> {{ $record->special_note ?? '-' }}</p>
            </div>
        </div>
    </x-filament::section>

    <!-- Order Items -->
    <x-filament::section heading="Order Items" icon="heroicon-o-shopping-cart" class="mb-6">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-sm">
                <thead class="bg-blue-100">
                    <tr>
                        <th class="p-2 border">Item Code</th>
                        <th class="p-2 border">Item Name</th>
                        <th class="p-2 border">Qty</th>
                        <th class="p-2 border text-right">Price (Rs.)</th>
                        <th class="p-2 border text-right">Subtotal</th>
                        @if($record->vat_base === 'item_vat')
                            <th class="p-2 border text-right">VAT</th>
                            <th class="p-2 border text-right">Grand Total</th>
                        @endif
                        <th class="p-2 border text-right">Remaining Qty</th>
                        <th class="p-2 border text-right">Arrived Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $orderSubtotal = 0;
                        $orderVat = 0;
                        $orderGrandTotal = 0;
                    @endphp
                    @foreach ($record->items as $item)
                        <tr class="border-t">
                            <td class="p-2 border">{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                            <td class="p-2 border">{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                            <td class="p-2 border">{{ $item->quantity }}</td>
                            <td class="p-2 border text-right">{{ number_format($item->price,2) }}</td>
                            <td class="p-2 border text-right">{{ number_format($item->item_subtotal,2) }}</td>
                            @if($record->vat_base === 'item_vat')
                                <td class="p-2 border text-right">{{ number_format($item->item_vat_amount,2) }}</td>
                                <td class="p-2 border text-right">{{ number_format($item->item_grand_total,2) }}</td>
                            @endif
                            <td class="p-2 border text-right">{{ $item->remaining_quantity ?? 0 }}</td>
                            <td class="p-2 border text-right">{{ $item->arrived_quantity ?? 0 }}</td>
                        </tr>
                        @php
                            $orderSubtotal += $item->item_subtotal;
                            $orderVat += $item->item_vat_amount;
                            $orderGrandTotal += $item->item_grand_total;
                        @endphp
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 text-right font-bold space-y-1">
                <p>Subtotal: Rs. {{ number_format($orderSubtotal,2) }}</p>
                <p>Order Discount: Rs. {{ number_format($record->order_discount_amount ?? 0,2) }}</p>
                @if($record->vat_base === 'item_vat')
                    <p>VAT: Rs. {{ number_format($orderVat,2) }}</p>
                @else
                    <p>VAT: Rs. {{ number_format($record->vat_amount,2) }}</p>
                @endif
                <p>Grand Total: Rs. {{ number_format($record->grand_total,2) }}</p>
                <p>Final Grand Total: Rs. {{ number_format($record->final_grand_total,2) }}</p>
                <p>Remaining Balance: Rs. {{ number_format($record->remaining_balance,2) }}</p>
            </div>
        </div>
    </x-filament::section>

    <!-- Supplier Advance Invoices -->
    @if($record->supplierAdvanceInvoices->isNotEmpty())
    <x-filament::section heading="Supplier Advance Invoices" icon="heroicon-o-banknotes" class="mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left">Invoice #</th>
                        <th class="p-2 text-left">Supplier</th>
                        <th class="p-2 text-right">Grand Total</th>
                        <th class="p-2 text-right">Paid</th>
                        <th class="p-2 text-right">Remaining</th>
                        <th class="p-2 text-center">Status</th>
                        <th class="p-2 text-center">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->supplierAdvanceInvoices as $invoice)
                        <tr class="border-t">
                            <td class="p-2">{{ str_pad($invoice->id,5,'0',STR_PAD_LEFT) }}</td>
                            <td class="p-2">{{ $invoice->supplier?->name }}</td>
                            <td class="p-2 text-right">{{ number_format($invoice->grand_total,2) }}</td>
                            <td class="p-2 text-right text-green-600">{{ number_format($invoice->paid_amount,2) }}</td>
                            <td class="p-2 text-right text-red-600">{{ number_format($invoice->remaining_amount,2) }}</td>
                            <td class="p-2 text-center">
                                <x-filament::badge :color="match($invoice->status){'pending'=>'warning','partial'=>'info','paid'=>'success',default=>'gray'}">
                                    {{ ucfirst($invoice->status) }}
                                </x-filament::badge>
                            </td>
                            <td class="p-2 text-center">{{ optional($invoice->paid_date)->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    @endif

    <style>
        .animate-blink { animation: blink 1s infinite; font-weight:bold; }
        @keyframes blink { 50% { opacity:0.5; } }
    </style>

</x-filament::page>

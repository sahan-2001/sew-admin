<x-filament::page>

<!-- Status & Progress -->
@php
    $status = $record->status;
    $progressValue = match($status) {
        'draft' => 0,
        'sent' => 25,
        'accepted' => 75,
        'rejected' => 100,
        default => 0
    };
    $color = match($status) {
        'draft' => '#e5e7eb',
        'sent' => '#3b82f6',
        'accepted' => '#10b981',
        'rejected' => '#ef4444',
        default => '#e5e7eb'
    };
@endphp

<div class="flex items-center gap-4">
    <div>{{ ucfirst($record->status) }}</div>
    <div class="w-full bg-gray-200 h-3 rounded-md overflow-hidden">
        <div style="width: {{ $progressValue }}%; background-color: {{ $color }}; height: 100%;"></div>
    </div>
    <div>{{ $progressValue }}%</div>
</div>

<!-- RFQ Details -->
<p><strong>RFQ ID:</strong> {{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</p>
<p><strong>Supplier ID:</strong> {{ str_pad($record->supplier?->supplier_id ?? 0, 5, '0', STR_PAD_LEFT) }}</p>
<p><strong>Supplier:</strong> {{ $record->supplier?->name ?? 'N/A' }}</p>
<p><strong>Email:</strong> {{ $record->supplier?->email ?? 'N/A' }}</p>
<p><strong>Phone:</strong> {{ $record->supplier?->phone_1 ?? 'N/A' }}</p>
<p><strong>Status:</strong> {{ ucfirst($record->status) }}</p>
<p><strong>Wanted Delivery Date:</strong> {{ $record->wanted_delivery_date?->format('Y-m-d') ?? '-' }}</p>
<p><strong>Valid Until:</strong> {{ $record->valid_until?->format('Y-m-d') ?? '-' }}</p>
<p><strong>Special Notes:</strong> {{ $record->special_note ?? '-' }}</p>
<p><strong>RFQ Code:</strong> {{ $record->random_code ?? '-' }}</p>
<p><strong>Order Subtotal:</strong> Rs. {{ number_format($record->order_subtotal, 2) }}</p>
<p><strong>Grand Total:</strong> Rs. {{ number_format($record->order_subtotal, 2) }}</p>

<!-- Quotation Items Table -->
<h3>Quotation Items</h3>
@php $totalQuantity = 0; $totalSubtotal = 0; @endphp
<table class="w-full border border-gray-300">
    <thead>
        <tr class="bg-blue-100">
            <th class="border p-2">Item Code</th>
            <th class="border p-2">Item Name</th>
            <th class="border p-2 text-center">Unit</th>
            <th class="border p-2 text-center">Quantity</th>
            <th class="border p-2 text-right">Unit Price (Rs.)</th>
            <th class="border p-2 text-right">Subtotal (Rs.)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($record->items as $item)
            @php
                $totalQuantity += $item->quantity;
                $totalSubtotal += $item->item_subtotal;
            @endphp
            <tr>
                <td class="border p-2">{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                <td class="border p-2">{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                <td class="border p-2 text-center">{{ $item->inventoryItem?->unit ?? '-' }}</td>
                <td class="border p-2 text-center">{{ $item->quantity }}</td>
                <td class="border p-2 text-right">{{ number_format($item->price, 2) }}</td>
                <td class="border p-2 text-right">{{ number_format($item->item_subtotal, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<!-- Totals -->
<p><strong>Total Items:</strong> {{ $record->items->count() }}</p>
<p><strong>Total Quantity:</strong> {{ $totalQuantity }}</p>
<p><strong>Subtotal:</strong> Rs. {{ number_format($totalSubtotal, 2) }}</p>
<p><strong>Grand Total:</strong> Rs. {{ number_format($totalSubtotal, 2) }}</p>


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

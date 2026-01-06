<x-filament::page>
    <div class="space-y-6">

        {{-- Supplier & RFQ Info --}}
        <div class="grid grid-cols-2 gap-4">
            <x-filament::card>
                <h2 class="text-lg font-medium">Supplier Information</h2>
                <p><strong>Supplier:</strong> {{ $record->supplier?->name ?? '-' }}</p>
                <p><strong>Email:</strong> {{ $record->supplier?->email ?? '-' }}</p>
                <p><strong>Phone:</strong> {{ $record->supplier?->phone_1 ?? '-' }}</p>
                <p><strong>VAT Group:</strong> {{ $record->supplierVatGroup?->vat_group_name ?? '-' }}</p>
                <p><strong>VAT Rate:</strong> {{ $record->supplier_vat_rate ?? 0 }}%</p>
            </x-filament::card>

            <x-filament::card>
                <h2 class="text-lg font-medium">RFQ Details</h2>
                <p><strong>Quotation Date:</strong> {{ $record->created_at?->format('Y-m-d') ?? '-' }}</p>
                <p><strong>Valid Until:</strong> {{ \Carbon\Carbon::parse($record->valid_until)?->format('Y-m-d') ?? '-' }}</p>
                <p><strong>Expected Delivery:</strong> {{ \Carbon\Carbon::parse($record->wanted_delivery_date)?->format('Y-m-d') ?? '-' }}</p>
                <p><strong>Status:</strong> 
                    <x-filament::badge color="{{ match($record->status) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray'
                    } }}">
                        {{ ucfirst($record->status) }}
                    </x-filament::badge>
                </p>
                <p><strong>RFQ Code:</strong> {{ $record->random_code ?? '-' }}</p>
                <p><strong>Created By:</strong> {{ $record->user?->name ?? '-' }}</p>
                <p><strong>Order Subtotal:</strong> Rs. {{ number_format($record->order_subtotal, 2) }}</p>
                <p><strong>VAT Amount:</strong> Rs. {{ number_format($record->vat_amount, 2) }}</p>
                <p><strong>Grand Total:</strong> Rs. {{ number_format($record->grand_total, 2) }}</p>
            </x-filament::card>
        </div>
        
        {{-- Items Table --}}
        <x-filament::card>
            <h2 class="text-lg font-medium mb-2">Quotation Items</h2>
            <table class="w-full table-auto border-collapse border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-2 py-1 text-left">Item Code</th>
                        <th class="border px-2 py-1 text-left">Item Name</th>
                        <th class="border px-2 py-1">Unit</th>
                        <th class="border px-2 py-1">Quantity</th>
                        <th class="border px-2 py-1">Unit Price</th>
                        <th class="border px-2 py-1">VAT %</th>
                        <th class="border px-2 py-1">Subtotal</th>
                        <th class="border px-2 py-1">VAT Amount</th>
                        <th class="border px-2 py-1">Grand Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->items as $item)
                        <tr>
                            <td class="border px-2 py-1">{{ $item->inventoryItem->code ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $item->inventoryItem->name ?? '-' }}</td>
                            <td class="border px-2 py-1 text-center">{{ $item->inventoryItem->unit ?? '-' }}</td>
                            <td class="border px-2 py-1 text-center">{{ $item->quantity }}</td>
                            <td class="border px-2 py-1 text-right">Rs. {{ number_format($item->price, 2) }}</td>
                            <td class="border px-2 py-1 text-center">{{ $item->inventory_vat_rate ?? 0 }}%</td>
                            <td class="border px-2 py-1 text-right">Rs. {{ number_format($item->item_subtotal, 2) }}</td>
                            <td class="border px-2 py-1 text-right">Rs. {{ number_format($item->item_vat_amount, 2) }}</td>
                            <td class="border px-2 py-1 text-right">Rs. {{ number_format($item->item_grand_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td colspan="6" class="border px-2 py-1 text-right">Totals:</td>
                        <td class="border px-2 py-1 text-right">Rs. {{ number_format($record->order_subtotal, 2) }}</td>
                        <td class="border px-2 py-1 text-right">Rs. {{ number_format($record->vat_amount, 2) }}</td>
                        <td class="border px-2 py-1 text-right">Rs. {{ number_format($record->grand_total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </x-filament::card>

        {{-- Summary Card --}}
        <x-filament::card>
            <div class="grid grid-cols-4 gap-4 text-center">
                <div>
                    <p class="text-sm text-gray-500">Total Items</p>
                    <p class="text-xl font-bold">{{ $record->items->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Quantity</p>
                    <p class="text-xl font-bold">{{ $record->items->sum('quantity') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">VAT Base</p>
                    <p class="text-xl font-bold">{{ ucfirst(str_replace('_', ' ', $record->vat_base)) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">VAT Type</p>
                    <p class="text-xl font-bold">
                        {{ $record->vat_base === 'supplier_vat' ? 'Supplier VAT' : 'Item VAT' }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        {{-- Special Note --}}
        <x-filament::card>
            <h2 class="text-lg font-medium">Remarks</h2>
            <p>{{ $record->special_note ?? '-' }}</p>
        </x-filament::card>

    </div>
</x-filament::page>

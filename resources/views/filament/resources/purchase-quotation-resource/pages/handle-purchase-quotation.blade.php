<x-filament::page>
    <div class="space-y-6">

        {{-- Status --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <span class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-medium">
                    Status: {{ ucfirst($this->record->status) }}
                </span>
            </div>
        </x-filament::card>

        {{-- Basic Info --}}
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-800 dark:text-gray-200">
                <div><strong>Supplier Quotation ID:</strong> {{ $this->record->id }}</div>
                <div><strong>Supplier ID:</strong> {{ $this->record->supplier?->supplier_id ?? 'N/A' }}</div>
                <div><strong>Supplier Name:</strong> {{ $this->record->supplier?->name ?? 'N/A' }}</div>
                <div><strong>RFQ:</strong> {{ $this->record->rfq ? "#{$this->record->rfq->id} | {$this->record->rfq->random_code}" : 'N/A' }}</div>
                <div><strong>Quotation Date:</strong> {{ $this->record->created_at?->format('Y-m-d') ?? 'N/A' }}</div>
                <div><strong>Wanted Delivery Date:</strong> {{ $this->record->wanted_delivery_date?->format('Y-m-d') ?? 'N/A' }}</div>
                <div class="md:col-span-2"><strong>Special Note:</strong> {{ $this->record->special_note ?? '-' }}</div>
            </div>
        </x-filament::card>

        {{-- Totals --}}
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">Totals</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-gray-800 dark:text-gray-200">
                <div><strong>Order Subtotal:</strong> {{ number_format($this->record->order_subtotal, 2) }}</div>
                <div><strong>VAT Amount:</strong> {{ number_format($this->record->vat_amount, 2) }}</div>
                <div><strong>Grand Total:</strong> {{ number_format($this->record->grand_total, 2) }}</div>
                <div><strong>VAT Base:</strong> {{ ucfirst($this->record->vat_base) }}</div>
            </div>
        </x-filament::card>

        {{-- Items --}}
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">Items</h2>
            <div class="overflow-x-auto border rounded border-gray-200 dark:border-gray-700">
                <table class="w-full table-auto text-left text-gray-800 dark:text-gray-200">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Item</th>
                            <th class="px-4 py-2">Quantity</th>
                            <th class="px-4 py-2">Price</th>
                            <th class="px-4 py-2">Subtotal</th>
                            <th class="px-4 py-2">VAT Amount</th>
                            <th class="px-4 py-2">Item Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->items as $index => $item)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700' }}">
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $item->quantity }}</td>
                                <td class="px-4 py-2">{{ number_format($item->price, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($item->item_subtotal, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($item->item_vat_amount, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($item->item_grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2 text-center" colspan="7">No items added.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>

        {{-- Supplier Quotation Details --}}
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">Supplier Quotation Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-800 dark:text-gray-200">
                <div><strong>Supplier Quotation Number:</strong> {{ $this->record->supplier_quotation_number ?? '-' }}</div>
                <div><strong>Received Date:</strong> {{ $this->record->received_date?->format('Y-m-d') ?? '-' }}</div>
                <div><strong>Estimated Delivery Date:</strong> {{ $this->record->estimated_delivery_date?->format('Y-m-d') ?? '-' }}</div>
                <div class="md:col-span-2"><strong>Notes:</strong> {{ $this->record->supplier_note ?? '-' }}</div>
                <div class="md:col-span-2">
                    <strong>Quotation Image:</strong><br>
                    @if($this->record->image_of_quotation)
                        <img src="{{ \Storage::url($this->record->image_of_quotation) }}" alt="Quotation Image" class="max-w-xs mt-2 rounded border">
                    @else
                        <span>-</span>
                    @endif
                </div>
            </div>
        </x-filament::card>

    </div>
</x-filament::page>

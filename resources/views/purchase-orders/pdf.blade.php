// resources/views/purchase-orders/pdf.blade.php

<h1>Purchase Order #{{ $purchaseOrder->id }}</h1>

<p>Provider: {{ $purchaseOrder->provider_name }}</p>
<p>Provider Email: {{ $purchaseOrder->provider_email }}</p>
<p>Provider Phone: {{ $purchaseOrder->provider_phone }}</p>
<p>Wanted Delivery Date: {{ $purchaseOrder->wanted_date }}</p>
<p>Special Note: {{ $purchaseOrder->special_note }}</p>

<h2>Items:</h2>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Quantity</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchaseOrder->items as $item)
            <tr>
                <td>{{ $item->inventoryItem->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->price }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
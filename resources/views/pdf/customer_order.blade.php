<!DOCTYPE html>
<html>
<head>
    <title>Customer Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .variation-header {
            font-weight: bold;
            background-color: #e6f7ff;
        }
        .variation-row td {
            padding-left: 20px;
            font-style: italic;
        }
        .footer {
            margin-top: 20px;
            font-weight: bold;
            text-align: right; /* Align the grand total to the right */
        }
        .right-align {
            text-align: right; /* Align price, total, and grand total to the right */
        }
    </style>
</head>
<body>
    <h1>Customer Order</h1>
    <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
    <p><strong>Customer Name:</strong> {{ $order->customer->name }}</p>
    <p><strong>Order Name:</strong> {{ $order->name }}</p>
    <p><strong>Wanted Delivery Date:</strong> {{ $order->wanted_delivery_date }}</p>
    <p><strong>Special Notes:</strong> {{ $order->special_notes }}</p>
    <p><strong>Generated By:</strong> {{ auth()->user()->email }}</p>
    <p><strong>Generated Date & Time:</strong> {{ now() }}</p>

    <h2>Order Items</h2>
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <!-- Non-Variation Items -->
            @foreach ($orderDescriptions as $description)
                @if (!$description->is_variation)
                    <tr>
                        <td>{{ $description->item_name }}</td>
                        <td>{{ $description->quantity }}</td>
                        <td class="right-align">{{ $description->price }}</td>
                        <td class="right-align">{{ $description->total }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- Variation Items -->
    <h2>Variation Items</h2>
    <table>
        <thead>
            <tr>
                <th>Parent Item Name</th>
                <th>Variation Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orderDescriptions as $description)
                @if ($description->is_variation)
                    <!-- Parent Item Row (only once per item) -->
                    <tr class="variation-header">
                        <td colspan="5">{{ $description->item_name }}</td>
                    </tr>
                    <!-- Variation Rows for each variation of this parent item -->
                    @foreach ($description->variationItems as $variation)
                        <tr class="variation-row">
                            <td></td> <!-- Leave empty for the parent name -->
                            <td>{{ $variation->variation_name }}</td>
                            <td>{{ $variation->quantity }}</td>
                            <td class="right-align">{{ $variation->price }}</td>
                            <td class="right-align">{{ $variation->total }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- Grand Total -->
    <div class="footer">
        <p><strong>Grand Total:</strong> {{ $grandTotal }}</p>
    </div>
</body>
</html>

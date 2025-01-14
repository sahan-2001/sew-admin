<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #{{ $purchaseOrder->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        header {
            text-align: center;
            margin-bottom: 30px;
        }
        header h1 {
            margin: 0;
        }
        header p {
            margin: 5px 0;
        }
        .details, .footer {
            margin-top: 20px;
        }
        .details {
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .footer {
            text-align: right;
            font-size: 0.9em;
            color: #555;
        }
    </style>
</head>
<body>
    <!-- Company Header -->
    <header>
        <h1>ABC Apparels (Pvt) LTD</h1>
        <p>Damunugolla, Ibbagamuwa, Kurunegala, Sri Lanka</p>
        <p>Phone: 08634262829 | Email: abc@gmail.com</p>
    </header>

    <!-- Document Details -->
    <div class="details">
        <p><strong>Purchase Order #:</strong> {{ $purchaseOrder->id }}</p>
        <p><strong>PDF Generated Date and Time:</strong> {{ now()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') }}</p>
    </div>

    <!-- Provider Information -->
    <section class="details">
        <h2>Provider Details</h2>
        <p><strong>Name:</strong> {{ $purchaseOrder->provider_name ?? 'N/A' }}</p>
        <p><strong>ID:</strong> {{ $purchaseOrder->provider_id ?? 'N/A' }}</p>
        <p><strong>Email:</strong> {{ $purchaseOrder->provider_email ?? 'N/A' }}</p>
        <p><strong>Phone:</strong> {{ $purchaseOrder->provider_phone ?? 'N/A' }}</p>
    </section>

    <!-- Wanted Delivery Date and Special Note -->
    <section class="details">
        <p><strong>Wanted Delivery Date:</strong> {{ $purchaseOrder->wanted_date ? \Carbon\Carbon::parse($purchaseOrder->wanted_date)->format('Y-m-d') : 'N/A' }}</p>
        <p><strong>Special Note:</strong> {{ $purchaseOrder->special_note ?? 'None' }}</p>
    </section>

    <!-- Items Table -->
    <h2>Item List</h2>
    @if ($purchaseOrder->items->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th scope="col">Item Code</th>
                    <th scope="col">Item Name</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Price</th>
                    <th scope="col">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseOrder->items as $item)
                    <tr>
                        <td>{{ $item->inventoryItem->item_code }}</td>
                        <td>{{ $item->inventoryItem->name ?? 'Unnamed Item' }}</td>
                        <td>{{ $item->quantity ?? 0 }}</td>
                        <td>{{ number_format($item->price, 2) ?? '0.00' }}</td>
                        <td>{{ number_format($item->quantity * $item->price, 2) ?? '0.00' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Total Amount</strong></td>
                    <td>
                        {{ number_format($purchaseOrder->items->sum(fn($item) => $item->quantity * $item->price), 2) ?? '0.00' }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @else
        <p>No items found for this purchase order.</p>
    @endif

    <!-- Footer -->
    <footer class="footer">
        <p>Thank you for dealing with us - ABC Apparels (Pvt) LTD</p>
    </footer>
</body>
</html>

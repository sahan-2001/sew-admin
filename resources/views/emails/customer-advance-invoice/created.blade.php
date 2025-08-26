<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Advance Invoice</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family:'Poppins', sans-serif; background:#f7f9fc; color:#333; padding:20px; }
    .email-container { max-width:700px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.08);}
    .header { background:#1e40af; color:#fff; padding:20px; text-align:center; border-radius:10px 10px 0 0; }
    .header h1 { margin:0; font-size:22px; }
    .content { margin-top:20px; }
    .section { background:#f1f5f9; padding:20px; border-radius:10px; margin-bottom:20px; }
    .section h2 { margin-bottom:15px; color:#1e293b; font-size:18px; }
    .details .label { font-weight:500; min-width:150px; display:inline-block; color:#475569; }
    .details .value { font-weight:500; color:#1e293b; }
    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th, td { padding:10px; text-align:left; border-bottom:1px solid #e2e8f0; font-size:14px; }
    th { background:#2563eb; color:white; font-weight:600; }
</style>
</head>
<body>
<div class="email-container">

    <!-- Header -->
    <div class="header">
        <h1>{{ $companyDetails['name'] ?? 'Company Name' }}</h1>
        <p>{{ $companyDetails['address'] ?? 'Company Address' }}</p>
        <p>Phone: {{ $companyDetails['phone'] ?? 'N/A' }} | Email: {{ $companyDetails['email'] ?? 'N/A' }}</p>
    </div>

    <div class="content">
        <h2>Dear {{ $customer->name ?? 'Customer' }},</h2>
        <p>Your advance invoice <strong>#{{ $invoice->id }}</strong> has been recorded successfully.</p>

        <!-- Invoice Info -->
        <div class="section details">
            <h2>Invoice Details</h2>
            <div><span class="label">Invoice ID:</span> <span class="value">{{ $invoice->id }}</span></div>
            <div><span class="label">Received Amount:</span> <span class="value">Rs. {{ number_format($invoice->amount, 2) }}</span></div>
            <div><span class="label">Payment Received Date:</span> <span class="value">{{ $invoice->paid_date ? \Carbon\Carbon::parse($invoice->paid_date)->format('d M Y') : '-' }}</span></div>
            <div><span class="label">Payment Method:</span> <span class="value">{{ ucfirst($invoice->paid_via ?? '-') }}</span></div>
            <div><span class="label">Payment Reference:</span> <span class="value">{{ $invoice->payment_reference ?? '-' }}</span></div>
            <div><span class="label">Customer's Invoice Number:</span> <span class="value">{{ $invoice->cus_invoice_number ?? '-' }}</span></div>
            <div><span class="label">Status:</span> <span class="value">{{ ucfirst($invoice->status ?? '-') }}</span></div>
        </div>

        <!-- Customer Info -->
        <div class="section details">
            <h2>Customer Details</h2>
            <div><span class="label">Customer ID:</span> <span class="value">{{ $customer->customer_id ?? '-' }}</span></div>
            <div><span class="label">Name:</span> <span class="value">{{ $customer->name ?? '-' }}</span></div>
            <div><span class="label">Shop Name:</span> <span class="value">{{ $customer->shop_name ?? '-' }}</span></div>
            <div><span class="label">Email:</span> <span class="value">{{ $customer->email ?? '-' }}</span></div>
            <div><span class="label">Phone:</span> <span class="value">{{ $customer->phone_1 ?? '-' }}{{ $customer->phone_2 ? ', '.$customer->phone_2 : '' }}</span></div>
        </div>

        <!-- Order Info -->
        @if($order)
        <div class="section details">
            <h2>Order Details</h2>
            <div><span class="label">Order ID:</span> <span class="value">{{ $order->order_id ?? '-' }}</span></div>
            <div><span class="label">Name:</span> <span class="value">{{ $order->name ?? '-' }}</span></div>
            <div><span class="label">Wanted Delivery Date:</span> <span class="value">{{ $order->wanted_delivery_date ? \Carbon\Carbon::parse($order->wanted_delivery_date)->format('d M Y') : '-' }}</span></div>
            <div><span class="label">Status:</span> <span class="value">{{ ucfirst($order->status ?? '-') }}</span></div>
            <div><span class="label">Grand Total:</span> <span class="value">Rs. {{ number_format($order->grand_total ?? 0, 2) }}</span></div>
            <div><span class="label">Remaining Balance:</span> <span class="value">Rs. {{ number_format($order->remaining_balance ?? 0, 2) }}</span></div>
        </div>

        <!-- Order Items -->
        <div class="section">
            <h2>Order Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Variation</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items ?? $order->orderItems ?? [] as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->item_name ?? $item->name ?? '-' }}</td>
                            <td>
                                @if(isset($item->variationItems) && $item->variationItems->count())
                                    @foreach($item->variationItems as $var)
                                        {{ $var->variation_name ?? '-' }} ({{ $var->quantity }} x Rs. {{ number_format($var->price, 2) }})<br>
                                    @endforeach
                                @elseif(isset($item->variation_name))
                                    {{ $item->variation_name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->quantity ?? 1 }}</td>
                            <td>Rs. {{ number_format($item->price ?? 0, 2) }}</td>
                            <td>Rs. {{ number_format($item->total ?? ($item->quantity * ($item->price ?? 0)), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <p>Thank you for your payment. If you have any questions, please contact us.</p>
    </div>

</div>
</body>
</html>

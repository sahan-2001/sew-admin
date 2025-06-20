<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Receipt</h1>
        <p>Supplier Advance Invoice #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</p>
        @if($payment)
            <p>Payment #{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</p>
        @endif
    </div>

    <div class="details">
        <h3>Supplier Invoice Details</h3>
        <table>
            <tr>
                <th>PO Number</th>
                <td>{{ str_pad($invoice->purchase_order_id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Current Status</th>
                <td>{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</td>
            </tr>
        </table>
    </div>

    <div class="payment-details">
        <h3>Payment Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($payment)
                <tr>
                    <td>Payment Amount</td>
                    <td>Rs. {{ number_format($payment->payment_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Payment Method</td>
                    <td>{{ ucfirst($payment->payment_method) }}</td>
                </tr>
                <tr>
                    <td>Payment Reference</td>
                    <td>{{ $payment->payment_reference ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Payment Date</td>
                    <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                <tr>
                    <td>Remaining Before</td>
                    <td>Rs. {{ number_format($payment->remaining_amount_before, 2) }}</td>
                </tr>
                <tr>
                    <td>Remaining After</td>
                    <td>Rs. {{ number_format($payment->remaining_amount_after, 2) }}</td>
                </tr>
                @else
                <!-- Fallback to show all payments if no specific payment provided -->
                @foreach($invoice->payments as $payment)
                <tr>
                    <td colspan="2"><strong>Payment #{{ $payment->id }}</strong></td>
                </tr>
                <tr>
                    <td>Amount</td>
                    <td>Rs. {{ number_format($payment->payment_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Method</td>
                    <td>{{ ucfirst($payment->payment_method) }}</td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if($payment && $payment->notes)
    <div class="notes">
        <h3>Payment Notes</h3>
        <p>{{ $payment->notes }}</p>
    </div>
    @endif
</body>
</html>
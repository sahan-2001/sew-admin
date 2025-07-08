<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Details PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        h2, h3 {
            margin: 10px 0;
        }

        .section {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            padding: 8px;
            border: 1px solid #222;
            text-align: left;
        }

        .qr {
            text-align: center;
            margin-top: 30px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h3>Customer Summary Report</h3>

    <h4>Company Information</h4>
    <p>
        <strong>Name:</strong> {{ $companyDetails['name'] }}<br>
        <strong>Address:</strong> {{ $companyDetails['address'] }}<br>
        <strong>Phone:</strong> {{ $companyDetails['phone'] }}<br>
        <strong>Email:</strong> {{ $companyDetails['email'] }}
    </p>

    <h4>Customer Information</h4>
        <table>
            <tbody>
                <tr>
                    <th>ID</th>
                    <td>{{ $customerDetails['id'] }}</td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $customerDetails['name'] }}</td>
                </tr>
                <tr>
                    <th>Shop Name</th>
                    <td>{{ $customerDetails['shop_name'] }}</td>
                </tr>
                <tr>
                    <th>Address Line 1</th>
                    <td>{{ $customerDetails['address_line_1'] }}</td>
                </tr>
                <tr>
                    <th>Address Line 2</th>
                    <td>{{ $customerDetails['address_line_2'] }}</td>
                </tr>
                <tr>
                    <th>City</th>
                    <td>{{ $customerDetails['city'] }}</td>
                </tr>
                <tr>
                    <th>Phone 1</th>
                    <td>{{ $customerDetails['phone_1'] }}</td>
                </tr>
                <tr>
                    <th>Phone 2</th>
                    <td>{{ $customerDetails['phone_2'] }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $customerDetails['email'] }}</td>
                </tr>
                <tr>
                    <th>Outstanding Balance</th>
                    <td>{{ number_format($customerDetails['outstanding_balance'], 2) }}</td>
                </tr>
                <tr>
                    <th>Created At</th>
                    <td>{{ $customerDetails['created_at'] }}</td>
                </tr>
            </tbody>
        </table>


    <br>
    <h3>Purchase Order Advance Invoices</h3>

    @if ($advanceInvoices->isEmpty())
        <p>No advance invoices found for this supplier.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PO ID</th>
                    <th>Status</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Paid Date</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($advanceInvoices as $invoice)
                    <tr>
                        <td>{{ $invoice->id }}</td>
                        <td>{{ $invoice->purchase_order_id }}</td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                        <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                        <td>{{ number_format($invoice->remaining_amount, 2) }}</td>
                        <td>{{ $invoice->paid_date ? \Carbon\Carbon::parse($invoice->paid_date)->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach

                @php
                    $totalPaid = $advanceInvoices->sum('paid_amount');
                    $totalRemaining = $advanceInvoices->sum('remaining_amount');
                @endphp

                <tr style="font-weight: bold;">
                    <td colspan="3" align="right">Total</td>
                    <td>{{ number_format($totalPaid, 2) }}</td>
                    <td>{{ number_format($totalRemaining, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    @endif

    <br>
    <h3>Purchase Order Final Invoices</h3>

    @if ($poInvoices->isEmpty())
        <p>No purchase order invoices found for this supplier.</p>
    @else
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PO ID</th>
                    <th>Status</th>
                    <th>Grand Total</th>
                    <th>Adv Paid</th>
                    <th>Total Due</th>
                    <th>Due for Now</th>
                    <th>Paid</th> {{-- New calculated column --}}
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalGrand = 0;
                    $totalAdvPaid = 0;
                    $totalDuePayment = 0;
                    $totalDueForNow = 0;
                    $totalPaid = 0; // new total for calculated Paid
                @endphp

                @foreach ($poInvoices as $invoice)
                    @php
                        $paid = $invoice->due_payment - $invoice->due_payment_for_now;
                        $totalGrand += $invoice->grand_total;
                        $totalAdvPaid += $invoice->adv_paid;
                        $totalDuePayment += $invoice->due_payment;
                        $totalDueForNow += $invoice->due_payment_for_now;
                        $totalPaid += $paid;
                    @endphp
                    <tr>
                        <td>{{ $invoice->id }}</td>
                        <td>{{ $invoice->purchase_order_id }}</td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                        <td>{{ number_format($invoice->grand_total, 2) }}</td>
                        <td>{{ number_format($invoice->adv_paid, 2) }}</td>
                        <td>{{ number_format($invoice->due_payment, 2) }}</td>
                        <td>{{ number_format($invoice->due_payment_for_now, 2) }}</td>
                        <td>{{ number_format($paid, 2) }}</td> {{-- Display calculated Paid --}}
                        <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach

                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="3" align="right">Total</td>
                    <td>{{ number_format($totalGrand, 2) }}</td>
                    <td>{{ number_format($totalAdvPaid, 2) }}</td>
                    <td>{{ number_format($totalDuePayment, 2) }}</td>
                    <td>{{ number_format($totalDueForNow, 2) }}</td>
                    <td>{{ number_format($totalPaid, 2) }}</td> {{-- Total calculated Paid --}}
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif

    <br>
    <h3>Customer Order/ Sample Order Invoices</h3>

    @if ($customerInvoices->isEmpty())
        <p>No customer advance invoices found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order Type</th>
                    <th>Order ID</th>
                    <th>Invoice No</th>
                    <th>Amount</th>
                    <th>Paid Date</th>
                    <th>Paid Via</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalCustomerAdvance = 0;
                @endphp

                @foreach ($customerInvoices as $invoice)
                    @php
                        $totalCustomerAdvance += $invoice->amount;
                    @endphp
                    <tr>
                        <td>{{ $invoice->id }}</td>
                        <td>{{ ucfirst($invoice->order_type) }}</td>
                        <td>{{ $invoice->order_id }}</td>
                        <td>{{ $invoice->cus_invoice_number ?? 'N/A' }}</td>
                        <td>{{ number_format($invoice->amount, 2) }}</td>
                        <td>{{ $invoice->paid_date ? \Carbon\Carbon::parse($invoice->paid_date)->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ $invoice->paid_via ?? 'N/A' }}</td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                        <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach

                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="4" align="right">Total</td>
                    <td>{{ number_format($totalCustomerAdvance, 2) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>
    @endif

    @php
        // Calculate combined totals:
        $combinedTotalPaid = $advanceInvoices->sum('paid_amount')
                            + $poInvoices->sum('adv_paid')  // assuming 'adv_paid' is amount paid in PO final invoices
                            + $customerInvoices->sum('amount');

        // For remaining, sum remaining_amount in advanceInvoices and
        // due_payment in PO invoices minus adv_paid (or use relevant fields)

        // You might need to customize this depending on your logic.
        // Here's an example approximation:
        $combinedTotalRemaining = $advanceInvoices->sum('remaining_amount')
                                + ($poInvoices->sum('due_payment') - $poInvoices->sum('adv_paid'))
                                + 0; // no remaining for customer invoices assumed
    @endphp

    <br>
    <h3>Total Summary</h3>
    <table border="1" cellpadding="5" cellspacing="0" style="width: 300px;">
        <tbody>
            <tr>
                <th style="text-align: left;">Total Paid</th>
                <td style="text-align: right;">{{ number_format($combinedTotalPaid, 2) }}</td>
            </tr>
            <tr>
                <th style="text-align: left;">Total Remaining</th>
                <td style="text-align: right;">{{ number_format($combinedTotalRemaining, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
    <div style="text-align: center; font-size: 10px; color: #666; margin-top: 40px;">
        No signature section needed as this is a generated report by the system.
    </div>

</body>
</html>

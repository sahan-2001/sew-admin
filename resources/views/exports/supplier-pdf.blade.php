<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Details PDF</title>
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

    <h2>{{ $companyDetails['name'] }}</h2>
    <p>{{ $companyDetails['address'] }}</p>
    <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>

    <hr>

    <div class="section">
        <h3>Supplier Information</h3>
        <table>
            <tr><th>Supplier ID</th><td>{{ $supplierDetails['id'] }}</td></tr>
            <tr><th>Name</th><td>{{ $supplierDetails['name'] }}</td></tr>
            <tr><th>Shop Name</th><td>{{ $supplierDetails['shop_name'] }}</td></tr>
            <tr><th>Address</th><td>{{ $supplierDetails['address'] }}</td></tr>
            <tr><th>Phone 1</th><td>{{ $supplierDetails['phone_1'] }}</td></tr>
            <tr><th>Phone 2</th><td>{{ $supplierDetails['phone_2'] }}</td></tr>
            <tr><th>Email</th><td>{{ $supplierDetails['email'] }}</td></tr>
            <tr><th>Created At</th><td>{{ $supplierDetails['created_at'] }}</td></tr>
        </table>
    </div>


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
    <h3>Third Party Services</h3>

    @php
        $totalServiceAmount = 0;
        $totalPaidAmount = 0;
        $totalRemainingBalance = 0;
    @endphp

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Service Name</th>
                <th>Service Total</th>
                <th>Paid</th>
                <th>Remaining Balance</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($thirdPartyServices as $service)
                @php
                    $totalServiceAmount += $service->service_total;
                    $totalPaidAmount += $service->paid;
                    $totalRemainingBalance += $service->remaining_balance;
                @endphp
                <tr>
                    <td>{{ $service->id }}</td>
                    <td>{{ $service->name }}</td>
                    <td>{{ number_format($service->service_total, 2) }}</td>
                    <td>{{ number_format($service->paid, 2) }}</td>
                    <td>{{ number_format($service->remaining_balance, 2) }}</td>
                    <td>{{ ucfirst($service->status) }}</td>
                    <td>{{ $service->created_at->format('Y-m-d') }}</td>
                </tr>
            @endforeach

            <tr>
                <td colspan="2"><strong>Totals</strong></td>
                <td><strong>{{ number_format($totalServiceAmount, 2) }}</strong></td>
                <td><strong>{{ number_format($totalPaidAmount, 2) }}</strong></td>
                <td><strong>{{ number_format($totalRemainingBalance, 2) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>


    @php
        // Calculate combined totals
        $totalPaidOverall = $advanceInvoices->sum('paid_amount') + $poInvoices->sum('paid') + $thirdPartyServices->sum('paid');
        $totalRemainingOverall = $advanceInvoices->sum('remaining_amount') + $poInvoices->sum('due_payment_for_now') + $thirdPartyServices->sum('remaining_balance');
    @endphp

    <br>
    <h3>Total Summary</h3>
    <table border="1" cellpadding="5" cellspacing="0" style="width: 300px;">
        <tbody>
            <tr>
                <th style="text-align: left;">Total Paid</th>
                <td style="text-align: right;">{{ number_format($totalPaidOverall, 2) }}</td>
            </tr>
            <tr>
                <th style="text-align: left;">Total Remaining</th>
                <td style="text-align: right;">{{ number_format($totalRemainingOverall, 2) }}</td>
            </tr>
        </tbody>
    </table>





    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

</body>
</html>

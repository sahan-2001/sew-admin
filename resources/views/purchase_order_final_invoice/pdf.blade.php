<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Purchase Order Invoice - {{ $invoiceDetails['id'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header, .footer {
            text-align: center;
        }
        .details, .provider-details {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
        }
        .details th, .details td, .provider-details th, .provider-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .details th, .provider-details th {
            background-color: #f2f2f2;
        }
        .items th {
            text-align: center;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .items th, .items td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items th {
            background-color: #f2f2f2;
        }
        .items tfoot th {
            text-align: right;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature div {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px dotted black;
            margin: 10px auto;
            width: 80%;
        }
        .qr-code {
            width: 48%;
            float: right;
            text-align: center;
            margin-top: 20px;
        }
        .clear {
            clear: both;
        }
        .section-title {
            margin-top: 30px;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
        <h2>Final Purchase Order Invoice</h2>
    </div>

    <div class="provider-details">
        <h3>Invoice Details</h3>
        <table>
            <tr>
                <th>Invoice ID</th>
                <td>{{ str_pad($invoiceDetails['id'], 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>PO ID</th>
                <td>{{ str_pad($invoiceDetails['purchase_order_id'], 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <th>Register Arrival ID</th>
                <td>{{ $invoiceDetails['register_arrival_id'] }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $invoiceDetails['status'] }}</td>
            </tr>
            <tr>
                <th>Created Date</th>
                <td>{{ $invoiceDetails['created_at'] }}</td>
            </tr>
        </table>
    </div>

    <div class="details">
        <h3>Provider Details</h3>
        <table>
            <tr>
                <th>Provider Type</th>
                <td>{{ $providerDetails['type'] }}</td>
            </tr>
            <tr>
                <th>Provider ID</th>
                <td>{{ $providerDetails['id'] }}</td>
            </tr>
            <tr>
                <th>Provider Name</th>
                <td>{{ $providerDetails['name'] }}</td>
            </tr>
        </table>
    </div>

    <div class="qr-code">
        <h4>Scan to View Invoice</h4>
        <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" 
            width="100" height="100"
            alt="QR Code for Invoice {{ $invoiceDetails['id'] }}"
            style="width: 100px; height: 100px; border: 1px solid #eee;">
        <div style="margin-top: 10px;">
            <a href="{{ $qrCodeData }}" 
            style="font-size: 12px; color: #3490dc; text-decoration: none;">
            View Invoice Online
            </a>
        </div>
    </div>

    <div class="clear"></div>

    <div class="section-title">Invoice Items</div>
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Location</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoiceItems as $item)
                <tr>
                    <td>{{ $item['item_id'] }}</td>
                    <td>{{ $item['item_code'] }}</td>
                    <td>{{ $item['item_name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['unit_price'], 2) }}</td>
                    <td>{{ $item['location'] }}</td>
                    <td>{{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" style="text-align: right;">Subtotal</th>
                    <th>{{ number_format($invoiceDetails['grand_total'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(count($additionalCosts) > 0)
    <div class="section-title">Additional Costs</div>
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Unit Rate</th>
                    <th>Quantity</th>
                    <th>UOM</th>
                    <th>Date</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($additionalCosts as $cost)
                <tr>
                    <td>{{ $cost['description'] }}</td>
                    <td>{{ number_format($cost['unit_rate'], 2) }}</td>
                    <td>{{ $cost['quantity'] }}</td>
                    <td>{{ $cost['uom'] }}</td>
                    <td>{{ $cost['date'] }}</td>
                    <td>{{ number_format($cost['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right;">Total Additional Costs</th>
                    <th>{{ number_format($invoiceDetails['additional_cost'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if(count($discountsDeductions) > 0)
    <div class="section-title">Discounts & Deductions</div>
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Unit Rate</th>
                    <th>Quantity</th>
                    <th>UOM</th>
                    <th>Date</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($discountsDeductions as $discount)
                <tr>
                    <td>{{ $discount['description'] }}</td>
                    <td>{{ number_format($discount['unit_rate'], 2) }}</td>
                    <td>{{ $discount['quantity'] }}</td>
                    <td>{{ $discount['uom'] }}</td>
                    <td>{{ $discount['date'] }}</td>
                    <td>{{ number_format($discount['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right;">Total Discounts/Deductions</th>
                    <th>{{ number_format($invoiceDetails['discount'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if(count($advanceInvoices) > 0)
    <div class="section-title">Advance Payments Applied</div>
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Advance Invoice ID</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Paid Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($advanceInvoices as $advance)
                <tr>
                    <td>{{ $advance['advance_invoice_id'] }}</td>
                    <td>{{ $advance['type'] }}</td>
                    <td>{{ number_format($advance['amount'], 2) }}</td>
                    <td>{{ $advance['paid_date'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" style="text-align: right;">Total Advance Payments</th>
                    <th colspan="2">{{ number_format($invoiceDetails['adv_paid'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <div class="section-title">Payment Summary</div>
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Items Subtotal</td>
                    <td>{{ number_format($invoiceDetails['grand_total'], 2) }}</td>
                </tr>
                <tr>
                    <td>Additional Costs</td>
                    <td>{{ number_format($invoiceDetails['additional_cost'], 2) }}</td>
                </tr>
                <tr>
                    <td>Discounts & Deductions</td>
                    <td>-{{ number_format($invoiceDetails['discount'], 2) }}</td>
                </tr>
                <tr>
                    <td>Advance Payments Applied</td>
                    <td>-{{ number_format($invoiceDetails['adv_paid'], 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th style="text-align: right;">Payment Due</th>
                    <th>{{ number_format($invoiceDetails['due_payment'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(count($invoicePayments) > 0)
    <div class="section-title">Payment Records</div>
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Remaining Before</th>
                    <th>Remaining After</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Notes</th>
                    <th>Paid By</th>
                    <th>Paid At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoicePayments as $payment)
                    <tr>
                        <td>{{ number_format($payment['amount'], 2) }}</td>
                        <td>{{ number_format($payment['remaining_before'], 2) }}</td>
                        <td>{{ number_format($payment['remaining_after'], 2) }}</td>
                        <td>{{ $payment['method'] }}</td>
                        <td>{{ $payment['reference'] }}</td>
                        <td>{{ $payment['notes'] }}</td>
                        <td>{{ $payment['paid_by'] }}</td>
                        <td>{{ $payment['paid_at'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif


    
    <div class="signature">
        <div style="flex: 1; text-align: left;">
            <p>Company Representative</p>
            <div class="signature-line"></div>
        </div>
        <div style="flex: 1; text-align: right;">
            <p>Supplier Representative</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
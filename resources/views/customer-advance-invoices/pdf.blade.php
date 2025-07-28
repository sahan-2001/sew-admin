<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #{{ $invoiceDetails['id'] }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background-color: white;
            margin: 0;
            padding: 15mm;
            box-sizing: border-box;
        }
        .invoice-container {
            width: 100%;
            height: 277mm; /* A4 height minus padding */
            display: flex;
            flex-direction: column;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .company-name {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .company-details {
            color: #7f8c8d;
            margin-bottom: 10px;
            font-size: 11px;
        }
        .success-banner {
            background-color: #2ecc71;
            color: white;
            padding: 10px;
            border-radius: 3px;
            text-align: center;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .success-icon {
            margin-right: 8px;
            font-size: 18px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .info-table td {
            padding: 6px 3px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-table td.label {
            font-weight: bold;
            width: 35%;
            color: #7f8c8d;
        }
        .amount-highlight {
            font-weight: bold;
            color: #2ecc71;
            font-size: 13px;
        }
        .footer {
            text-align: center;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #eee;
            color: #7f8c8d;
            font-size: 10px;
        }
        .thank-you {
            font-size: 13px;
            color: #2c3e50;
            text-align: center;
            margin: 15px 0;
            font-style: italic;
        }
        @media print {
            body {
                padding: 10mm;
            }
            .invoice-container {
                height: auto;
                min-height: 277mm;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="invoice-container">

    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ $companyDetails['name'] }}</div>
        <div class="company-details">
            {{ $companyDetails['address'] }} | 
            Phone: {{ $companyDetails['phone'] }} | 
            Email: {{ $companyDetails['email'] }}
        </div>
        <h2 style="font-size:16px;margin:10px 0;">Payment Receipt #{{ $invoiceDetails['id'] }}</h2>
    </div>

    {{-- Success Banner --}}
    <div class="success-banner">
        <i class="fas fa-check-circle success-icon"></i>
        <div>
            <strong>Payment Successful!</strong> Thank you for your payment.
        </div>
    </div>

    {{-- Invoice Details --}}
    <div class="section">
        <h3 class="section-title">Payment Information</h3>
        <table class="info-table">
            <tr>
                <td class="label">Receipt Number:</td>
                <td>#{{ $invoiceDetails['id'] }}</td>
            </tr>
            <tr>
                <td class="label">Customer:</td>
                <td>{{ $invoiceDetails['customer_name'] }} (ID: {{ $invoiceDetails['customer_id'] }})</td>
            </tr>
            <tr>
                <td class="label">Order Reference:</td>
                <td>{{ $invoiceDetails['order_type'] }} #{{ $invoiceDetails['order_id'] }}</td>
            </tr>
            <tr>
                <td class="label">Customer's Invoice No:</td>
                <td>{{ $invoiceDetails['cus_invoice_number'] }}</td>
            </tr>
            <tr>
                <td class="label">Payment Date:</td>
                <td>{{ $invoiceDetails['paid_date'] }}</td>
            </tr>
            <tr>
                <td class="label">Payment Method:</td>
                <td>
                    {{ $invoiceDetails['paid_via'] }}
                    @if($invoiceDetails['payment_reference'])
                        <br>(Ref: {{ $invoiceDetails['payment_reference'] }})
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Invoice Amount:</td>
                <td>Rs. {{ number_format($invoiceDetails['grand_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Amount Paid:</td>
                <td class="amount-highlight">Rs. {{ number_format($invoiceDetails['amount'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Payment Status:</td>
                <td>
                    <span style="color: #2ecc71; font-weight: bold;">
                        <i class="fas fa-check-circle" style="font-size:12px;"></i> {{ $invoiceDetails['status'] }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="thank-you">
        Thank you for your business!
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>This is an automated receipt. Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>For any inquiries, please contact {{ $companyDetails['email'] }}</p>
    </div>

</div>

</body>
</html>
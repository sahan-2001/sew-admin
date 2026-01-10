<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request for Quotation {{ str_pad($rfqDetails['id'], 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { 
            font-family: 'Times New Roman', serif; 
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #000;
        }
        
        .letter-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            position: relative;
        }
        
        .letterhead {
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            text-align: left;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .company-contact {
            font-size: 13px;
            color: #666;
        }
        
        .letter-date {
            text-align: right;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .recipient-info {
            margin-bottom: 30px;
        }
        
        .recipient-name {
            font-weight: bold;
        }
        
        .subject-line {
            font-weight: bold;
            font-size: 16px;
            margin: 30px 0 20px 0;
            text-decoration: underline;
        }
        
        .letter-body {
            margin: 20px 0;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }
        
        th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .delivery-info {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-left: 3px solid #000;
            font-size: 13px;
        }
        
        .closing {
            margin-top: 40px;
        }
        
        .signature-block {
            margin-top: 50px;
        }
        
        .signature {
            margin-top: 40px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin: 40px 0 5px 0;
        }
        
        .signature-details {
            font-size: 13px;
            margin-top: 5px;
        }
        
        .footer {
            margin-top: 50px;
            font-size: 12px;
            color: #666;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        
        .qr-section {
            float: right;
            text-align: center;
            margin-left: 20px;
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #eee;
            background-color: #fafafa;
        }
        
        .qr-text {
            font-size: 11px;
            margin-top: 5px;
            color: #333;
        }
        
        .reference-number {
            font-weight: bold;
            color: #000;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<div class="letter-container">
    
    <!-- Letterhead -->
    <div class="letterhead">
        <div class="company-info">
            <div class="company-name">{{ $companyDetails['name'] }}</div>
            <div class="company-address">{{ $companyDetails['address'] }}</div>
            <div class="company-contact">
                Tel: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}
            </div>
        </div>
    </div>
    
    <!-- Date -->
    <div class="letter-date">
        {{ date('F j, Y') }}
    </div>
    
    <!-- Recipient -->
    <div class="recipient-info">
        <div class="recipient-name">{{ $rfqDetails['supplier_name'] }}</div>
        <div>Supplier ID: {{ $rfqDetails['supplier_id'] }}</div>
        <div>{{ $rfqDetails['supplier_email'] }}</div>
        <div>{{ $rfqDetails['supplier_phone'] }}</div>
    </div>
    
    <!-- QR Code Section -->
    <div class="qr-section">
        <img src="{{ $qrCodePath }}" width="80" height="80">
        <div class="qr-text">RFQ #{{ str_pad($rfqDetails['id'], 5, '0', STR_PAD_LEFT) }}</div>
        <div class="qr-text"><a href="{{ $qrCodeData }}" style="color:#000; text-decoration:none;">View Online</a></div>
    </div>
    
    <!-- Subject -->
    <div class="subject-line">
        REQUEST FOR QUOTATION - RFQ #{{ str_pad($rfqDetails['id'], 5, '0', STR_PAD_LEFT) }}
    </div>
    
    <!-- Letter Body -->
    <div class="letter-body">
        <p>Dear Valued Supplier,</p>
        
        <p>We are pleased to invite you to submit a quotation for the items listed below. This Request for Quotation (RFQ) represents our requirements for the specified goods.</p>
        
        <p>Please review the following details carefully and submit your quotation by <strong>{{ $rfqDetails['valid_until'] ?? 'the specified deadline' }}</strong>.</p>
        
        <!-- RFQ Details -->
        <div class="reference-number">
            RFQ Reference: {{ str_pad($rfqDetails['id'], 5, '0', STR_PAD_LEFT) }}
        </div>
        
        <!-- Delivery Information -->
        <div class="delivery-info">
            <strong>Required Delivery Date:</strong> {{ $rfqDetails['wanted_delivery_date'] }}<br>
            <strong>RFQ Valid Until:</strong> {{ $rfqDetails['valid_until'] ?? 'To be specified' }}
        </div>
        
        <!-- Items Table -->
        <p><strong>Items Requested:</strong></p>
        <table>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Description</th>
                    <th class="text-right">Quantity Required</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                    <td>{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Additional Instructions -->
        <p>Please include the following in your quotation:</p>
        <ul>
            <li>Unit price for each item</li>
            <li>Total price including all applicable taxes and charges</li>
            <li>Delivery terms and estimated time</li>
            <li>Payment terms</li>
            <li>Validity period of your quotation</li>
        </ul>
        
        <p>Your quotation should be submitted in writing and must reference our RFQ number shown above. We will evaluate all quotations received based on price, quality, delivery time, and terms.</p>
        
        <p>We look forward to receiving your competitive quotation. Should you require any clarification, please do not hesitate to contact us.</p>
    </div>
    
    <!-- Closing -->
    <div class="closing">
        <p>Yours sincerely,</p>
        
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-details">
                <strong>{{ $rfqDetails['created_by'] }}</strong><br>
                {{ $companyDetails['name'] }}<br>
                Date: {{ $rfqDetails['created_at'] }}
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>This document is electronically generated. RFQ #{{ str_pad($rfqDetails['id'], 5, '0', STR_PAD_LEFT) }} generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>{{ $companyDetails['name'] }} - All Rights Reserved</p>
    </div>
    
</div>

</body>
</html>
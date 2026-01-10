<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request for Quotation</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Poppins', sans-serif; line-height:1.6; color:#333; background-color:#f7f9fc; padding:20px; }
    .email-container { max-width:700px; margin:0 auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.08); }
    
    .company-header { background:#1e40af; color:white; text-align:center; padding:20px; }
    .company-header h1 { margin-bottom:5px; font-size:22px; font-weight:700; }
    .company-header p { margin:2px 0; font-size:14px; }

    .content { padding:35px; }
    .welcome-text { font-size:22px; font-weight:600; margin-bottom:20px; color:#1e293b; }
    .intro { font-size:16px; margin-bottom:25px; color:#475569; line-height:1.7; }

    .rfq-details, .items-table { background:#f1f5f9; border-radius:10px; padding:25px; margin-bottom:30px; }
    .details-title { font-size:18px; font-weight:600; margin-bottom:15px; color:#1e293b; }

    .detail-item { display:flex; margin-bottom:12px; }
    .detail-label { font-weight:500; min-width:160px; color:#475569; }
    .detail-value { color:#1e293b; font-weight:500; }

    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th, td { padding:10px; text-align:left; font-size:14px; border-bottom:1px solid #e2e8f0; }
    th { background:#2563eb; color:white; font-weight:600; }

    .footer { background:#1e293b; color:white; padding:25px; text-align:center; font-size:14px; }
    .social-links { margin:15px 0; }
    .social-link { display:inline-block; margin:0 10px; color:#e2e8f0; text-decoration:none; }
    .copyright { opacity:0.7; margin-top:15px; }

    @media(max-width:650px){
        .content { padding:25px; }
        .detail-item { flex-direction:column; margin-bottom:15px; }
        .detail-label { margin-bottom:5px; }
        table, thead, tbody, th, td, tr { display:block; }
        th { display:none; }
        td { padding:8px; border:none; border-bottom:1px solid #e5e7eb; }
    }
</style>
</head>
<body>
<div class="email-container">

    <!-- Company Info -->
    <div class="company-header">
        <h1>{{ $companyDetails['name'] ?? 'Company Name' }}</h1>
        <p>{{ $companyDetails['address'] ?? 'Company Address' }}</p>
        <p>Phone: {{ $companyDetails['phone'] ?? 'N/A' }} | Email: {{ $companyDetails['email'] ?? 'N/A' }}</p>
    </div>

    <div class="content">
        <h1 class="welcome-text">Request for Quotation</h1>
        <p class="intro">
            A new Request for Quotation <strong>#{{ str_pad($rfq->id, 5, '0', STR_PAD_LEFT) }}</strong> has been created.  
            Below are the details of the request:
        </p>

        <!-- Supplier Details -->
        <div class="rfq-details">
            <h2 class="details-title">Supplier Details</h2>
            @if($rfq->supplier)
                <div class="detail-item">
                    <span class="detail-label">Supplier Name:</span>
                    <span class="detail-value">{{ $rfq->supplier->name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Supplier Email:</span>
                    <span class="detail-value">{{ $rfq->supplier->email ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Supplier Phone:</span>
                    <span class="detail-value">{{ $rfq->supplier->phone ?? 'N/A' }}</span>
                </div>
            @else
                <p style="color:#64748b;">No supplier details available.</p>
            @endif
        </div>

        <!-- RFQ Info -->
        <div class="rfq-details">
            <h2 class="details-title">RFQ Information</h2>
            <div class="detail-item"><span class="detail-label">Requested By:</span><span class="detail-value">{{ $rfq->user?->name ?? 'N/A' }}</span></div>
            <div class="detail-item"><span class="detail-label">Wanted Delivery Date:</span><span class="detail-value">{{ $rfq->wanted_delivery_date ?? '-' }}</span></div>
            @if($rfq->special_note)
                <div class="detail-item"><span class="detail-label">Special Note:</span><span class="detail-value">{{ $rfq->special_note }}</span></div>
            @endif
        </div>

        <!-- Items Table -->
        <div class="items-table">
            <h2 class="details-title">Requested Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rfq->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                            <td>{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                            <td>{{ $item->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

    <div class="footer">
        <div class="social-links">
            <a href="#" class="social-link">LinkedIn</a>
            <a href="#" class="social-link">Twitter</a>
            <a href="#" class="social-link">Facebook</a>
        </div>
        <p class="copyright">© {{ date('Y') }} {{ $companyDetails['name'] ?? 'Company Name' }}. All rights reserved.</p>
        <p class="copyright">This email was sent regarding RFQ #{{ str_pad($rfq->id, 5, '0', STR_PAD_LEFT) }}.</p>
    </div>

</div>
</body>
</html>

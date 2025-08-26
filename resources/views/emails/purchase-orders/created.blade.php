<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Purchase Order Confirmation</title>
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

    .order-details, .items-table { background:#f1f5f9; border-radius:10px; padding:25px; margin-bottom:30px; }
    .details-title { font-size:18px; font-weight:600; margin-bottom:15px; color:#1e293b; }

    .detail-item { display:flex; margin-bottom:12px; }
    .detail-label { font-weight:500; min-width:160px; color:#475569; }
    .detail-value { color:#1e293b; font-weight:500; }

    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th, td { padding:10px; text-align:left; font-size:14px; border-bottom:1px solid #e2e8f0; }
    th { background:#2563eb; color:white; font-weight:600; }

    .cta-section { text-align:center; margin:30px 0; }
    .cta-button { display:inline-block; background:linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color:white; padding:14px 35px; border-radius:50px; text-decoration:none; font-weight:600; font-size:16px; transition:all 0.3s ease; }
    .cta-button:hover { transform:translateY(-2px); box-shadow:0 6px 12px rgba(37,99,235,0.3); }

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
        <h1 class="welcome-text">Purchase Order Confirmation</h1>
        <p class="intro">
            A new purchase order <strong>#{{ $order->id }}</strong> has been created successfully.  
            Below are the full details of this order:
        </p>
        
        <!-- Provider Details -->
        <div class="order-details">
            <h2 class="details-title">Provider Details</h2>
            @if(!empty($providerDetails))
                <div class="detail-item">
                    <span class="detail-label">Provider Type:</span>
                    <span class="detail-value">{{ $providerDetails['type'] }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Provider ID:</span>
                    <span class="detail-value">{{ $providerDetails['id'] }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Provider Name:</span>
                    <span class="detail-value">{{ $providerDetails['name'] }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Shop Name:</span>
                    <span class="detail-value">{{ $providerDetails['shop'] }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value">{{ $providerDetails['address'] }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{ $providerDetails['phone'] }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $providerDetails['email'] }}</span>
                </div>
            @else
                <p style="color:#64748b;">No provider details available.</p>
            @endif
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <h2 class="details-title">Purchase Order Information</h2>
            <div class="detail-item"><span class="detail-label">Purchase Order ID:</span><span class="detail-value">#{{ $order->id }}</span></div>
            <div class="detail-item"><span class="detail-label">Wanted Delivery Date:</span><span class="detail-value">{{ $order->wanted_date ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Grand Total:</span><span class="detail-value">{{ number_format($order->grand_total, 2) }}</span></div>
            <div class="detail-item"><span class="detail-label">Paid Amount:</span><span class="detail-value">{{ number_format($order->paid_amount, 2) }}</span></div>
            <div class="detail-item"><span class="detail-label">Remaining Balance:</span><span class="detail-value">{{ number_format($order->remaining_balance, 2) }}</span></div>
            <div class="detail-item"><span class="detail-label">Status:</span><span class="detail-value" style="color:#16a34a;">{{ ucfirst($order->status) }}</span></div>
        </div>

        <!-- Items Table -->
        <div class="items-table">
            <h2 class="details-title">Ordered Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->inventoryItem->item_code ?? 'N/A' }}</td>
                            <td>{{ $item->inventoryItem->name ?? 'N/A' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->price, 2) }}</td>
                            <td>{{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


        <!-- CTA -->
        <div class="cta-section">
            <p>You can view this purchase order using the QR code below:</p>
            <p>
                <a href="{{ $qrCodeUrl }}">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($qrCodeUrl) }}" alt="Purchase Order QR Code">
                </a>
            </p>

            <p>Or click this link directly: <a href="{{ $qrCodeUrl }}">{{ $qrCodeUrl }}</a></p>
        </div>
    </div>

    <div class="footer">
        <div class="social-links">
            <a href="#" class="social-link">LinkedIn</a>
            <a href="#" class="social-link">Twitter</a>
            <a href="#" class="social-link">Facebook</a>
        </div>
        <p class="copyright">© {{ date('Y') }} {{ $companyDetails['name'] ?? 'Company Name' }}. All rights reserved.</p>
        <p class="copyright">This email was sent regarding Purchase Order #{{ $order->id }}.</p>
    </div>
</div>
</body>
</html>

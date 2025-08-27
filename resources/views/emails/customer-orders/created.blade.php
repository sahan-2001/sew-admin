<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Customer Order Confirmation</title>
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
    .detail-label { font-weight:500; min-width:140px; color:#475569; }
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
        <h1 class="welcome-text">Thank you, {{ $customer->name ?? 'Customer' }}!</h1>
        <p class="intro">
            Your customer order <strong>#{{ $order->order_id }}</strong> has been received successfully.  
            Below are the full details of your order:
        </p>
        
        <!-- Customer Info -->
         <div class="order-details">
            <h2 class="details-title">Customer Information</h2>
            <div class="detail-item">
                <span class="detail-label">Customer ID:</span>
                <span class="detail-value">{{ $customer->customer_id ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value">{{ $customer->name ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Shop Name:</span>
                <span class="detail-value">{{ $customer->shop_name ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $customer->email ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Phone:</span>
                <span class="detail-value">
                    {{ $customer->phone_1 ?? '-' }}
                    @if(!empty($customer->phone_2))
                        , {{ $customer->phone_2 }}
                    @endif
                </span>
            </div>
        </div>
        
        <!-- Order Details -->
        <div class="order-details">
            <h2 class="details-title">Order Information</h2>
            <div class="detail-item"><span class="detail-label">Order ID:</span><span class="detail-value">#{{ $order->order_id }}</span></div>
            <div class="detail-item"><span class="detail-label">Wanted Delivery Date:</span><span class="detail-value">{{ $order->wanted_delivery_date }}</span></div>
            <div class="detail-item"><span class="detail-label">Grand Total:</span><span class="detail-value">{{ number_format($order->grand_total, 2) }}</span></div>
            <div class="detail-item"><span class="detail-label">Remaining Balance:</span><span class="detail-value">{{ number_format($order->remaining_balance, 2) }}</span></div>
            <div class="detail-item"><span class="detail-label">Status:</span><span class="detail-value" style="color:#16a34a;">{{ ucfirst($order->status) }}</span></div>
        </div>

        <!-- Passcode Section -->
        <div style="margin-top:30px; padding:25px; border:2px solid #facc15; 
                    background: linear-gradient(to right, #fef9c3, #ffffff); 
                    border-radius:20px; text-align:center; box-shadow:0 8px 20px rgba(0,0,0,0.08);">
            
            <h3 style="font-size:20px; font-weight:700; color:#b45309; margin-bottom:15px; display:flex; justify-content:center; align-items:center; gap:10px;">
                <span style="font-size:24px;">🔑</span> Tracking Passcode
            </h3>

            <p style="font-size:14px; color:#374151; line-height:1.6; margin-bottom:20px;">
                Use this passcode to <strong style="color:#b45309;">track your order status anytime</strong>. 
                Keep it safe and do not share publicly.
            </p>

            <span style="display:inline-block; font-size:14px; font-weight:800; letter-spacing:6px; 
                        color:#78350f; background:#fef3c7; border:2px solid #fcd34d; 
                        padding:15px 30px; border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,0.1); 
                        transition:all 0.3s ease; cursor:default;">
                {{ $order->random_code }}
            </span>
        </div>

        <br><br>

        <!-- Items Table -->
        <div class="items-table">
            <h2 class="details-title">Ordered Items</h2>
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
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td> 
                            <td>{{ $item->item_name }}</td>
                            <td>
                                @if($item->variationItems->count())
                                    @foreach($item->variationItems as $variation)
                                        {{ $variation->variation_name }} ({{ $variation->quantity }}x{{ number_format($variation->price, 2) }})<br>
                                    @endforeach
                                @else
                                    {{ $item->variation_name ?? '-' }}
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->price, 2) }}</td>
                            <td>{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


        <!-- CTA -->
        <div class="cta-section" style="margin-top:20px; text-align:center;">
            <!-- Rectangle Button -->
            <p>
                <a href="{{ $qrCodeUrl }}" 
                style="display:inline-block; padding:14px 60px; background:#007bff; color:#fff; text-decoration:none; 
                        font-weight:bold; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.2); min-width:220px; text-align:center;">
                    View Order
                </a>
            </p>
            
            <!-- QR Code Below -->
            <p style="margin-top:15px;">
                <a href="{{ $qrCodeUrl }}">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($qrCodeUrl) }}" 
                        alt="Customer Order QR Code" 
                        style="border:1px solid #ddd; border-radius:8px; padding:5px; background:#fff;">
                </a>
            </p>
        </div>
    </div>

    <div class="footer">
        <div class="social-links">
            <a href="#" class="social-link">LinkedIn</a>
            <a href="#" class="social-link">Twitter</a>
            <a href="#" class="social-link">Facebook</a>
        </div>
        <p class="copyright">© {{ date('Y') }} {{ $companyDetails['name'] ?? 'Company Name' }}. All rights reserved.</p>
        <p class="copyright">This email was sent to {{ $customer->email ?? 'your email' }} as a registered customer.</p>
    </div>
</div>
</body>
</html>

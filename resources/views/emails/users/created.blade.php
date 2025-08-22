<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome to {{ $companyDetails['name'] ?? 'Our Company' }}</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Poppins', sans-serif; line-height:1.6; color:#333; background-color:#f7f9fc; padding:20px; }
    .email-container { max-width:650px; margin:0 auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.08); }

    .company-header { background:#1e40af; color:white; text-align:center; padding:20px; }
    .company-header h1 { margin-bottom:5px; font-size:22px; font-weight:700; }
    .company-header p { margin:2px 0; font-size:14px; }

    .content { padding:35px; }
    .welcome-text { font-size:24px; font-weight:600; margin-bottom:20px; color:#1e293b; }
    .intro { font-size:16px; margin-bottom:25px; color:#475569; line-height:1.7; }

    .user-details { background:#f1f5f9; border-radius:10px; padding:25px; margin-bottom:30px; }
    .details-title { font-size:18px; font-weight:600; margin-bottom:15px; color:#1e293b; }
    .detail-item { display:flex; margin-bottom:12px; }
    .detail-label { font-weight:500; min-width:140px; color:#475569; }
    .detail-value { color:#1e293b; font-weight:500; }

    .note { background:#fffbeb; border-left:5px solid #facc15; padding:15px; border-radius:8px; margin-bottom:25px; color:#78350f; }

    .cta-section { text-align:center; margin:30px 0; }
    .cta-title { font-size:20px; font-weight:600; margin-bottom:15px; color:#1e293b; }
    .cta-text { font-size:16px; margin-bottom:25px; color:#475569; }
    .cta-button { display:inline-block; background:linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color:white; padding:14px 35px; border-radius:50px; text-decoration:none; font-weight:600; font-size:16px; transition:all 0.3s ease; box-shadow:0 4px 6px rgba(37,99,235,0.2); }
    .cta-button:hover { transform:translateY(-2px); box-shadow:0 6px 12px rgba(37,99,235,0.3); }

    .next-steps { margin:30px 0; }
    .steps-title { font-size:20px; font-weight:600; margin-bottom:20px; color:#1e293b; }
    .step { display:flex; margin-bottom:20px; align-items:flex-start; }
    .step-number { background:#2563eb; color:white; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; margin-right:15px; flex-shrink:0; }
    .step-text { color:#475569; }

    .contact-section { background:#f8fafc; border-radius:10px; padding:25px; margin:30px 0; text-align:center; }
    .contact-title { font-size:18px; font-weight:600; margin-bottom:15px; color:#1e293b; }
    .contact-info { color:#475569; margin-bottom:5px; }

    .footer { background:#1e293b; color:white; padding:25px; text-align:center; font-size:14px; }
    .social-links { margin:15px 0; }
    .social-link { display:inline-block; margin:0 10px; color:#e2e8f0; text-decoration:none; }
    .copyright { opacity:0.7; margin-top:15px; }

    @media(max-width:650px){
        .content { padding:25px; }
        .detail-item { flex-direction:column; margin-bottom:15px; }
        .detail-label { margin-bottom:5px; }
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
        <h1 class="welcome-text">Welcome, {{ $user->name }}!</h1>
        <p class="intro">
            You now have an account in {{ $companyDetails['name'] ?? 'Our Company' }}. 
            Use your credentials to access the portal and manage your activities.
        </p>

        <div class="note">
            <strong>Note:</strong> Your initial password is the default password. Please change it after your first login.
        </div>
        
        <div class="user-details">
            <h2 class="details-title">Your Account Details</h2>
            <div class="detail-item">
                <span class="detail-label">User ID:</span>
                <span class="detail-value">#{{ $user->id }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Name:</span>
                <span class="detail-value">{{ $user->name }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $user->email }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Roles:</span>
                <span class="detail-value">{{ implode(', ', $user->getRoleNames()->toArray()) }}</span>
            </div>
            @if($user->phone_1)
            <div class="detail-item">
                <span class="detail-label">Primary Phone:</span>
                <span class="detail-value">{{ $user->phone_1 }}</span>
            </div>
            @endif
            @if($user->phone_2)
            <div class="detail-item">
                <span class="detail-label">Secondary Phone:</span>
                <span class="detail-value">{{ $user->phone_2 }}</span>
            </div>
            @endif
        </div>

        <div class="cta-section">
            <h2 class="cta-title">Get Started Now</h2>
            <p class="cta-text">Log in to your user portal to access your account.</p>
            <a href="#" class="cta-button">Access User Portal</a>
        </div>

        <div class="next-steps">
            <h2 class="steps-title">Next Steps</h2>
            <div class="step">
                <div class="step-number">1</div>
                <p class="step-text">Check your email and credentials for portal access.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <p class="step-text">Update your profile and personal information.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <p class="step-text">Start using the portal to manage your tasks efficiently.</p>
            </div>
        </div>

        <div class="contact-section">
            <h2 class="contact-title">Need Assistance?</h2>
            <p class="contact-info">Our Support Team is here to help</p>
            <p class="contact-info">Email: {{ $companyDetails['email'] ?? 'N/A' }}</p>
            <p class="contact-info">Phone: {{ $companyDetails['phone'] ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="footer">
        <div class="social-links">
            <a href="#" class="social-link">LinkedIn</a>
            <a href="#" class="social-link">Twitter</a>
            <a href="#" class="social-link">Facebook</a>
        </div>
        <p class="copyright">© {{ date('Y') }} {{ $companyDetails['name'] ?? 'Our Company' }}. All rights reserved.</p>
        <p class="copyright">This email was sent to {{ $user->email }} as a registered user.</p>
    </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employment Offer - {{ $employeeDetails['full_name'] }}</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            color: #333;
            font-size: 14px;
        }
        .letter-head {
            border-bottom: 2px solid #2c5282;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .letter-head h1 {
            color: #2c5282;
            margin: 0 0 5px 0;
            font-size: 22px;
        }
        .company-info {
            font-size: 12px;
            color: #718096;
        }
        .date {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
        }
        .section {
            margin: 20px 0;
        }
        .employee-info {
            background: #f7fafc;
            padding: 15px;
            border-left: 4px solid #4299e1;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .label {
            font-weight: 600;
            min-width: 120px;
            color: #2d3748;
        }
        .salary-highlight {
            background: #ebf8ff;
            padding: 12px;
            border: 1px solid #bee3f8;
            border-radius: 4px;
            margin: 15px 0;
        }
        .closing {
            margin-top: 30px;
        }
        .signature-area {
            margin-top: 50px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        .footer {
            font-size: 12px;
            color: #718096;
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="letter-head">
        <h1>OFFER OF EMPLOYMENT</h1>
        <div class="company-info">
            <strong>{{ $companyDetails['name'] }}</strong><br>
            {{ $companyDetails['address'] }}<br>
            Phone: {{ $companyDetails['phone'] }}, Email: {{ $companyDetails['email'] }}
        </div>
    </div>

    <div class="date">{{ now()->format('d M, Y') }}</div>

    <div class="section">
        <p><strong>{{ $employeeDetails['full_name'] }}</strong><br>
        Via Email</p>
    </div>

    <div class="section">
        <p>Dear <strong>{{ $employeeDetails['full_name'] }}</strong>,</p>
        
        <p>We are pleased to offer you the position of <strong>{{ $employeeDetails['designation'] ?? 'To be determined' }}</strong> in the <strong>{{ $employeeDetails['department'] ?? 'To be assigned' }}</strong> department at our company. This letter outlines the key terms of your employment.</p>
    </div>

    <div class="employee-info">
        <div class="info-row">
            <span class="label">Employee Code:</span>
            <span>{{ $employeeDetails['code'] }}</span>
        </div>
        <div class="info-row">
            <span class="label">Start Date:</span>
            <span>{{ $employeeDetails['joined_date'] ? $employeeDetails['joined_date']->format('d M, Y') : 'To be confirmed' }}</span>
        </div>
    </div>

    <div class="salary-highlight">
        <div class="info-row">
            <span class="label" style="min-width: 140px;">Basic Salary:</span>
            <span style="font-weight: 600; color: #2d3748;">
                {{ isset($employeeDetails['basic_salary']) ? 'Rs. ' . number_format($employeeDetails['basic_salary'], 2) : 'To be discussed' }}
            </span>
        </div>
        @if(isset($employeeDetails['basic_salary']))
        <div style="font-size: 12px; color: #718096; margin-top: 5px;">
            Paid monthly, subject to applicable deductions
        </div>
        @endif
    </div>

    <div class="section">
        <p>We believe your skills and experience will be a valuable addition to our team and look forward to your contributions to our company's success. To accept this offer, please sign and return this letter by {{ now()->addDays(3)->format('d M, Y') }}.</p>
    </div>

    <div class="closing">
        <p>Welcome to our team. We're excited about the possibility of working together.</p>
        
        <p>Sincerely,</p>
        
        <div class="signature-area">
            <div class="signature-line"></div>
            <strong>HR Manager</strong><br>
            Human Resources Department
        </div>
    </div>

    <div class="footer">
        This offer is contingent upon satisfactory completion of all pre-employment requirements.
    </div>
</body>
</html>

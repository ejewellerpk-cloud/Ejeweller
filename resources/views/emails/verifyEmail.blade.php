<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #ff5c00;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px 30px;
            line-height: 1.6;
        }
        .content h2 {
            color: #0f172a;
            margin-top: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .content p {
            font-size: 16px;
            color: #475569;
            margin-bottom: 24px;
        }
        .pin-box {
            background-color: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin: 30px 0;
        }
        .pin-code {
            font-size: 36px;
            font-weight: 800;
            color: #ff5c00;
            letter-spacing: 6px;
            margin: 0;
        }
        .alert-text {
            font-size: 13px;
            color: #ef4444;
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 10px 0;
        }
        .footer a {
            color: #ff5c00;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>{{ config('app.name') }}</h1>
            </div>
            <div class="content">
                <h2>Verify Your Email Address</h2>
                <p>Hello,</p>
                <p>Thank you for signing up. Please use the following One-Time Password (OTP) to verify your email address and complete your registration:</p>
                
                <div class="pin-box">
                    <div class="pin-code">{{ $pin }}</div>
                </div>
                
                <div class="alert-text">
                    <strong>Important:</strong> Please do not share this One-Time Password with anyone. Our support team will never ask for this code.
                </div>
                
                <p>If you did not make this request, please disregard this email.</p>
                
                <p>Thanks,<br><strong>{{ config('app.name') }} Team</strong></p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p>If you have any questions, please contact our <a href="#">Support Team</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>

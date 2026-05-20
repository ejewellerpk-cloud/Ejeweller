<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Received</title>
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
            margin-bottom: 20px;
        }
        .order-highlight {
            background-color: #f1f5f9;
            border-left: 4px solid #ff5c00;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .order-id {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .order-message {
            font-size: 15px;
            color: #475569;
            margin: 0;
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
                <h1>{{ config('app.name') }} Admin</h1>
            </div>
            <div class="content">
                <h2>New Order Received</h2>
                <p>Hello Admin,</p>
                <p>A new order has been placed on the store. Below are the details:</p>
                
                <div class="order-highlight">
                    <div class="order-id">Order ID: #{{ $orderId }}</div>
                    <div class="order-message">{{ $message }}</div>
                </div>
                
                <p>Please log in to your admin panel to manage, fulfill, and review this order.</p>
                
                <p>Thanks,<br><strong>{{ config('app.name') }} Automated System</strong></p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

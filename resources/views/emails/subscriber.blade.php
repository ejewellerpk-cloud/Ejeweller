<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
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
            border-bottom: 2px solid #ff5c00;
            padding-bottom: 10px;
            margin-bottom: 24px;
        }
        .content p {
            font-size: 16px;
            color: #475569;
            margin-bottom: 20px;
        }
        .message-body {
            font-size: 15px;
            color: #334155;
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 24px;
            margin: 20px 0;
            line-height: 1.7;
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
                <h2>{{ $title }}</h2>
                <p>Hello,</p>
                <div class="message-body">
                    {!! nl2br(e($message)) !!}
                </div>
                <p>Thank you for subscribing to our updates. We look forward to sharing more exciting news with you soon.</p>
                
                <p>Thanks,<br><strong>{{ config('app.name') }} Team</strong></p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p>You received this because you are subscribed to our newsletter. <a href="#">Unsubscribe</a></p>
            </div>
        </div>
    </div>
</body>
</html>

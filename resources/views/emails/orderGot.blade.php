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
        .order-details-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
        }
        .order-details-box h3 {
            margin-top: 0;
            font-size: 16px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #475569;
        }
        .detail-label {
            font-weight: 600;
            color: #334155;
        }
        .product-list {
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
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
                    <div class="order-id">Order ID: #{{ $order->order_serial_no }}</div>
                    <div class="order-message">{{ $message }}</div>
                </div>

                <div class="order-details-box">
                    <h3>Order Summary</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Customer Name:</span>
                        <span>{{ $order->user?->name ?? 'Guest' }}</span>
                    </div>
                    
                    @if($order->user?->email)
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span>{{ $order->user->email }}</span>
                    </div>
                    @endif
                    
                    @if($order->user?->phone)
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span>{{ $order->user->phone }}</span>
                    </div>
                    @endif
                    
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span>{{ App\Libraries\AppLibrary::currencyAmountFormat($order->total) }}</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span>{{ $order->paymentMethod?->name ?? 'N/A' }}</span>
                    </div>

                    @if($order->orderProducts && $order->orderProducts->count() > 0)
                        <div class="product-list">
                            <span class="detail-label" style="display:block; margin-bottom: 10px;">Items Ordered:</span>
                            @foreach($order->orderProducts as $stock)
                                <div class="product-item">
                                    <span>{{ $stock->product?->name ?? 'Product' }} (x{{ abs($stock->quantity) }})</span>
                                    <span>{{ App\Libraries\AppLibrary::currencyAmountFormat($stock->total) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
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

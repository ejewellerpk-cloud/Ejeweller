@extends('emails.layouts.base')

@section('email_title', 'New Order Received | ' . config('app.name'))
@section('preheader', 'A new order #' . $order->order_serial_no . ' has been placed on your store.')

@section('header_subtitle')
    Store Notification
@endsection

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:26px;color:#0f172a;">
        Hello,
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#475569;">
        A new order has been placed on your store. Please review the summary below and process it from the admin panel when ready.
    </p>

    @include('emails.partials.info-box', [
        'title' => 'Order #' . $order->order_serial_no,
        'body' => e($message),
    ])

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:20px 0;border:1px solid #e2e8f0;border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
                <p style="margin:0;font-size:14px;line-height:20px;font-weight:700;color:#0f172a;">Order Summary</p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#64748b;width:42%;">Customer</td>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#0f172a;font-weight:600;">{{ $order->user?->name ?? 'Guest' }}</td>
                    </tr>
                    @if($order->user?->email)
                    <tr>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#64748b;">Email</td>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#0f172a;">{{ $order->user->email }}</td>
                    </tr>
                    @endif
                    @if($order->user?->phone)
                    <tr>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#64748b;">Phone</td>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#0f172a;">{{ $order->user->phone }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#64748b;">Total Amount</td>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#0f172a;font-weight:700;">{{ App\Libraries\AppLibrary::currencyAmountFormat($order->total) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#64748b;">Payment Method</td>
                        <td style="padding:6px 0;font-size:14px;line-height:20px;color:#0f172a;">{{ $order->paymentMethod?->name ?? 'N/A' }}</td>
                    </tr>
                </table>

                @if($order->orderProducts && $order->orderProducts->count() > 0)
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:14px;border-top:1px solid #e2e8f0;">
                        <tr>
                            <td style="padding:14px 0 8px;font-size:13px;line-height:18px;font-weight:700;color:#334155;">Items Ordered</td>
                        </tr>
                        @foreach($order->orderProducts as $stock)
                            <tr>
                                <td style="padding:5px 0;font-size:13px;line-height:20px;color:#475569;">
                                    {{ $stock->product?->name ?? 'Product' }} &times; {{ abs($stock->quantity) }}
                                </td>
                                <td align="right" style="padding:5px 0;font-size:13px;line-height:20px;color:#0f172a;white-space:nowrap;">
                                    {{ App\Libraries\AppLibrary::currencyAmountFormat($stock->total) }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 20px;">
        <tr>
            <td style="border-radius:6px;background-color:#0f172a;">
                <a href="{{ rtrim(config('app.url'), '/') }}/admin/online-orders" target="_blank" style="display:inline-block;padding:12px 22px;font-size:14px;line-height:20px;font-weight:600;color:#ffffff;text-decoration:none;">
                    Open Admin Panel
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:15px;line-height:24px;color:#475569;">
        This is an automated store notification.<br>
        <strong style="color:#0f172a;">{{ config('app.name') }}</strong>
    </p>
@endsection

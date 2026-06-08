@extends('emails.layouts.base')

@section('email_title', 'Order Update | ' . config('app.name'))
@section('preheader', 'An update about your order #' . $orderId . ' from ' . config('app.name') . '.')

@section('header_subtitle')
    Order Update
@endsection

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:26px;color:#0f172a;">
        Hello {{ $name }},
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#475569;">
        We wanted to let you know there is a new update regarding your recent purchase. Please find the details below.
    </p>

    @include('emails.partials.info-box', [
        'title' => 'Order #' . $orderId,
        'body' => e($message),
    ])

    <p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#475569;">
        You can review your order history and track progress anytime by signing in to your account on our website.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 20px;">
        <tr>
            <td style="border-radius:6px;background-color:#ff5c00;">
                <a href="{{ rtrim(config('app.url'), '/') }}" target="_blank" style="display:inline-block;padding:12px 22px;font-size:14px;line-height:20px;font-weight:600;color:#ffffff;text-decoration:none;">
                    View Your Account
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:15px;line-height:24px;color:#475569;">
        Thank you for shopping with us.<br>
        <strong style="color:#0f172a;">{{ config('app.name') }} Team</strong>
    </p>
@endsection

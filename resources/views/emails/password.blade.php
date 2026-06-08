@extends('emails.layouts.base')

@section('email_title', 'Password Reset | ' . config('app.name'))
@section('preheader', 'Use this verification code to reset your ' . config('app.name') . ' account password.')

@section('header_subtitle')
    Account Security
@endsection

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:26px;color:#0f172a;">
        Hello,
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#475569;">
        We received a request to reset the password for your account. Enter the verification code below on the password reset page to continue.
    </p>

    @include('emails.partials.code-box', [
        'label' => 'Password Reset Code',
        'code' => $pin,
    ])

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 20px;background-color:#fff7ed;border:1px solid #fed7aa;border-radius:6px;">
        <tr>
            <td style="padding:14px 16px;">
                <p style="margin:0;font-size:13px;line-height:20px;color:#9a3412;">
                    For your security, do not share this code with anyone. Our team will never ask for it by phone or email. If you did not request a password reset, you can safely ignore this message and your account will remain unchanged.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:15px;line-height:24px;color:#475569;">
        Thank you,<br>
        <strong style="color:#0f172a;">{{ config('app.name') }} Team</strong>
    </p>
@endsection

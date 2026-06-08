@extends('emails.layouts.base')

@section('email_title', 'Email Verification | ' . config('app.name'))
@section('preheader', 'Confirm your email address to complete your ' . config('app.name') . ' registration.')

@section('header_subtitle')
    Email Verification
@endsection

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:26px;color:#0f172a;">
        Hello,
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#475569;">
        Thank you for creating an account with us. To complete your registration, please enter the verification code below on the sign-up page.
    </p>

    @include('emails.partials.code-box', [
        'label' => 'Email Verification Code',
        'code' => $pin,
    ])

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 20px;background-color:#fff7ed;border:1px solid #fed7aa;border-radius:6px;">
        <tr>
            <td style="padding:14px 16px;">
                <p style="margin:0;font-size:13px;line-height:20px;color:#9a3412;">
                    Keep this code private. If you did not create an account with {{ config('app.name') }}, please disregard this email.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:15px;line-height:24px;color:#475569;">
        Welcome aboard,<br>
        <strong style="color:#0f172a;">{{ config('app.name') }} Team</strong>
    </p>
@endsection

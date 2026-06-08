@extends('emails.layouts.base')

@section('email_title', $title . ' | ' . config('app.name'))
@section('preheader', $title . ' — a message from ' . config('app.name') . '.')

@section('header_subtitle')
    Newsletter Update
@endsection

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:26px;color:#0f172a;">
        Hello,
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#475569;">
        {{ $title }}
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:16px 0;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
        <tr>
            <td style="padding:22px 20px;">
                <div style="font-size:15px;line-height:26px;color:#334155;">
                    {!! nl2br(e($message)) !!}
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:15px;line-height:24px;color:#475569;">
        Thank you for staying connected with us.<br>
        <strong style="color:#0f172a;">{{ config('app.name') }} Team</strong>
    </p>
@endsection

@section('footer_extra')
    You are receiving this email because you subscribed to updates from {{ config('app.name') }}.
    @php $supportEmail = \Dipokhalder\Settings\Facades\Settings::group('company')->get('company_email'); @endphp
    @if($supportEmail)
        To unsubscribe, reply to this email with the subject "Unsubscribe".
    @endif
@endsection

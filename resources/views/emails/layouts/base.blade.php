@php
    $companyName = \Dipokhalder\Settings\Facades\Settings::group('company')->get('company_name') ?: config('app.name');
    $companyEmail = \Dipokhalder\Settings\Facades\Settings::group('company')->get('company_email');
    $companyPhone = \Dipokhalder\Settings\Facades\Settings::group('company')->get('company_phone');
    $companyWebsite = \Dipokhalder\Settings\Facades\Settings::group('company')->get('company_website') ?: config('app.url');
    $companyAddress = \Dipokhalder\Settings\Facades\Settings::group('company')->get('company_address');
    $siteUrl = rtrim((string) config('app.url'), '/');
    $supportUrl = $companyWebsite ?: $siteUrl;
    $supportEmail = $companyEmail ?: config('mail.from.address');
    $year = date('Y');
    $emailTitle = trim($__env->yieldContent('email_title')) ?: $companyName;
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $emailTitle }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
            border-collapse: collapse !important;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }
        a {
            text-decoration: none;
        }
        .preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
            max-height: 0;
            max-width: 0;
            overflow: hidden;
            mso-hide: all;
            font-size: 1px;
            line-height: 1px;
        }
        @media only screen and (max-width: 620px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .mobile-padding {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
        }
    </style>
</head>
<body width="100%" style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
@hasSection('preheader')
    <div class="preheader">@yield('preheader')</div>
@endif

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f1f5f9;">
    <tr>
        <td align="center" style="padding:32px 16px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <!-- Header -->
                <tr>
                    <td style="background-color:#ff5c00;padding:28px 32px;text-align:center;">
                        <p style="margin:0;font-size:22px;line-height:28px;font-weight:700;color:#ffffff;letter-spacing:0.2px;">
                            {{ $companyName }}
                        </p>
                        @hasSection('header_subtitle')
                            <p style="margin:8px 0 0;font-size:13px;line-height:18px;color:#ffe8d9;">
                                @yield('header_subtitle')
                            </p>
                        @endif
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td class="mobile-padding" style="padding:36px 32px 28px;">
                        @yield('content')
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background-color:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 32px;text-align:center;">
                        <p style="margin:0 0 10px;font-size:13px;line-height:20px;color:#64748b;">
                            Need help? Contact us at
                            @if($supportEmail)
                                <a href="mailto:{{ $supportEmail }}" style="color:#ff5c00;font-weight:600;">{{ $supportEmail }}</a>
                            @else
                                <a href="{{ $supportUrl }}" style="color:#ff5c00;font-weight:600;">our support team</a>
                            @endif
                        </p>
                        @if($companyPhone)
                            <p style="margin:0 0 10px;font-size:12px;line-height:18px;color:#94a3b8;">
                                Phone: {{ $companyPhone }}
                            </p>
                        @endif
                        @hasSection('footer_extra')
                            <p style="margin:0 0 10px;font-size:12px;line-height:18px;color:#94a3b8;">
                                @yield('footer_extra')
                            </p>
                        @endif
                        <p style="margin:0 0 8px;font-size:12px;line-height:18px;color:#94a3b8;">
                            &copy; {{ $year }} {{ $companyName }}. All rights reserved.
                        </p>
                        @if($companyAddress)
                            <p style="margin:0;font-size:11px;line-height:16px;color:#cbd5e1;">
                                {{ $companyAddress }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="max-width:600px;width:100%;">
                <tr>
                    <td style="padding:16px 8px 0;text-align:center;">
                        <p style="margin:0;font-size:11px;line-height:16px;color:#94a3b8;">
                            This is a service email from {{ $companyName }}.
                            @if($supportUrl)
                                Visit <a href="{{ $supportUrl }}" style="color:#64748b;text-decoration:underline;">{{ parse_url($supportUrl, PHP_URL_HOST) ?: $supportUrl }}</a>
                            @endif
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

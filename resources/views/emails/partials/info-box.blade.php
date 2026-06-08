<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:20px 0;background-color:#f8fafc;border-left:4px solid #ff5c00;border-radius:4px;">
    <tr>
        <td style="padding:18px 20px;">
            @if(!empty($title))
                <p style="margin:0 0 8px;font-size:15px;line-height:22px;font-weight:700;color:#0f172a;">
                    {{ $title }}
                </p>
            @endif
            <p style="margin:0;font-size:15px;line-height:24px;color:#475569;">
                {!! $body !!}
            </p>
        </td>
    </tr>
</table>

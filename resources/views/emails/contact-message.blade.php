@php
    $locale = app()->getLocale();
    $direction = $locale === 'ar' ? 'rtl' : 'ltr';
    $alignment = $direction === 'rtl' ? 'right' : 'left';
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.mail.contact_title') }}</title>
</head>
<body style="margin:0; padding:32px 12px; background-color:#f3f5f8; color:#172033; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px; border-collapse:separate; border-spacing:0; background-color:#ffffff; border:1px solid #e4e8ef; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px; background-color:#101c32; border-bottom:4px solid #d71920; text-align:{{ $alignment }};">
                            <span style="color:#ffffff; font-size:20px; font-weight:700; letter-spacing:.2px;">{{ __('app.mail.brand') }}</span>
                            <span style="display:block; margin-top:5px; color:#c8d0dd; font-size:12px;">{{ __('app.mail.eyebrow') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px 10px; text-align:{{ $alignment }};">
                            <h1 style="margin:0; color:#101c32; font-size:23px; line-height:1.4; font-weight:700;">{{ __('app.mail.contact_title') }}</h1>
                            <p style="margin:10px 0 0; color:#596579; font-size:15px; line-height:1.8;">{{ __('app.mail.contact_intro') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 8px; text-align:{{ $alignment }};">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td width="50%" valign="top" style="padding:0 8px 14px 0; text-align:{{ $alignment }};">
                                        <div style="padding:14px 16px; background-color:#f7f8fa; border:1px solid #e8ebf0; border-radius:8px;">
                                            <div style="margin-bottom:6px; color:#687386; font-size:12px;">{{ __('app.mail.sender_name') }}</div>
                                            <div style="color:#172033; font-size:15px; font-weight:600; line-height:1.6; word-break:break-word;">{{ $senderName }}</div>
                                        </div>
                                    </td>
                                    <td width="50%" valign="top" style="padding:0 0 14px 8px; text-align:{{ $alignment }};">
                                        <div style="padding:14px 16px; background-color:#f7f8fa; border:1px solid #e8ebf0; border-radius:8px;">
                                            <div style="margin-bottom:6px; color:#687386; font-size:12px;">{{ __('app.mail.sender_email') }}</div>
                                            <a href="mailto:{{ $senderEmail }}" style="color:#b8171d; font-size:15px; font-weight:600; line-height:1.6; text-decoration:none; word-break:break-word;">{{ $senderEmail }}</a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px; text-align:{{ $alignment }};">
                            <div style="margin-bottom:9px; color:#687386; font-size:13px; font-weight:700;">{{ __('app.mail.message') }}</div>
                            <div style="padding:18px; background-color:#ffffff; border:1px solid #e4e8ef; border-{{ $alignment }}:3px solid #d71920; border-radius:8px; color:#263246; font-size:15px; line-height:1.9; white-space:pre-line; overflow-wrap:anywhere;">{{ $messageContent }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px; background-color:#f7f8fa; border-top:1px solid #e8ebf0; color:#687386; font-size:12px; line-height:1.7; text-align:{{ $alignment }};">
                            {{ __('app.mail.reply_note') }}
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0; color:#8992a1; font-size:11px; line-height:1.6; text-align:center;">{{ __('app.mail.footer') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>

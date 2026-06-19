@php
    $siteUrl = rtrim(config('app.frontend_url') ?: config('app.url'), '/');
    $logoUrl = $siteUrl . '/images/aiweetraadlogo.png';
@endphp
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'AI Weet Raad' }}</title>
</head>
<body style="margin:0;background:#fff7ef;color:#3e363c;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff7ef;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #d2f0f1;border-radius:28px;overflow:hidden;box-shadow:0 12px 32px rgba(0,106,108,0.10);">
                    <tr>
                        <td style="height:6px;background:linear-gradient(90deg,#006A6C,#73cacd,#f9c6c5);"></td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 12px;">
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding-right:12px;">
                                        <img src="{{ $logoUrl }}" width="44" height="44" alt="AI Weet Raad" style="display:block;border-radius:14px;">
                                    </td>
                                    <td>
                                        <div style="font-size:18px;font-weight:800;color:#0d3a3b;line-height:1.2;">AI Weet Raad</div>
                                        <div style="font-size:13px;color:#7d747a;line-height:1.4;">Meerdere AI-antwoorden, praktisch vergeleken.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 32px 32px;">
                            @yield('content')
                        </td>
                    </tr>
                </table>
                <div style="max-width:640px;padding:18px 12px 0;color:#8b8288;font-size:12px;line-height:1.6;text-align:center;">
                    Je ontvangt deze e-mail omdat er een actie is uitgevoerd op AI Weet Raad.<br>
                    <a href="{{ $siteUrl }}" style="color:#006A6C;text-decoration:none;font-weight:700;">{{ $siteUrl }}</a>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

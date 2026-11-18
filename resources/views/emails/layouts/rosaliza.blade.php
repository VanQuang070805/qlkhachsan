<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Rosaliza Hotel')</title>
</head>
<body style="margin:0; padding:28px 14px; background-color:#FAF9F6; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1C1917; -webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#FAF9F6;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px; margin:0 auto; background-color:#FFFFFF; border:1px solid #E6E1D6; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#141413; padding:32px 28px 28px; text-align:center; border-bottom:2px solid #B89C72;">
                            @hasSection('header_logo')
                                @yield('header_logo')
                            @endif
                            <p style="margin:0 0 6px 0; font-size:11px; letter-spacing:2.4px; text-transform:uppercase; color:#D4BC94; font-weight:600;">
                                Hà Nội Boutique Sanctuary
                            </p>
                            <h1 style="margin:0; font-family:'Playfair Display', Georgia, 'Times New Roman', serif; font-size:24px; letter-spacing:3px; text-transform:uppercase; color:#FAF9F6; font-weight:600;">
                                ROSALIZA HOTEL
                            </h1>
                            @hasSection('subtitle')
                                <p style="margin:10px 0 0 0; font-size:12px; letter-spacing:1.4px; text-transform:uppercase; color:#D6D3CD; font-weight:500;">
                                    @yield('subtitle')
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px; font-size:14.5px; line-height:1.68; color:#292524;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#F5F2EB; padding:22px 28px; text-align:center; border-top:1px solid #E6E1D6; font-size:12.5px; line-height:1.65; color:#57534E;">
                            <p style="margin:0 0 4px 0; font-weight:700; color:#1C1917; letter-spacing:0.4px;">
                                Rosaliza Hotel Hà Nội
                            </p>
                            <p style="margin:0 0 4px 0;">
                                5B Trần Quốc Toản, Phường Hàng Bài, Quận Hoàn Kiếm, Hà Nội
                            </p>
                            <p style="margin:0 0 8px 0;">
                                Hotline: <a href="tel:02438289999" style="color:#785D32; text-decoration:none; font-weight:600;">024 3828 9999</a>
                                &nbsp;&middot;&nbsp;
                                Email: <a href="mailto:contact@rosalizahotel.vn" style="color:#785D32; text-decoration:none; font-weight:600;">contact@rosalizahotel.vn</a>
                            </p>
                            <p style="margin:0; font-size:11.5px; color:#78716C;">
                                &copy; {{ date('Y') }} Rosaliza Hotel. Bảo lưu mọi quyền.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

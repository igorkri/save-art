<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Нова заявка на співпрацю</title>
</head>
<body style="margin:0; padding:0; background-color:#1c1c1c; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#1c1c1c; padding:32px 16px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#272727; border-radius:4px; overflow:hidden;">

        {{-- Шапка --}}
        <tr>
          <td style="background-color:#272727; padding:24px 32px 0;">
            <span style="font-family: Arial, Helvetica, sans-serif; font-weight:700; font-size:20px; color:#fecc39; letter-spacing:0.5px;">save-art.in.ua</span>
          </td>
        </tr>

        {{-- Смужка-акцент --}}
        <tr>
          <td style="padding:16px 32px 0;">
            <div style="height:2px; background-color:#fecc39; width:64px;"></div>
          </td>
        </tr>

        {{-- Заголовок --}}
        <tr>
          <td style="padding:24px 32px 8px;">
            <h1 style="margin:0; font-size:22px; line-height:28px; color:#ffffff; font-weight:700;">Нова заявка на співпрацю</h1>
          </td>
        </tr>

        {{-- Тіло --}}
        <tr>
          <td style="padding:8px 32px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#343434; border-radius:4px;">
              <tr>
                <td style="padding:20px 24px;">
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:0 0 14px; color:#999999; font-size:13px; line-height:18px;">Ім'я</td>
                    </tr>
                    <tr>
                      <td style="padding:0 0 14px; color:#ffffff; font-size:16px; line-height:22px; font-weight:700;">{{ $applicant['name'] }}</td>
                    </tr>

                    <tr>
                      <td style="padding:0 0 14px; color:#999999; font-size:13px; line-height:18px;">Електронна пошта</td>
                    </tr>
                    <tr>
                      <td style="padding:0 0 14px;">
                        <a href="mailto:{{ $applicant['email'] }}" style="color:#fecc39; font-size:16px; line-height:22px; text-decoration:none;">{{ $applicant['email'] }}</a>
                      </td>
                    </tr>

                    @if(!empty($applicant['phone']))
                    <tr>
                      <td style="padding:0 0 14px; color:#999999; font-size:13px; line-height:18px;">Телефон</td>
                    </tr>
                    <tr>
                      <td style="padding:0 0 14px;">
                        <a href="tel:{{ $applicant['phone'] }}" style="color:#ffffff; font-size:16px; line-height:22px; text-decoration:none;">{{ $applicant['phone'] }}</a>
                      </td>
                    </tr>
                    @endif

                    @if(!empty($applicant['about']))
                    <tr>
                      <td style="padding:0 0 6px; color:#999999; font-size:13px; line-height:18px;">Про себе</td>
                    </tr>
                    <tr>
                      <td style="padding:0; color:#ffffff; font-size:15px; line-height:22px;">{{ $applicant['about'] }}</td>
                    </tr>
                    @endif
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Підвал --}}
        <tr>
          <td style="padding:0 32px 28px;">
            <p style="margin:0; color:#777777; font-size:12px; line-height:18px;">
              Заявку надіслано з форми на сайті
              <a href="https://save-art.in.ua" style="color:#fecc39; text-decoration:none;">save-art.in.ua</a>
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>

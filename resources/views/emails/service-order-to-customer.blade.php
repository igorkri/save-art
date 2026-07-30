<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ваш запит надіслано</title>
</head>
<body style="margin:0; padding:0; background-color:#1c1c1c; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#1c1c1c; padding:32px 16px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#272727; border-radius:4px; overflow:hidden;">

        {{-- Шапка --}}
        <tr>
          <td style="background-color:#272727; padding:24px 32px 0;">
            <span style="font-family: Arial, Helvetica, sans-serif; font-weight:700; font-size:20px; color:#fecc39; letter-spacing:0.5px;">art-ua.info</span>
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
            <h1 style="margin:0; font-size:22px; line-height:28px; color:#ffffff; font-weight:700;">Дякуємо, ваш запит надіслано!</h1>
            <p style="margin:8px 0 0; font-size:15px; line-height:20px; color:#cccccc;">
              Ваше замовлення на послугу
              <a href="{{ $service['url'] }}" style="color:#fecc39; text-decoration:none; font-weight:700;">«{{ $service['title'] }}»</a>
              передано виконавцю — <strong style="color:#ffffff;">{{ $performer['name'] }}</strong>. Він зв'яжеться з вами найближчим часом.
            </p>
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
                      <td style="padding:0 0 14px; color:#999999; font-size:13px; line-height:18px;">Ваші контактні дані</td>
                    </tr>
                    <tr>
                      <td style="padding:0 0 14px; color:#ffffff; font-size:16px; line-height:22px; font-weight:700;">{{ $customer['name'] }}</td>
                    </tr>
                    <tr>
                      <td style="padding:0 0 14px; color:#ffffff; font-size:15px; line-height:22px;">{{ $customer['email'] }}</td>
                    </tr>
                    @if(!empty($customer['phone']))
                    <tr>
                      <td style="padding:0 0 14px; color:#ffffff; font-size:15px; line-height:22px;">{{ $customer['phone'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($customer['options']))
                    <tr>
                      <td style="padding:0 0 6px; color:#999999; font-size:13px; line-height:18px;">Обрані опції</td>
                    </tr>
                    <tr>
                      <td style="padding:0 0 14px; color:#ffffff; font-size:15px; line-height:22px;">
                        @foreach($customer['options'] as $option)
                          &bull; {{ $option }}<br>
                        @endforeach
                      </td>
                    </tr>
                    @endif

                    <tr>
                      <td style="padding:0 0 6px; color:#999999; font-size:13px; line-height:18px;">Ваше повідомлення</td>
                    </tr>
                    <tr>
                      <td style="padding:0; color:#ffffff; font-size:15px; line-height:22px;">{{ $customer['message'] }}</td>
                    </tr>
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
              Це автоматичний лист з
              <a href="{{ $service['url'] }}" style="color:#fecc39; text-decoration:none;">art-ua.info</a>.
              Якщо ви не залишали цей запит — просто проігноруйте цей лист.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>

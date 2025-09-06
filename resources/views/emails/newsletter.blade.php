@php($year = now()->year)

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Welcome to our Newsletter</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f6f9fc;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f9fc;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #eee;">
          <tr>
            <td style="padding:24px;text-align:center;background:#111;color:#fff;">
              <h2 style="margin:0;font-weight:700;">Thanks for subscribing</h2>
              <div style="opacity:.85;margin-top:6px;">You’re on the list!</div>
            </td>
          </tr>

          <tr>
            <td style="padding:24px;color:#222;line-height:1.6;">
              <p style="margin:0 0 12px;">Hi there,</p>
              <p style="margin:0 0 12px;">
                We’ve added <strong>{{ $email }}</strong> to our newsletter.
                Expect new arrivals, offers, and product tips in your inbox.
              </p>
              <p style="margin:0 0 12px;">
                If you didn’t request this, you can safely ignore this email.
              </p>
              <p style="margin:16px 0 0;">Cheers,<br>The Team</p>
            </td>
          </tr>

          <tr>
            <td style="padding:16px 24px;background:#fafafa;color:#666;font-size:12px;text-align:center;border-top:1px solid #eee;">
              © {{ $year }} Ecomus Store. You can unsubscribe anytime from the footer of our emails.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

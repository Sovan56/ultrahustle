<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>@yield('title','UltraHustle')</title>
  <style>
    body{font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu, Cantarell,Noto Sans,sans-serif;background:#f6f8fb;margin:0;padding:24px;color:#1f2937}
    .card{max-width:720px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);overflow:hidden}
    .header{background:linear-gradient(135deg,#6777ef,#6fc4f1);padding:20px 24px;color:#fff}
    .header h1{margin:0;font-size:20px}
    .content{padding:24px}
    .btn{display:inline-block;padding:10px 16px;border-radius:10px;background:#6777ef;color:#fff;text-decoration:none}
    .muted{color:#6b7280;font-size:14px}
    .table{width:100%;border-collapse:collapse;margin-top:12px}
    .table th,.table td{padding:10px 8px;border-bottom:1px solid #eef2f7;text-align:left;font-size:14px}
    .stage{display:flex;align-items:center;gap:10px;margin:8px 0}
    .badge{display:inline-block;font-size:12px;padding:3px 8px;border-radius:999px;background:#eef2ff;color:#334155}
    .badge.pending{background:#fff7ed;color:#9a3412}
    .badge.in_progress{background:#eff6ff;color:#1d4ed8}
    .badge.done{background:#ecfdf5;color:#166534}
    .footer{padding:16px 24px;background:#fafafa;border-top:1px solid #f0f2f7;color:#6b7280;font-size:12px}
  </style>
</head>
<body>
  <div class="card">
    <div class="header">@yield('header')</div>
    <div class="content">@yield('content')</div>
    <div class="footer">You’re receiving this because you made a purchase at UltraHustle.</div>
  </div>
</body>
</html>

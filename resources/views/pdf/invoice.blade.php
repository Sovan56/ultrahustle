<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  body{font-family:DejaVu Sans, sans-serif;font-size:12px;color:#111}
  .wrap{padding:16px}
  h1{font-size:18px;margin:0 0 8px}
  table{width:100%;border-collapse:collapse}
  th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
</style>
</head>
<body>
<div class="wrap">
  <h1>Invoice #{{ $order->id }}</h1>
  <p><strong>Date:</strong> {{ now()->format('Y-m-d H:i') }}</p>
  <p><strong>Buyer:</strong> {{ $buyer['name'] }} ({{ $buyer['email'] }})</p>
  <p><strong>Seller:</strong> {{ $seller['name'] }} ({{ $seller['email'] }})</p>

  <table>
    <thead><tr><th>Item</th><th>Amount</th></tr></thead>
    <tbody>
      <tr>
        <td>{{ $order->product?->name }}</td>
        <td>{{ ($order->currency?->symbol ?? '₹') . number_format($order->amount,2) }}</td>
      </tr>
    </tbody>
  </table>
</div>
</body>
</html>

<!doctype html>
<html>
  <body style="font-family:Inter,Arial,sans-serif;background:#f7f7fb;margin:0;padding:30px">
    <div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;box-shadow:0 6px 24px rgba(0,0,0,.06);overflow:hidden">
      <div style="background:#22c55e;color:#fff;padding:18px 22px">
        <h2 style="margin:0;font-weight:700">New Order Paid</h2>
      </div>
      <div style="padding:22px">
        <p>Hi {{ $seller->name }},</p>
        <p>You’ve received a paid order for <b>{{ $order->product->name }}</b>.</p>
        <table style="width:100%;border-collapse:collapse;margin:12px 0">
          <tr><td>Order #</td><td><b>PO-{{ $order->id }}</b></td></tr>
          <tr><td>Buyer</td><td>{{ $order->buyer_name }} ({{ $order->buyer_email }})</td></tr>
          <tr><td>Amount</td><td><b>{{ $order->currency->code }} {{ number_format($order->amount,2) }}</b></td></tr>
          <tr><td>Status</td><td><span>Paid</span></td></tr>
        </table>
        <p>Head to your dashboard to manage stages and delivery.</p>
        <p style="margin-top:22px">Thanks,<br>{{ config('app.name') }}</p>
      </div>
    </div>
  </body>
</html>

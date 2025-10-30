<p>Your contract (#{{ $c->id }}) was confirmed by the buyer.</p>
<p>Total: {{ $c->buyer_currency }} {{ number_format($c->total_price_buyer, 2) }}</p>
<p><a href="{{ route('service.contracts.show', $c->id) }}">Open contract</a></p>

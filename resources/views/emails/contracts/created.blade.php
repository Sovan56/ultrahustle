<p>A new contract (#{{ $c->id }}) has been created for you.</p>
<p>Seller: {{ $c->seller->first_name ?? 'Seller' }} — Buyer: {{ $c->buyer->first_name ?? 'Buyer' }}</p>
<p><a href="{{ route('service.contracts.show', $c->id) }}">View contract</a></p>

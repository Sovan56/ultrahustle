@extends('emails.layouts.base')

@section('header')
  <h1>Order started</h1>
@endsection

@section('content')
  <p>Hi {{ $order->buyer_name ?? 'there' }},</p>
  <p>Your order <strong>#{{ $order->id }}</strong> for <strong>{{ $order->product?->name }}</strong> has been started by {{ $seller['name'] }}.</p>

  <h3>Stages</h3>
  <table class="table">
    <thead><tr><th>#</th><th>Title</th><th>Status</th></tr></thead>
    <tbody>
      @foreach ($order->stages()->orderBy('position')->get() as $i => $s)
      <tr><td>{{ $i+1 }}</td><td>{{ $s->title }}</td><td><span class="badge {{ $s->status }}">{{ str_replace('_',' ',$s->status) }}</span></td></tr>
      @endforeach
    </tbody>
  </table>

  <p class="muted">Seller: {{ $seller['name'] }} — {{ $seller['email'] }}</p>
@endsection

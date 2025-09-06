@extends('emails.layouts.base')

@section('header')
  <h1>Purchase receipt</h1>
@endsection

@section('content')
  <p>Hi {{ $buyer['name'] ?? 'there' }},</p>
  <p>Thanks for your purchase of <strong>{{ $order->product?->name }}</strong>. Your invoice is attached.</p>
  <p class="muted">Seller: {{ $seller['name'] }} — {{ $seller['email'] }}</p>
@endsection

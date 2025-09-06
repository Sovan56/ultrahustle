@extends('emails.layouts.base')

@section('header')
  <h1>Order completed</h1>
@endsection

@section('content')
  <p>Hi {{ $order->buyer_name ?? 'there' }},</p>
  <p>Your order <strong>#{{ $order->id }}</strong> for <strong>{{ $order->product?->name }}</strong> is now <strong>completed</strong>.</p>
  <p>Log in to review and, if all looks good, approve the delivery.</p>
  <a class="btn" href="{{ url('/UserAdmin/my-orders') }}">View my orders</a>
  <p class="muted">Seller: {{ $seller['name'] }} — {{ $seller['email'] }}</p>
@endsection

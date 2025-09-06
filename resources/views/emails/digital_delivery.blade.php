@extends('emails.layouts.base')

@section('header')
  <h1>Your files are ready</h1>
@endsection

@section('content')
  <p>Hi {{ $order->buyer_name ?? 'there' }},</p>
  <p>Files for your order <strong>#{{ $order->id }}</strong> / <strong>{{ $order->product?->name }}</strong> are ready:</p>
  <ul>
    @foreach ($fileUrls as $u)
      <li><a href="{{ $u }}">{{ basename($u) }}</a></li>
    @endforeach
  </ul>
  <p class="muted">Seller: {{ $seller['name'] }} — {{ $seller['email'] }}</p>
@endsection

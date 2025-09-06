@extends('emails.layouts.base')

@section('header')
  <h1>Buyer approved</h1>
@endsection

@section('content')
  <p>Hello,</p>
  <p>The buyer {{ $buyer['name'] ?? 'Buyer' }} has approved order <strong>#{{ $order->id }}</strong> ({{ $order->product?->name }}).</p>
  <p>You’ll see funds released soon.</p>
@endsection

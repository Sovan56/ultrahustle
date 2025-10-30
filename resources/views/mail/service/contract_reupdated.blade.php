@component('mail::message')
# Contract Updated

Hello,

The seller has updated the contract **#{{ $order->id }}**.

@component('mail::panel')
**Status:** {{ ucfirst(str_replace('_',' ', $order->status)) }}  
**Subtotal:** {{ $order->currency_symbol }}{{ number_format($order->subtotal,2) }}  
@endcomponent

@component('mail::button', ['url' => route('service.contracts.show', $order->id)])
View Contract
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

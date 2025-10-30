@component('mail::message')
# Milestone Submitted

A new submission for **Milestone #{{ $milestone->id }}** on Contract **#{{ $order->id }}** is ready for your review.

@component('mail::panel')
**Title:** {{ $milestone->title }}  
**Amount:** {{ $order->currency_symbol }}{{ number_format($milestone->price,2) }}  
**Status:** {{ ucfirst($milestone->status) }}
@endcomponent

@component('mail::button', ['url' => route('service.contracts.show', $order->id)])
Review & Release
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

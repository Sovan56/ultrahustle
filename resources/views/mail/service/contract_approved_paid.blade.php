@component('mail::message')
# Contract Approved & Paid

Great news! The buyer has approved and paid for contract **#{{ $order->id }}**.

@component('mail::panel')
**Total Paid (On Hold):** {{ $order->currency_symbol }}{{ number_format($order->total_payable,2) }}  
**Milestones:** {{ $order->milestones()->count() }}
@endcomponent

You can begin work and submit milestones.

@component('mail::button', ['url' => route('service.contracts.show', $order->id)])
Open Contract
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

@component('mail::message')
# Report Approved

Your report **#{{ $report->id }}** for Service Order **#{{ $order->id }}** has been **approved**.

We’ve processed the funds according to the policy.

@component('mail::button', ['url' => route('service.contracts.show', $order->id)])
View Contract
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

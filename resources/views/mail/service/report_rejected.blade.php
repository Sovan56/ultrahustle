@component('mail::message')
# Report Rejected

Your report **#{{ $report->id }}** for Service Order **#{{ $order->id }}** has been **rejected** after review.

@component('mail::button', ['url' => route('service.contracts.show', $order->id)])
View Contract
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

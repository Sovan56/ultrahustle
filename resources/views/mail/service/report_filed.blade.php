@component('mail::message')
# New Report Filed

A dispute has been filed on Service Order **#{{ $order->id }}**.

@component('mail::panel')
**Report ID:** #{{ $report->id }}  
**Role:** {{ ucfirst($report->role) }}  
**Reason:** {{ $report->reason }}
@endcomponent

Please review it in the admin panel.

@component('mail::button', ['url' => route('admin.service.reports.index')])
Open Reports
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

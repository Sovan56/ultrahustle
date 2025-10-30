@php($contract = $r->contract)
<p>Your report for contract #{{ $contract->id }} was rejected.</p>
<p>Resolution: {{ \Illuminate\Support\Str::limit($r->resolution_notes, 160) }}</p>
<p><a href="{{ route('service.contracts.show', $contract->id) }}">Open contract</a></p>

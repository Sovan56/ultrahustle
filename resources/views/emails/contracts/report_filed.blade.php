@php($contract = $r->contract)
<p>A report was filed on contract #{{ $contract->id }} by the {{ $r->raised_by_role }}.</p>
<p>Reason: {{ \Illuminate\Support\Str::limit($r->reason, 160) }}</p>
<p><a href="{{ route('service.contracts.show', $contract->id) }}">Open contract</a></p>

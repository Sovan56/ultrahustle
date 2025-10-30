@php($contract = $r->contract)
<p>Your report for contract #{{ $contract->id }} was approved.</p>
<p>Refund has been processed as per policy.</p>
<p><a href="{{ route('service.contracts.show', $contract->id) }}">Open contract</a></p>

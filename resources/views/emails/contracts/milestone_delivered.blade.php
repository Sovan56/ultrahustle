@php($contract = $m->contract)
<p>A milestone was submitted on contract #{{ $contract->id }}: “{{ $m->title }}”.</p>
<p>Please review and either release hold or request changes.</p>
<p><a href="{{ route('service.contracts.show', $contract->id) }}">View milestone</a></p>

<div class="card h-100">
  <div class="card-header">
    <h6 class="mb-0">Profile</h6>
  </div>
  <div class="card-body">
    @if(!$partner)
      <div class="text-muted">Select a chat to see details.</div>
    @else
      <div class="text-center">
        <img src="{{ \App\Support\Avatar::url($partner) }}" class="rounded-circle mb-2" style="width:96px;height:96px;object-fit:cover" onerror="this.src='https://placehold.co/96x96?text=U'">
        <div class="fw-semibold">{{ $partner->name ?? trim(($partner->first_name ?? '').' '.($partner->last_name ?? '')) }}</div>
      </div>
      <hr>
      <dl class="row">
        <dt class="col-4">Email</dt>
        <dd class="col-8">{{ $partner->email ?? '-' }}</dd>

        <dt class="col-4">Bio</dt>
        <dd class="col-8">{{ $partner->anotherDetail->bio ?? '-' }}</dd>
      </dl>
    @endif
  </div>
</div>

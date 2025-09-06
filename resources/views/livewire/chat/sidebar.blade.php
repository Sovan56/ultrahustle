<div class="card h-100">
  <div class="card-header">
    <h6 class="mb-0">Messages</h6>
  </div>
  <div class="card-body p-0">
    <div class="list-group list-group-flush" style="max-height:70vh;overflow:auto">
      @forelse($items as $it)
        <a href="javascript:void(0)" wire:click="open({{ $it['id'] }})" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
          <img src="{{ $it['partner']['avatar'] }}" class="rounded-circle" style="width:36px;height:36px;object-fit:cover" onerror="this.src='https://placehold.co/36x36?text=U'">
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between">
              <div class="fw-semibold">{{ $it['partner']['name'] ?? 'User' }}</div>
              <small class="text-white">{{ $it['time'] }}</small>
            </div>
            <div class="text-white text-truncate">{{ $it['last'] }}</div>
          </div>
        </a>
      @empty
        <div class="p-3 text-center text-muted">No conversations yet.</div>
      @endforelse
    </div>
  </div>
</div>

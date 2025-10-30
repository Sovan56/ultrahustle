@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  /* ====== Dark Admin Table (no pagination) ====== */
  .uh-admin { --uh-bg:#000; --uh-card:#0b0b0b; --uh-border:#1a1a1a; --uh-text:#e6e6e6; --uh-muted:#a6a6a6; --uh-neon:#CEFF1B; }
  .uh-admin .card { background:#0b0b0b; border:1px solid var(--uh-border)!important; }
  .uh-admin .card-header h4, .uh-admin h1 { color:#fff; }

  .uh-admin .table thead th { border-bottom:1px solid var(--uh-border); color:#cfcfcf; white-space:nowrap; }
  .uh-admin .table td, .uh-admin .table th { border-top:1px solid var(--uh-border); color:#e6e6e6; vertical-align:middle; padding:.65rem .75rem; }
  .uh-admin .table tbody tr:hover { background:#0f0f0f; }

  .badge-chip { display:inline-block; padding:.2rem .5rem; border:1px solid var(--uh-border); background:#101010; color:#e6e6e6; border-radius:999px; font-size:.72rem; font-weight:600; }

  .uh-actions .btn { border-radius:999px; line-height:1.2; padding:.35rem .6rem; font-size:.78rem; }
  .btn-ghost { background:#101010; border:1px solid var(--uh-border); color:#fff; }
  .btn-ghost:hover { box-shadow:0 0 0 2px var(--uh-neon); color:#000; background:var(--uh-neon); border-color:transparent; }
  .uh-actions i { font-size:.85rem; margin-right:.25rem; }

  .uh-muted { color:var(--uh-muted); font-size:.78rem; }
  a.table-link { color:#CEFF1B; text-decoration:none; }
  a.table-link:hover { text-shadow:0 0 8px rgba(206,255,27,.35); }
</style>

<div class="main-content uh-admin">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0" style="color: #cefe1b;">My Threads</h1>

      <form class="form-inline" method="GET" action="{{ route('admin.forum.page') }}">
        @php $q = $q ?? request('q',''); @endphp
        <input type="text" name="q" class="form-control mr-2" value="{{ $q }}" placeholder="Search title...">
        <button class="btn btn-primary">Search</button>
      </form>
    </div>

    <div class="section-body">
      @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      <div class="card">
        <div class="card-header"><h4 class="m-0">Threads</h4></div>
        <div class="card-body table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th style="width:70px">#</th>
                <th>Title</th>
                <th style="width:120px">Type</th>
                <th style="width:180px">Category</th>
                <th style="width:110px">Likes</th>
                <th style="width:130px">Comments</th>
                <th style="width:130px">Shares</th>
                <th style="width:170px">Created</th>
                <th class="text-right" style="width:260px">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($threads as $t)
                <tr>
                  <td>#{{ $t->id }}</td>
                  <td>
                    <a class="table-link" href="{{ route('forum.show', $t->id) }}" target="_blank">{{ $t->title }}</a>
                    @if($t->excerpt)
                      <div class="uh-muted">{{ \Illuminate\Support\Str::limit(strip_tags($t->excerpt), 100) }}</div>
                    @endif
                  </td>
                  <td><span class="badge-chip">{{ strtoupper($t->post_type) }}</span></td>
                  <td>{{ $t->category->name ?? '—' }}</td>
                  <td>{{ (int)$t->likes_count }}</td>
                  <td>{{ (int)$t->comments_count }}</td>
                  <td>{{ (int)$t->shares_count }}</td>
                  <td>{{ optional($t->created_at)->diffForHumans() }}</td>
                  <td class="text-right uh-actions">
                    <a class="btn btn-sm btn-ghost" href="{{ route('forum.show', $t->id) }}" target="_blank" title="View">
                      <i class="fas fa-external-link-alt"></i> View
                    </a>
                    <a class="btn btn-sm btn-ghost" href="{{ route('admin.forum.edit', $t->id) }}" title="Edit">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-sm" onclick="confirmDelete({{ $t->id }})" title="Delete">
                      <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <form id="del-{{ $t->id }}" class="d-none" method="POST" action="{{ route('admin.forum.delete', $t->id) }}">
                      @csrf @method('DELETE')
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center uh-muted">No threads found.</td></tr>
              @endforelse
            </tbody>
          </table>
          {{-- no pagination --}}
        </div>
      </div>
    </div>
  </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(id){
  Swal.fire({
    title: 'Delete this thread?',
    text: 'This will also remove its comments and likes.',
    icon: 'warning',
    background: '#0b0b0b',
    color: '#fff',
    showCancelButton: true,
    confirmButtonColor: '#CEFF1B',
    cancelButtonColor: '#444',
    confirmButtonText: 'Yes, delete',
    customClass: { popup: 'border rounded', confirmButton: 'text-dark' }
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('del-'+id).submit();
    }
  });
}
</script>

@include('UserAdmin.common.footer')

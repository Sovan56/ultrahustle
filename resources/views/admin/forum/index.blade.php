{{-- resources/views/admin/forum/index.blade.php --}}
@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0">Forum Admin</h1>
    </div>

    {{-- flash --}}
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="section-body">

      {{-- ========== Categories ========== --}}
      <div class="card">
        <div class="card-header">
          <h4 class="m-0">Categories</h4>
        </div>
        <div class="card-body">
          {{-- create / edit form --}}
          <form method="POST" action="{{ route('admin.forum.category.save') }}" id="catForm" class="mb-3">
            @csrf
            <input type="hidden" name="id" id="cat_id">
            <div class="form-row align-items-end">
              <div class="form-group col-md-4">
                <label for="cat_name">Name</label>
                <input type="text" class="form-control" id="cat_name" name="name" placeholder="Category name" required>
              </div>
              <div class="form-group col-md-3">
                <label>Slug (auto)</label>
                <input type="text" class="form-control" id="cat_slug_preview" placeholder="Auto after save" readonly>
              </div>
              <div class="form-group col-md-3">
                <button class="btn btn-primary btn-block" type="submit" id="catSubmitBtn">Create</button>
              </div>
              <div class="form-group col-md-2">
                <button type="button" class="btn btn-secondary btn-block" id="catResetBtn">Reset</button>
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Slug</th>
                  <th>Threads</th>
                  <th>Updated</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
              @forelse($categories as $c)
                <tr>
                  <td>{{ $c->id }}</td>
                  <td>{{ $c->name }}</td>
                  <td><code>{{ $c->slug }}</code></td>
                  <td>{{ $c->threads_count }}</td>
                  <td>{{ optional($c->updated_at)->diffForHumans() }}</td>
                  <td class="text-right">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary"
                      data-id="{{ $c->id }}"
                      data-name="{{ e($c->name) }}"
                      data-slug="{{ e($c->slug) }}"
                      onclick="editCat(this)"
                    >
                      Edit
                    </button>

                    @if($c->threads_count == 0)
                      {{-- Use SweetAlert via data-confirm --}}
                      <form method="POST"
                            action="{{ route('admin.forum.category.delete', $c) }}"
                            style="display:inline-block"
                            data-confirm="Delete this category?">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    @else
                      <button type="button" class="btn btn-sm btn-outline-secondary" title="Category in use" disabled>Delete</button>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted">No categories yet.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ========== Reports (with View & single Delete) ========== --}}
      <div class="card">
        <div class="card-header">
          <h4 class="m-0">Reports (All)</h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Thread</th>
                  <th>Reporter</th>
                  <th>Reason</th>
                  <th>Notes</th>
                  <th>Created</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
              @forelse($reports as $r)
                @php
                  $reporter = trim(($r->user->first_name ?? '').' '.($r->user->last_name ?? ''));
                  if($reporter==='') $reporter = $r->user->email ?? 'User';
                  $threadTitle = $r->thread->title ?? '—';
                  $threadId    = $r->thread->id ?? null;
                @endphp
                <tr>
                  <td>{{ $r->id }}</td>
                  <td>
                    {{ $threadTitle }}
                    @if($threadId)
                      <div><small class="text-muted">#{{ $threadId }}</small></div>
                    @endif
                  </td>
                  <td>{{ $reporter }}</td>
                  <td style="max-width:260px">{{ $r->reason ?? '—' }}</td>
                  <td style="max-width:260px">{{ $r->notes ?? '—' }}</td>
                  <td>{{ optional($r->created_at)->diffForHumans() }}</td>
                  <td class="text-right">
                    {{-- View --}}
                    @if($threadId)
                      <a class="btn btn-sm btn-outline-info" href="{{ url('/forum/'.$threadId) }}" target="_blank">View</a>
                    @else
                      <button class="btn btn-sm btn-outline-secondary" disabled>View</button>
                    @endif

                    {{-- Single Delete action via SweetAlert --}}
                    @if($threadId)
                      <form method="POST"
                            action="{{ route('admin.forum.thread.delete', $r->thread) }}"
                            style="display:inline-block"
                            data-confirm="Delete this THREAD and ALL related items (likes, saves, shares, comments, polls, reports)?">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    @else
                      <form method="POST"
                            action="{{ route('admin.forum.report.delete', $r) }}"
                            style="display:inline-block"
                            data-confirm="Thread already removed. Delete this report?">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted">No reports.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<script>
  function editCat(btn){
    const id   = btn.dataset.id || '';
    const name = btn.dataset.name || '';
    const slug = btn.dataset.slug || '';

    const idEl   = document.getElementById('cat_id');
    const nameEl = document.getElementById('cat_name');
    const slugEl = document.getElementById('cat_slug_preview');
    const btnEl  = document.getElementById('catSubmitBtn');

    idEl.value   = id;
    nameEl.value = name;
    slugEl.value = slug;
    btnEl.textContent = id ? 'Update' : 'Create';

    document.getElementById('catForm').scrollIntoView({behavior:'smooth', block:'start'});
    nameEl.focus();
  }

  document.getElementById('catResetBtn')?.addEventListener('click', function(){
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_slug_preview').value = '';
    document.getElementById('catSubmitBtn').textContent = 'Create';
  });

  // ===== SweetAlert confirm (theme-aligned) =====
  (function initSweetAlert(){
    function hookForms(){
      document.querySelectorAll('form[data-confirm]').forEach(form=>{
        if (form._swalHooked) return;
        form._swalHooked = true;
        form.addEventListener('submit', function(e){
          // prevent native submit; show SweetAlert
          e.preventDefault();

          const message = form.getAttribute('data-confirm') || 'Are you sure?';
          // If SweetAlert not present, fallback to native confirm (rare)
          if (typeof Swal === 'undefined') {
            if (confirm(message)) form.submit();
            return;
          }

          Swal.fire({
            title: 'Confirm',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,

            // theme match
            background: '#0b0b0b',
            color: '#e5e5e5',
            customClass: {
              popup: 'swal2-uh', // optional class if Otika styles are present
              confirmButton: 'swal2-confirm-uh',
              cancelButton: 'swal2-cancel-uh'
            },
            buttonsStyling: false,
            // simple inline style to match neon accent
            didOpen: (el)=>{
              const ok = el.querySelector('.swal2-confirm-uh');
              const ca = el.querySelector('.swal2-cancel-uh');
              if(ok){
                ok.className = 'swal2-confirm-uh';
                ok.style.cssText = 'border:0;border-radius:999px;background:#CEFF1B;color:#000;font-weight:700;padding:8px 16px;margin:0 6px;';
              }
              if(ca){
                ca.className = 'swal2-cancel-uh';
                ca.style.cssText = 'border:1px solid #1a1a1a;border-radius:999px;background:#0a0a0a;color:#e5e5e5;font-weight:600;padding:8px 16px;margin:0 6px;';
              }
            }
          }).then((res)=>{
            if(res.isConfirmed){
              // submit for real
              form.submit();
            }
          });
        });
      });
    }

    // If SweetAlert already available, just hook.
    if (typeof Swal !== 'undefined') {
      hookForms();
      return;
    }

    // Load SweetAlert2 from CDN once (Otika often already includes it)
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js';
    s.onload = hookForms;
    document.head.appendChild(s);

    // Optional: minimal CSS for SweetAlert to blend if not already shipped by theme
    const c = document.createElement('link');
    c.rel = 'stylesheet';
    c.href = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css';
    document.head.appendChild(c);
  })();
</script>

@include('admin.common.footer')

{{-- resources/views/admin/KycShow.blade.php --}}
@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .doc-thumb img{max-width:100%;height:auto;border-radius:8px;border:1px solid #e9ecef}
  .kv { margin-bottom:.75rem; }
  .kv > .k { font-weight:600; display:block; }
  .avatar40 { width:40px;height:40px;object-fit:cover;border-radius:8px;margin-right:10px; }
  .badge-capitalize { text-transform: capitalize; }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <a href="{{ route('admin.kyc.page') }}" class="btn btn-light btn-sm mr-3">← Back to list</a>
        <h1 class="m-0">KYC Details</h1>
      </div>
      <div>
        <span class="badge badge-capitalize badge-{{ $kyc->status==='approved'?'success':($kyc->status==='rejected'?'danger':'warning') }}">
          {{ $kyc->status }}
        </span>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header w-100 justify-content-between align-items-center">
          <h4 class="m-0">
            {{ $kyc->legal_name }}
            <small class="ml-2 text-muted">({{ $kyc->id_type }})</small>
          </h4>

          <div class="card-header-action">
            @if($kyc->status !== 'approved')
              <button id="btnApprove" class="btn btn-success btn-sm">Approve</button>
            @endif
            @if($kyc->status !== 'rejected')
              <button id="btnReject" class="btn btn-danger btn-sm">Reject</button>
            @endif
          </div>
        </div>

        <div class="card-body">
          {{-- User --}}
          <div class="mb-3">
            <strong>User</strong>
            @php
              $u = $kyc->user;
              $fullName = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->name ?? 'User');
              $avatar = optional($u->detail)->profile_picture ?? null;
              $avatarUrl = $avatar ? url('/media/'.ltrim($avatar,'/')) : asset('assets/img/users/user-1.png');
            @endphp
            <div class="d-flex align-items-center mt-1">
              <img src="{{ $avatarUrl }}" class="avatar40" alt="User">
              <div>
                <div>{{ $fullName }}</div>
                <div class="text-muted small">{{ $u->email }}</div>
              </div>
            </div>
          </div>

          {{-- Fields --}}
          <div class="row">
            <div class="col-md-4 kv">
              <span class="k">Legal Name</span>
              <div>{{ $kyc->legal_name }}</div>
            </div>
            <div class="col-md-4 kv">
              <span class="k">DOB</span>
              <div>{{ optional($kyc->dob)->format('Y-m-d') }}</div>
            </div>
            <div class="col-md-4 kv">
              <span class="k">ID Type / Number</span>
              <div>{{ $kyc->id_type }} / {{ $kyc->id_number }}</div>
            </div>
          </div>

          <div class="kv">
            <span class="k">Address</span>
            <div style="white-space:pre-line">{{ $kyc->address }}</div>
          </div>

          <hr>

          {{-- Documents --}}
          <div class="row">
            <div class="col-md-4">
              <div class="mb-2 d-flex justify-content-between align-items-center">
                <strong>ID Front</strong>
                @if($kyc->id_front_url)
                  <a class="btn btn-outline-primary btn-sm" href="{{ $kyc->id_front_url }}" target="_blank">Open in new tab</a>
                @endif
              </div>
              <div class="doc-thumb">
                @if($kyc->id_front_url)
                  @php $isPdf = \Illuminate\Support\Str::endsWith(strtolower($kyc->id_front_url), '.pdf'); @endphp
                  @if($isPdf)
                    <div class="text-muted small">PDF file. Use the button above to view.</div>
                  @else
                    <img src="{{ $kyc->id_front_url }}" alt="ID Front">
                  @endif
                @else
                  <div class="text-muted">—</div>
                @endif
              </div>
            </div>

            <div class="col-md-4">
              <div class="mb-2 d-flex justify-content-between align-items-center">
                <strong>ID Back</strong>
                @if($kyc->id_back_url)
                  <a class="btn btn-outline-primary btn-sm" href="{{ $kyc->id_back_url }}" target="_blank">Open in new tab</a>
                @endif
              </div>
              <div class="doc-thumb">
                @if($kyc->id_back_url)
                  @php $isPdf = \Illuminate\Support\Str::endsWith(strtolower($kyc->id_back_url), '.pdf'); @endphp
                  @if($isPdf)
                    <div class="text-muted small">PDF file. Use the button above to view.</div>
                  @else
                    <img src="{{ $kyc->id_back_url }}" alt="ID Back">
                  @endif
                @else
                  <div class="text-muted">—</div>
                @endif
              </div>
            </div>

            <div class="col-md-4">
              <div class="mb-2 d-flex justify-content-between align-items-center">
                <strong>Selfie</strong>
                @if($kyc->selfie_url)
                  <a class="btn btn-outline-primary btn-sm" href="{{ $kyc->selfie_url }}" target="_blank">Open in new tab</a>
                @endif
              </div>
              <div class="doc-thumb">
                @if($kyc->selfie_url)
                  <img src="{{ $kyc->selfie_url }}" alt="Selfie">
                @else
                  <div class="text-muted">—</div>
                @endif
              </div>
            </div>
          </div>

          @if($kyc->review_notes)
          <div class="mt-3">
            <strong>Review Notes</strong>
            <div class="text-muted" style="white-space:pre-line">{{ $kyc->review_notes }}</div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </section>
</div>

@include('admin.common.footer')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const KYC_ID = {{ (int)$kyc->id }};
  const POST = (u,d)=>$.ajax({url:u,method:'POST',data:d,dataType:'json',headers:{'X-CSRF-TOKEN':CSRF}});

  function changeStatus(target){
    const approve = target === 'approved';
    Swal.fire({
      title: approve ? 'Approve KYC' : 'Reject KYC',
      input: 'textarea',
      inputLabel: 'Review Notes (optional)',
      inputPlaceholder: 'Add a reason / note (optional)',
      showCancelButton: true,
      confirmButtonText: approve ? 'Approve' : 'Reject',
      confirmButtonColor: approve ? '#28a745' : '#dc3545',
      preConfirm: (notes)=>{
        return POST(`{{ url('/admin/kyc-requests') }}/${KYC_ID}/status`, {
          status: target,
          review_notes: notes || ''
        }).catch(xhr=>{
          Swal.showValidationMessage(xhr?.responseJSON?.message || 'Failed to update');
          return false;
        });
      }
    }).then(res=>{
      if(res.isConfirmed){
        Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false})
          .then(()=>window.location.reload());
      }
    });
  }

  $('#btnApprove').on('click', ()=>changeStatus('approved'));
  $('#btnReject').on('click',  ()=>changeStatus('rejected'));
})();
</script>

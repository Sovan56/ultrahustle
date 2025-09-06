{{-- resources/views/admin/kycRequest.blade.php --}}
@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<style>
  .dt-center{ text-align:center; }
  .text-trunc { max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .avatar32 { width:32px; height:32px; object-fit:cover; border-radius:6px; margin-right:8px; }
  .main-content .table td, 
  .main-content .table th { vertical-align: middle; }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0">KYC Requests</h1>
    </div>

    <div class="section-body">
      {{-- PENDING --}}
      <div class="card">
        <div class="card-header justify-content-between align-items-center w-100">
          <h4 class="m-0">Pending</h4>
        </div>
        <div class="card-body">
          <table id="tblKycPending" class="table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>User</th>
                <th>Legal Name</th>
                <th>ID Type</th>
                <th>ID Number</th>
                <th class="dt-center">Status</th>
                <th>Submitted</th>
                <th class="dt-center">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      {{-- APPROVED + REJECTED --}}
      <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4 class="m-0">Approved</h4></div>
            <div class="card-body">
              <table id="tblKycApproved" class="table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Legal Name</th>
                    <th>ID Type</th>
                    <th>ID Number</th>
                    <th>Updated</th>
                    <th class="dt-center">View</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4 class="m-0">Rejected</h4></div>
            <div class="card-body">
              <table id="tblKycRejected" class="table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Legal Name</th>
                    <th>ID Type</th>
                    <th>ID Number</th>
                    <th>Updated</th>
                    <th class="dt-center">View</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

@include('admin.common.footer')

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  const CSRF = $('meta[name="csrf-token"]').attr('content');
  const GET  = (u,d={})=>$.getJSON(u,d);
  const POST = (u,d)=>$.ajax({url:u,method:'POST',data:d,dataType:'json',headers:{'X-CSRF-TOKEN':CSRF}});
  const onErr=(xhr,msg='Something went wrong')=>{
    Swal.fire({icon:'error',title:'Error',text:(xhr?.responseJSON?.message)||msg});
  };

  const ROUTES = {
    list:  '{{ route("admin.kyc.list") }}',
    show:  '{{ url("/admin/kyc-requests") }}',       // + '/{id}'
    state: '{{ url("/admin/kyc-requests") }}',       // + '/{id}/status'
  };

  // DataTables instances
  let dtPending, dtApproved, dtRejected;

  function initPending(){
    dtPending = $('#tblKycPending').DataTable({
      processing:true, serverSide:true, searching:true,
      ajax: {
        url: ROUTES.list,
        data: d => { d.status = 'pending'; }
      },
      columns: [
        { data: 'user', orderable:false },
        { data: 'legal_name' },
        { data: 'id_type' },
        { data: 'id_number' },
        { data: 'status', className:'dt-center', orderable:false },
        { data: 'created_at' },
        { data: 'actions', className:'dt-center', orderable:false },
      ],
      order: [[5,'desc']],
      pageLength: 10,
      responsive: true
    });
  }

  function initApproved(){
    dtApproved = $('#tblKycApproved').DataTable({
      processing:true, serverSide:true, searching:true,
      ajax: {
        url: ROUTES.list,
        data: d => { d.status = 'approved'; }
      },
      columns: [
        { data: 'user', orderable:false },
        { data: 'legal_name' },
        { data: 'id_type' },
        { data: 'id_number' },
        { data: 'created_at' },
        { data: null, orderable:false, className:'dt-center',
          render: (data, type, row) => `<a class="btn btn-sm btn-info" target="_blank" href="${row.view_url}">View</a>`
        },
      ],
      order: [[4,'desc']],
      pageLength: 5,
      responsive: true
    });
  }

  function initRejected(){
    dtRejected = $('#tblKycRejected').DataTable({
      processing:true, serverSide:true, searching:true,
      ajax: {
        url: ROUTES.list,
        data: d => { d.status = 'rejected'; }
      },
      columns: [
        { data: 'user', orderable:false },
        { data: 'legal_name' },
        { data: 'id_type' },
        { data: 'id_number' },
        { data: 'created_at' },
        { data: null, orderable:false, className:'dt-center',
          render: (data, type, row) => `<a class="btn btn-sm btn-info" target="_blank" href="${row.view_url}">View</a>`
        },
      ],
      order: [[4,'desc']],
      pageLength: 5,
      responsive: true
    });
  }

  // Approve / Reject prompts via SweetAlert
  function openStatusPrompt(id, status){
    const title = status === 'approved' ? 'Approve KYC' : 'Reject KYC';
    const confirmText = status === 'approved' ? 'Approve' : 'Reject';
    const confirmColor = status === 'approved' ? '#28a745' : '#dc3545';

    Swal.fire({
      title, input:'textarea',
      inputLabel: 'Review Notes (optional)',
      inputPlaceholder: 'Add a reason / note (optional)',
      inputAttributes: { 'aria-label': 'Review notes' },
      showCancelButton: true,
      confirmButtonText: confirmText,
      confirmButtonColor: confirmColor,
      preConfirm: (notes)=>{
        return POST(`${ROUTES.state}/${id}/status`, { status, review_notes: notes || '' })
          .catch(xhr=>{
            Swal.showValidationMessage(xhr?.responseJSON?.message || 'Failed to update');
            return false;
          });
      }
    }).then((result)=>{
      if (result.isConfirmed) {
        Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false});
        // reload all three tables so lists stay in sync
        dtPending?.ajax?.reload(null,false);
        dtApproved?.ajax?.reload(null,false);
        dtRejected?.ajax?.reload(null,false);
      }
    });
  }

  // Delegated handlers for Approve/Reject buttons in Pending table
  $(document).on('click', '.js-approve', function(){
    const id = $(this).data('id');
    openStatusPrompt(id, 'approved');
  });
  $(document).on('click', '.js-reject', function(){
    const id = $(this).data('id');
    openStatusPrompt(id, 'rejected');
  });

  // Initialize all tables
  (function init(){
    initPending();
    initApproved();
    initRejected();
  })();

})();
</script>

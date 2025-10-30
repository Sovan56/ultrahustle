@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<style>.dt-center{text-align:center}</style>

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0">Service Contract Reports</h1>
    </div>

    <div class="section-body">
      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

      <div class="card">
        <div class="card-header"><h4 class="m-0">Open/Processed Reports</h4></div>
        <div class="card-body table-responsive">
          <table id="tbl" class="table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>ID</th><th>Order</th><th>Role</th><th>Reporter</th><th>Status</th><th>Reason</th><th>Created</th><th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($reports as $r)
              <tr>
                <td>#{{ $r->id }}</td>
                <td>#{{ $r->service_order_id }}</td>
                <td>{{ ucfirst($r->role) }}</td>
                <td>{{ $r->reporter?->email }}</td>
                <td><span class="badge badge-{{ $r->status === 'open' ? 'warning':'secondary' }}">{{ $r->status }}</span></td>
                <td class="desc-trunc">{{ \Illuminate\Support\Str::limit($r->reason, 120) }}</td>
                <td>{{ optional($r->created_at)->diffForHumans() }}</td>
                <td class="text-right">
                  @if($r->status === 'open')
                  <form action="{{ route('admin.service.reports.approve',$r->id) }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-sm btn-success">Approve</button>
                  </form>
                  <form action="{{ route('admin.service.reports.reject',$r->id) }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-sm btn-outline-danger">Reject</button>
                  </form>
                  @else
                  <span class="text-muted">Processed</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          <div class="mt-3">{{ $reports->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>

@include('admin.common.footer')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>$(function(){ $('#tbl').DataTable({pageLength:25,responsive:true}); });</script>

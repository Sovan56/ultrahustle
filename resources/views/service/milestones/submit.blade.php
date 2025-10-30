@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Submit Milestone</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('service.orders.index') }}">Service Orders</a></div>
        <div class="breadcrumb-item active">Milestone #{{ $milestone->id }}</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header"><h4 class="m-0">{{ $milestone->title }}</h4></div>
        <div class="card-body">
          <form method="POST" action="{{ route('service.milestones.submit', $milestone->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
              <label>Note (optional)</label>
              <textarea name="note" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group col-md-8">
                <label>File (required)</label>
                <input type="file" name="file" class="form-control" required>
              </div>
              <div class="form-group col-md-4">
                <label>URL (optional)</label>
                <input type="url" name="url" class="form-control" placeholder="https://...">
              </div>
            </div>
            <button class="btn btn-primary">Submit</button>
            <a href="{{ route('service.contracts.show', $milestone->order_id ?? $milestone->service_order_id) }}" class="btn btn-outline-secondary">Back</a>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

@include('UserAdmin.common.footer')

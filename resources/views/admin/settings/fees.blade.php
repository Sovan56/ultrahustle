@include('admin.common.header')
@section('title','Fee Settings')

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Platform Fees & GST</h1>
    </div>

    <div class="section-body">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="card">
        <div class="card-body">
          <form method="post" action="{{ route('admin.settings.fees.save') }}">
            @csrf
            <div class="form-group row">
              <label class="col-md-3 col-form-label">Buyer Platform fee (%)</label>
              <div class="col-md-4">
                <input type="number" min="0" max="50" step="0.1" class="form-control"
                  name="platform_fee_percent" value="{{ old('platform_fee_percent',$platform_fee_percent) }}">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-md-3 col-form-label">Seller Platform fee (%)</label>
              <div class="col-md-4">
                <input type="number" min="0" max="50" step="0.1" class="form-control"
                  name="seller_platform_fee_percent" value="{{ old('seller_platform_fee_percent',$seller_platform_fee_percent) }}">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-md-3 col-form-label">GST (%)</label>
              <div class="col-md-4">
                <input type="number" min="0" max="50" step="0.1" class="form-control"
                  name="gst_percent" value="{{ old('gst_percent',$gst_percent) }}">
              </div>
            </div>
            
            <button class="btn btn-primary">Save</button>
          </form>
        </div>
      </div>

    </div>
  </section>
</div>
@include('admin.common.footer')

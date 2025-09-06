@include('UserAdmin.common.header')
@section('title','Order Successful')

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Payment Successful</h1>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="badge bg-success mr-2">PAID</div>
                <h5 class="m-0">{{ $order->product->name ?? 'Product' }}</h5>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <ul class="list-unstyled mb-0">
                    <li><b>Order #</b> {{ $order->id }}</li>
                    <li><b>Tier</b> {{ strtoupper($order->tier) }}</li>
                    <li><b>Status</b> {{ ucfirst($order->status) }}</li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <ul class="list-unstyled mb-0">
                    <li><b>Base</b> {{ $order->currency }} {{ number_format($order->base_amount,2) }}</li>
                    <li><b>Platform fee</b> ({{ $order->platform_fee_percent }}%) {{ $order->currency }} {{ number_format($order->platform_fee_amount,2) }}</li>
                    <li><b>GST</b> ({{ $order->gst_percent }}%) {{ $order->currency }} {{ number_format($order->gst_amount,2) }}</li>
                    <li class="mt-2"><b>Total</b> {{ $order->currency }} {{ number_format($order->total_amount,2) }}</li>
                  </ul>
                </div>
              </div>

              @if(!empty($order->delivery_files))
                <hr>
                <h6>Your files</h6>
                <ul>
                  @foreach($order->delivery_files as $u)
                    <li><a href="{{ $u }}" target="_blank" rel="noopener">Download file</a></li>
                  @endforeach
                </ul>
              @endif

              @if(!empty($order->course_urls))
                <hr>
                <h6>Course Links</h6>
                <ol class="mb-0">
                  @foreach($order->course_urls as $u)
                    <li><a href="{{ $u }}" target="_blank" rel="noopener">{{ $u }}</a></li>
                  @endforeach
                </ol>
              @endif
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <h6>Next steps</h6>
              <a class="btn btn-primary w-100 mb-2" href="{{ route('user.myorders.page') }}">Go to My Orders</a>
              <a class="btn btn-outline-secondary w-100" href="{{ route('marketplace') }}">Continue Shopping</a>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h6>Need help?</h6>
              <p class="mb-0">Having issues with files or links? Reply to your email receipt or contact support.</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>
@include('UserAdmin.common.footer')

@include('UserAdmin.common.header')

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Checkout</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('user.admin.index') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Checkout</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12 col-lg-6">
          <div class="card">
            <div class="card-header"><h4>{{ $product->name }}</h4></div>
            <div class="card-body">
              <p class="text-muted mb-2">{!! $product->description !!}</p>
              <ul class="list-unstyled mb-4">
                <li><b>Order #</b> PO-{{ $order->id }}</li>
                <li><b>Amount</b> {{ $currency }} {{ number_format($amount, 2) }}</li>
              </ul>
              <button id="rzpPay" class="btn btn-primary">Pay with Razorpay</button>
              <button id="btnSim" class="btn btn-outline-secondary ml-2">Simulate Success (Local)</button>
              <div id="payMsg" class="mt-3"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </section>
</div>

@include('UserAdmin.common.footer')

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function(){
  const KEY     = @json($rzp_key);
  const ORDERID = @json($order->id);
  const CURRENCY= @json($currency);
  const AMOUNT  = @json($amount);
  const BUYER   = @json($buyer ? ['name'=>$buyer->name,'email'=>$buyer->email] : ['name'=>'','email'=>'']);

  const $btn = document.getElementById('rzpPay');
  const $msg = document.getElementById('payMsg');

  function post(url, data){
    return fetch(url, {
      method:'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      body: data instanceof FormData ? data : new URLSearchParams(data)
    }).then(r=>r.json());
  }

  // Create Razorpay order on our server
  async function createOrderOnServer(){
    const res = await post(@json(route('checkout.createOrder')), {local_order_id: ORDERID});
    if(!res.id){ throw new Error('Failed to create Razorpay order'); }
    return res;
  }

  $btn.addEventListener('click', async function(){
    $btn.classList.add('btn-progress'); $btn.disabled = true;
    try{
      const rzpOrder = await createOrderOnServer();

      const options = {
        key: KEY,
        amount: rzpOrder.amount, // in paise
        currency: rzpOrder.currency,
        name: @json(config('app.name')),
        description: 'Order #PO-'+ORDERID+' • '+@json($product->name),
        order_id: rzpOrder.id,
        prefill: {
          name: BUYER.name || '',
          email: BUYER.email || ''
        },
        theme: { color: "#6777ef" },
        handler: function (response){
          // Verify on our server
          const fd = new FormData();
          fd.append('razorpay_payment_id', response.razorpay_payment_id);
          fd.append('razorpay_order_id',   response.razorpay_order_id);
          fd.append('razorpay_signature',  response.razorpay_signature);
          fd.append('local_order_id',      ORDERID);

          post(@json(route('checkout.verify')), fd).then(res=>{
            $msg.innerHTML = '<div class="alert alert-success">Payment successful! You can close this page.</div>';
          }).catch(()=>{
            $msg.innerHTML = '<div class="alert alert-danger">Verification failed.</div>';
          });
        }
      };
      const rzp = new Razorpay(options);
      rzp.on('payment.failed', function (resp){
        $msg.innerHTML = '<div class="alert alert-danger">Payment failed: '+(resp.error?.description||'')+'</div>';
      });
      rzp.open();
    } catch(e){
      $msg.innerHTML = '<div class="alert alert-danger">'+(e.message||'Failed to start payment')+'</div>';
    } finally {
      $btn.classList.remove('btn-progress'); $btn.disabled = false;
    }
  });

  // Local simulation (POST)
  document.getElementById('btnSim').addEventListener('click', function(){
    const fd = new FormData();
    fetch(@json(route('checkout.simulate', $order->id)), {
      method:'POST', headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: fd
    }).then(r=>r.json()).then(()=>{
      $msg.innerHTML = '<div class="alert alert-info">Simulated as paid (local only).</div>';
    }).catch(()=> $msg.innerHTML = '<div class="alert alert-danger">Simulate failed.</div>');
  });

})();
</script>

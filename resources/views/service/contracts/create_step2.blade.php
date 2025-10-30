@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Build Milestones</h1>
      <div class="section-header-breadcrumb bg-dark">
        <div class="breadcrumb-item"><a href="{{ route('service.contracts.index') }}">Contracts</a></div>
        <div class="breadcrumb-item">Step 2</div>
        <div class="breadcrumb-item active">Order #{{ $order->id }}</div>
      </div>
    </div>

    <div class="section-body">
      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
      @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

      <div class="card">
        <div class="card-header justify-content-between">
          <h4 class="m-0">Milestones</h4>
          <button class="btn btn-outline-primary btn-sm" onclick="addRow()">Add Milestone</button>
        </div>
        <div class="card-body">
          <form id="frm" method="POST" action="{{ route('service.contracts.milestones.store', $order->id) }}">
            @csrf
            <div id="rows"></div>

            <hr>
            <div class="d-flex justify-content-between">
              <div class="h5">Subtotal: <span id="subtotal">$0.00</span></div>
              <div>
                <button class="btn btn-primary">Save & Send to Buyer</button>
                <a href="{{ route('service.contracts.show', $order->id) }}" class="btn btn-outline-secondary">Back</a>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </section>
</div>
@include('UserAdmin.common.footer')

<script>
let i = 0;

// use the order's currency symbol (seller currency) everywhere on this page
const CURRENCY_SYMBOL = @json($order->currency_symbol ?? '$');

function rowTpl(idx,data){
  data = data || {};
  return `
  <div class="border rounded p-3 mb-3" data-idx="${idx}">
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>Title</label>
        <input name="milestones[${idx}][title]" class="form-control" value="${data.title || ''}" required maxlength="160">
      </div>
      <div class="form-group col-md-3">
        <label>Price</label>
        <input name="milestones[${idx}][price]" class="form-control price" type="number" step="0.01" min="0" value="${(data.price ?? '')}" required oninput="calc()">
      </div>
      <div class="form-group col-md-3">
        <label>Start</label>
        <input name="milestones[${idx}][start_date]" type="date" class="form-control" value="${data.start_date || ''}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-9">
        <label>Description</label>
        <textarea name="milestones[${idx}][description]" class="form-control" rows="2">${data.description || ''}</textarea>
      </div>
      <div class="form-group col-md-3">
        <label>End</label>
        <input name="milestones[${idx}][end_date]" type="date" class="form-control" value="${data.end_date || ''}">
      </div>
    </div>
    <div class="text-right">
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="delRow(${idx})">Delete</button>
    </div>
  </div>`;
}

function addRow(prefill){
  const el = document.getElementById('rows');
  el.insertAdjacentHTML('beforeend', rowTpl(i++, prefill));
  calc();
}

function delRow(idx){
  const b = document.querySelector('[data-idx="'+idx+'"]');
  if(b){ b.remove(); calc(); }
}

function calc(){
  let sum = 0;
  document.querySelectorAll('.price').forEach(function(x){
    sum += parseFloat(x.value || 0);
  });
  document.getElementById('subtotal').textContent = CURRENCY_SYMBOL + (sum || 0).toFixed(2);
}

/* ---- Prefill safely from PHP (no nested <script>, no trailing commas) ---- */
@php
  $prefills = $order->milestones->map(function($m){
      return [
        'title'       => (string) ($m->title ?? ''),
        'price'       => (float)  ($m->price ?? 0),
        'start_date'  => $m->start_date ? $m->start_date->format('Y-m-d') : null,
        'end_date'    => $m->end_date ? $m->end_date->format('Y-m-d') : null,
        'description' => (string) ($m->description ?? ''),
      ];
  })->values()->all();
@endphp

const PREFILLS = {!! json_encode($prefills, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!};

if (Array.isArray(PREFILLS) && PREFILLS.length > 0) {
  PREFILLS.forEach(function(p){ addRow(p); });
} else {
  addRow({});
}
</script>




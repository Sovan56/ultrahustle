@include('UserAdmin.common.header')
@section('title','Create Contract')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Create Contract</h1>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('service.contracts.store') }}">
            @csrf

            {{-- hidden buyer (auto-detected) --}}
            <input type="hidden" name="buyer_id" value="{{ (int) $buyerId }}">
            {{-- keep conversation id if present, helpful for back-links --}}
            @if(!empty($conversationId))
              <input type="hidden" name="conversation_id" value="{{ (int) $conversationId }}">
            @endif

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Service Subcategory <span class="text-danger">*</span></label>
                {{-- NOTE: name is subcategory_id to match validation --}}
                <select id="subcategorySelect" name="subcategory_id" class="form-control" required>
                  <option value="">Select subcategory…</option>
                  @foreach($serviceSubcategories as $s)
                    <option value="{{ $s->id }}" {{ (int)$selectedSubcat === (int)$s->id ? 'selected' : '' }}>
                      {{ $s->name }}
                    </option>
                  @endforeach
                </select>
                @error('subcategory_id')
                  <div class="text-danger small">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group col-md-6">
                <label>Choose Product <span class="text-danger">*</span></label>
                <select id="productSelect" name="product_id" class="form-control" required>
                  <option value="">Select product…</option>
                  @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                  @endforeach
                </select>
                @error('product_id')
                  <div class="text-danger small">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="form-group">
              <label>Terms & Conditions <span class="text-danger">*</span></label>
              <textarea name="terms" class="form-control" rows="6" required placeholder="Write your terms…">{{ old('terms') }}</textarea>
              @error('terms')
                <div class="text-danger small">{{ $message }}</div>
              @enderror
            </div>

            <div class="text-right">
              <button class="btn btn-primary">Next</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

@include('UserAdmin.common.footer')

<script>
(function(){
  const subSel = document.getElementById('subcategorySelect');
  const prodSel = document.getElementById('productSelect');

  async function loadProducts(subcategoryId){
    prodSel.innerHTML = '<option value="">Loading…</option>';
    if (!subcategoryId) {
      prodSel.innerHTML = '<option value="">Select product…</option>';
      return;
    }
    try {
      const url = new URL(@json(route('service.products.mine')), window.location.origin);
      url.searchParams.set('subcategory_id', String(subcategoryId));
      const res = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' } });
      const js = await res.json();
      const items = js?.data || [];
      prodSel.innerHTML = '<option value="">Select product…</option>';
      items.forEach(it => {
        const opt = document.createElement('option');
        opt.value = it.id; opt.textContent = it.name;
        prodSel.appendChild(opt);
      });
    } catch (e) {
      prodSel.innerHTML = '<option value="">Select product…</option>';
    }
  }

  subSel?.addEventListener('change', e => loadProducts(e.target.value));

  // preload on first render if selected
  @if($selectedSubcat)
    loadProducts({{ (int) $selectedSubcat }});
  @endif
})();
</script>

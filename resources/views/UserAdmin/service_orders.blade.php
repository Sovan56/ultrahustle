@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  /* remove default radius for all group items so only first/last get rounded */
.btn-group > .btn {
  border-radius: 0;
}

/* first visible item: left corners rounded */
.btn-group > .btn:first-of-type,
.btn-group > a.btn:first-of-type {
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
}

/* last visible item: right corners rounded */
.btn-group > .btn:last-of-type,
.btn-group > a.btn:last-of-type {
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
}

/* if you want the outline to match rounded corners (optional) */
.btn-group > .btn:first-of-type { border-left: 1px solid rgba(0,0,0,.125); }

</style>

@php
  // Read seller platform fee % once for the whole table.
  $ps = \Illuminate\Support\Facades\DB::table('platform_settings')->pluck('value','key');
  $sellerFeeTotalPct = (float) ($ps['seller_platform_fee_percent'] ?? 0);

  // Helper to build the filter URL preserving query parts
  function so_build_url(array $merge = []) {
      $qs = array_merge(request()->query(), $merge);
      return request()->url() . (count($qs) ? ('?' . http_build_query($qs)) : '');
  }

  $activeStatus   = $activeStatus ?? request('status', 'all');
  $selectedSubcat = (int)($selectedSubcatId ?? request('subcategory_id'));
  $searchQ        = $productSearchQuery ?? request('q','');
@endphp

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0" style="color: #cefe1b;">Orders</h1>
    </div>

    <div class="section-body">

      {{-- FILTER BAR --}}
      <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-2" style="gap:.5rem">
          {{-- Status tabs --}}
          <div class="btn-group mr-3" role="group" aria-label="Status">
            @php
              $tabs = [
                'new'         => ['label' => 'New',         'count' => $counts['new'] ?? 0],
                'in_progress' => ['label' => 'In-progress', 'count' => $counts['in_progress'] ?? 0],
                'completed'   => ['label' => 'Completed',   'count' => $counts['completed'] ?? 0],
                'canceled'    => ['label' => 'Canceled',    'count' => $counts['canceled'] ?? 0],
              ];
            @endphp

            <a href="{{ so_build_url(['status'=>'all']) }}"
               class="btn btn-outline-secondary {{ ($activeStatus==='all') ? 'active' : '' }}">
              All
            </a>

            @foreach($tabs as $key => $t)
              <a href="{{ so_build_url(['status'=>$key]) }}"
                 class="btn btn-outline-secondary {{ ($activeStatus===$key) ? 'active' : '' }}">
                 {{ $t['label'] }}
                 <span class="badge badge-light ml-1">{{ $t['count'] }}</span>
              </a>
            @endforeach
          </div>

          {{-- Subcategory (Services only) --}}
          <form method="GET" action="{{ request()->url() }}" class="form-inline ml-auto" style="gap:.5rem">
            {{-- Preserve current status in the form submit --}}
            <input type="hidden" name="status" value="{{ $activeStatus }}">

            <select name="subcategory_id" class="form-control">
              <option value="">All subcategories</option>
              @foreach($serviceSubcategories as $s)
                <option value="{{ $s->id }}" {{ (int)$selectedSubcat === (int)$s->id ? 'selected' : '' }}>
                  {{ $s->name }}
                </option>
              @endforeach
            </select>

            <input type="text" name="q" class="form-control" placeholder="Search product..."
                   value="{{ $searchQ }}" style="min-width:240px">

            <button class="btn btn-primary">Filter</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4 class="m-0">Confirmed Contracts</h4></div>
        <div class="card-body table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Buyer</th>
                <th>Product</th>
                <th>Hold</th>
                <th>Released</th>
                {{-- Seller fee and net expectation --}}
                <th>Fee %/ms</th>
                <th>Net (All Released)</th>
                <th>Status</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $o)
              @php
                // Per-milestone fee percent for this order
                $msCount      = max(1, (int)($o->milestones_count ?? 0));
                $perMsPct     = $sellerFeeTotalPct / $msCount;

                // Net the seller would get if ALL milestones are released (fee split equally)
                $sellerSubtotal = (float) ($o->subtotal ?? 0);
                $netIfFull      = $sellerSubtotal - ($sellerSubtotal * ($perMsPct / 100));

                // Formats
                $feeLabel   = rtrim(rtrim(number_format($perMsPct, 2),'0'),'.');
                $netIfLabel = number_format($netIfFull, 2);

                // Buyer name (fallback to email)
                $buyerName = trim(($o->buyer->first_name ?? '').' '.($o->buyer->last_name ?? ''));
                if ($buyerName === '') $buyerName = $o->buyer->email;

                $productName = $o->product->name ?? '—';
              @endphp
              <tr>
                <td>#{{ $o->id }}</td>
                <td>{{ $buyerName }}</td>
                <td>{{ $productName }}</td>
                <td>{{ $o->currency_symbol }}{{ number_format($o->hold_amount,2) }}</td>
                <td>{{ $o->currency_symbol }}{{ number_format($o->released_amount,2) }}</td>

                <td>{{ $feeLabel }}%</td>
                <td>{{ $o->currency_symbol }}{{ $netIfLabel }}</td>

                <td><span class="badge badge-secondary">{{ str_replace('_',' ', $o->status) }}</span></td>
                <td>{{ optional($o->updated_at)->diffForHumans() }}</td>
                <td class="text-right">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('service.contracts.show', $o->id) }}">Open</a>
                </td>
              </tr>
              @empty
              <tr><td colspan="10" class="text-center text-muted">No service orders yet.</td></tr>
              @endforelse
            </tbody>
          </table>
          <div>{{ $orders->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>

@include('UserAdmin.common.footer')

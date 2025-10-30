@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
  /** @var \Illuminate\Contracts\Auth\Authenticatable|null $authUser */
  $authUser = auth()->user();
  $fx = app(\App\Services\Currency\CurrencyConverter::class);
@endphp

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0"  style="color: #CEFE1B !important">Contracts</h1>
    </div>

    <div class="section-body">
      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

      <div class="card">
        <div class="card-header"><h4 class="m-0">All Contracts</h4></div>
        <div class="card-body table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Buyer</th>
                <th>Seller</th>
                <th>Subtotal</th>
                <th>Status</th>
                <th>Milestones</th>
                <th>Progress</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $o)
              @php
                $viewerIsSeller = (optional($authUser)->id === $o->seller_id);
                if ($viewerIsSeller) {
                    // Seller sees seller currency
                    $subSym = $o->currency_symbol ?? '$';
                    $subAmt = number_format((float)$o->subtotal, 2);
                } else {
                    // Buyer sees in buyer currency
                    $buyerCode  = strtoupper(optional(optional($o->buyer)->country)->currency ?? 'USD');
                    $buyerSym   = optional(optional($o->buyer)->country)->currency_symbol ?? '$';
                    $sellerCode = strtoupper($o->currency_code ?: 'USD');
                    try {
                      $conv = $fx->convert((float)$o->subtotal, $sellerCode, $buyerCode);
                    } catch (\Throwable $e) { $conv = (float)$o->subtotal; }
                    $subSym = $buyerSym;
                    $subAmt = number_format($conv, 2);
                }

                $totalMs = (int) ($o->milestones_count ?? 0);
                $doneMs  = (int) ($o->milestones_released_count ?? 0);
                $pct     = $totalMs > 0 ? round(($doneMs / $totalMs) * 100) : 0;

                // Choose bar color based on percentage
                if ($pct >= 100) {
                  $barClass = 'bg-success';
                } elseif ($pct >= 67) {
                  $barClass = 'bg-info';
                } elseif ($pct >= 34) {
                  $barClass = 'bg-warning';
                } else {
                  $barClass = 'bg-danger';
                }
              @endphp
              <tr>
                <td>#{{ $o->id }}</td>
                <td>{{ trim(($o->buyer->first_name ?? '').' '.($o->buyer->last_name ?? '')) ?: $o->buyer->email }}</td>
                <td>{{ trim(($o->seller->first_name ?? '').' '.($o->seller->last_name ?? '')) ?: $o->seller->email }}</td>
                <td>{{ $subSym }}{{ $subAmt }}</td>
                <td><span class="badge badge-secondary">{{ str_replace('_',' ', $o->status) }}</span></td>
                <td>{{ $o->milestones_count }}</td>
                <td style="min-width:160px">
                  <div class="progress" style="height:8px;">
                    <div class="progress-bar {{ $barClass }}" role="progressbar"
                         style="width: {{ $pct }}%;"
                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <div class="small text-muted mt-1">{{ $doneMs }}/{{ $totalMs }} ({{ $pct }}%)</div>
                </td>
                <td>{{ optional($o->updated_at)->diffForHumans() }}</td>
                <td class="text-right">
                  <a href="{{ route('service.contracts.show', $o->id) }}" class="btn btn-sm btn-outline-primary">Open</a>
                  @if(auth()->id() === $o->seller_id && in_array($o->status, ['draft','sent','reupdated']))
                    <a href="{{ route('service.contracts.build', $o->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="9" class="text-center text-muted">No contracts yet.</td></tr>
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

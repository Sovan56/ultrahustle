@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0">Report #{{ $report->id }}</h1>
      <div class="section-header-breadcrumb bg-dark">
        <div class="breadcrumb-item"><a href="{{ route('service.reports.index') }}">Reports</a></div>
        <div class="breadcrumb-item active">{{ ucfirst($report->status) }}</div>
      </div>
    </div>

    <div class="section-body">
      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
      @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

      <div class="row">
        <div class="col-md-8">
          {{-- ORDER & PARTIES --}}
          <div class="card">
            <div class="card-header"><h4 class="m-0">Order #{{ $order->id }} Overview</h4></div>
            <div class="card-body">
              <div class="row">
                <div class="col-sm-6">
                  <div><b>Status:</b> {{ $order->status }}</div>
                  <div><b>Seller Currency:</b> {{ $order->currency_code }} ({{ $order->currency_symbol }})</div>
                  <div><b>Subtotal:</b> {{ $order->currency_symbol }}{{ number_format($order->subtotal, 2) }}</div>
                  <div><b>Hold:</b> {{ $order->currency_symbol }}{{ number_format($holdSeller, 2) }}</div>
                  <div><b>Released:</b> {{ $order->currency_symbol }}{{ number_format($order->released_amount, 2) }}</div>
                </div>
                <div class="col-sm-6">
                  <div><b>Buyer:</b> {{ trim(($order->buyer->first_name ?? '').' '.($order->buyer->last_name ?? '')) ?: $order->buyer->email }}</div>
                  <div><b>Seller:</b> {{ trim(($order->seller->first_name ?? '').' '.($order->seller->last_name ?? '')) ?: $order->seller->email }}</div>
                  <div><b>Buyer Currency:</b> {{ $order->buyer->country->currency ?? 'USD' }} ({{ $buyerSymbol }})</div>
                  <div><b>Hold (Buyer CCY):</b> {{ $buyerSymbol }}{{ number_format($holdBuyer,2) }}</div>
                  <div><b>Report By:</b> {{ ucfirst($report->role) }} &middot; {{ $report->reporter?->email }}</div>
                </div>
              </div>
              <hr>
              <div><b>Reason:</b> {{ $report->reason }}</div>
              @if(!empty($report->resolution_note))
                <div class="mt-2"><b>Resolution Note:</b> {{ $report->resolution_note }}</div>
              @endif
            </div>
          </div>

          {{-- MILESTONES --}}
          <div class="card">
            <div class="card-header"><h4 class="m-0">Milestones</h4></div>
            <div class="card-body">
              @forelse($order->milestones as $m)
                @php $sub = $m->submissions()->latest('id')->first(); @endphp
                <div class="border rounded p-3 mb-2">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="h6 m-0">{{ $m->title }}</div>
                      <div class="text-muted small">{{ $m->description ?: '—' }}</div>
                      <div class="small text-muted">
                        {{ $m->start_date ? 'Start: '.$m->start_date->toDateString() : '' }}
                        {{ $m->end_date ? ' • End: '.$m->end_date->toDateString() : '' }}
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="h6 m-0">{{ $order->currency_symbol }}{{ number_format($m->price,2) }}</div>
                      <span class="badge badge-secondary">{{ $m->status }}</span>
                    </div>
                  </div>

                  @if($sub)
                    <div class="mt-2 p-2 bg-dark rounded">
                      <div class="small text-muted mb-1">Latest submission:</div>
                      @if($sub->file_path)
                        <div class="mb-1">
                          <a class="btn btn-sm btn-outline-secondary" href="{{ route('media.pass', ['path' => $sub->file_path]) }}" target="_blank">
                            <i class="fa fa-paperclip mr-1"></i>{{ $sub->file_name ?? 'file' }}
                          </a>
                          @if($sub->file_size)
                            <span class="small text-muted ml-2">({{ number_format($sub->file_size/1024,1) }} KB)</span>
                          @endif
                        </div>
                      @endif
                      @if($sub->url)
                        <div>URL: <a href="{{ $sub->url }}" target="_blank">{{ $sub->url }}</a></div>
                      @endif
                      @if($sub->note)
                        <div class="small mt-1">{{ $sub->note }}</div>
                      @endif
                    </div>
                  @endif
                </div>
              @empty
                <div class="text-muted">No milestones.</div>
              @endforelse
            </div>
          </div>
        </div>

        {{-- RIGHT SIDEBAR: Admin Actions & Settlement Preview --}}
        <div class="col-md-4">
          <div class="card">
            <div class="card-header"><h4 class="m-0">Settlement Preview</h4></div>
            <div class="card-body">
              @if($report->role === 'buyer')
                <div class="mb-2"><b>Scenario:</b> Buyer refund from remaining hold</div>
                <div class="d-flex justify-content-between"><div>Hold (Seller CCY)</div><div>{{ $order->currency_symbol }}{{ number_format($holdSeller,2) }}</div></div>
                <div class="d-flex justify-content-between"><div>Hold (Buyer CCY)</div><div>{{ $buyerSymbol }}{{ number_format($holdBuyer,2) }}</div></div>
                <div class="d-flex justify-content-between"><div>Refund %</div><div>{{ rtrim(rtrim(number_format($buyerRefundPct,2),'0'),'.') }}%</div></div>
                <div class="small text-muted mt-2">On approve: refund {{ (100-$buyerRefundPct) }}% of remaining hold to buyer (in buyer currency), keep {{ $buyerRefundPct }}% as fee. Order → dispute_resolved_refund.</div>
              @else
                <div class="mb-2"><b>Scenario:</b> Seller gets last submitted milestone</div>
                <div class="d-flex justify-content-between"><div>Last Submitted Gross</div><div>{{ $order->currency_symbol }}{{ number_format($lastSubmittedGross,2) }}</div></div>
                <div class="d-flex justify-content-between"><div>Seller Fee / milestone</div><div>{{ rtrim(rtrim(number_format($perMsPct,2),'0'),'.') }}%</div></div>
                <div class="d-flex justify-content-between font-weight-bold"><div>Net to Seller</div><div>{{ $order->currency_symbol }}{{ number_format($lastSubmittedNet,2) }}</div></div>
                <div class="small text-muted mt-2">On approve: consume gross from HOLD, add gross to RELEASED, credit seller NET. Order → dispute_resolved_release.</div>
              @endif
            </div>
          </div>

          @if($report->status === 'open')
          <div class="card">
            <div class="card-header"><h4 class="m-0">Decision</h4></div>
            <div class="card-body">
              <form method="POST" action="{{ route('service.reports.approve', $report->id) }}" class="mb-2">
                @csrf
                <div class="form-group">
                  <label>Resolution note (optional)</label>
                  <textarea name="note" class="form-control" rows="2" placeholder="Write why this is approved..."></textarea>
                </div>
                <button class="btn btn-success btn-block">Approve</button>
              </form>

              <form method="POST" action="{{ route('service.reports.reject', $report->id) }}">
                @csrf
                <div class="form-group">
                  <label>Resolution note (optional)</label>
                  <textarea name="note" class="form-control" rows="2" placeholder="Write why this is rejected..."></textarea>
                </div>
                <button class="btn btn-outline-danger btn-block">Reject</button>
              </form>
            </div>
          </div>
          @else
          <div class="card">
            <div class="card-header"><h4 class="m-0">Decision</h4></div>
            <div class="card-body">
              <div class="alert alert-info mb-0">This report is already <b>{{ $report->status }}</b>.</div>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </section>
</div>

@include('admin.common.footer')

@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1 style="color: #CEFE1B;">Contract #{{ $order->id }}</h1>
      <div class="section-header-breadcrumb bg-dark">
        <div class="breadcrumb-item"><a href="{{ route('service.contracts.index') }}">Contracts</a></div>
        <div class="breadcrumb-item active">{{ ucfirst(str_replace('_',' ', $order->status)) }}</div>
      </div>
    </div>

    <div class="section-body">
      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
      @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <h4 class="m-0">Milestones</h4>
            </div>
            <div class="card-body">
              @forelse($order->milestones as $m)
              <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="h6 mb-2">{{ $m->title }}</div>
                    <div class="small" style="color:white !important;">{{ $m->description ?: '—' }}</div>
                    <div class="small text-muted" style="margin-top: 20px;">
                      {!! $m->start_date ? 'Start: <span style="color:white;">'.$m->start_date->toDateString().'</span>' : '' !!} <br>
{!! $m->end_date ? 'End: <span style="color:white;">'.$m->end_date->toDateString().'</span>' : '' !!}

                    </div>
                  </div>
                  <div class="text-right">
                    @php
                      $buyerView = auth()->id() === $order->buyer_id;
                      $buyerSym  = $buyerConverted['symbol'] ?? '$';
                      $buyerAmt  = $buyerView ? number_format(($buyerConverted['milestones'][$m->id] ?? 0), 2) : null;
                    @endphp
                    @if($buyerView)
                      <div class="h6 m-0">{{ $buyerSym }}{{ $buyerAmt }}</div>
                      <div class="small text-muted">(~ {{ $order->currency_symbol }}{{ number_format($m->price,2) }})</div>
                    @else
                      <div class="h6 m-0">{{ $order->currency_symbol }}{{ number_format($m->price,2) }}</div>
                    @endif
                    <span class="badge badge-secondary">{{ $m->status }}</span>
                  </div>
                </div>

                <div class="mt-2 d-flex gap-2">
                  {{-- Seller can submit when order is paid/in progress, milestone is draft/resubmitted, AND previous milestone (if any) is released/approved --}}
                  @php
                    $sellerView = auth()->id() === $order->seller_id;
                    $orderOk    = in_array($order->status, ['approved_paid','in_progress']);
                    $msOk       = in_array($m->status, ['draft','resubmitted']);
                    $prevOk     = true;
                    if(isset($loop) && !$loop->first) {
                      $prev = $order->milestones[$loop->index - 1] ?? null;
                      $prevOk = $prev ? in_array($prev->status, ['released','approved']) : true;
                    }
                    $canSubmitThis = $sellerView && $orderOk && $msOk && $prevOk;
                  @endphp

                  @if($canSubmitThis)
                    <a href="{{ route('service.milestones.submit.form', $m->id) }}" class="btn btn-sm btn-primary">Submit</a>
                  @elseif($sellerView && $orderOk && $msOk)
                    <button class="btn btn-sm btn-primary" disabled title="Submit unlocks after the previous milestone is released.">Submit</button>
                  @endif

                  {{-- Buyer controls after submission --}}
                  @if(auth()->id() === $order->buyer_id && in_array($m->status,['submitted','approved']))
                    <form method="POST" action="{{ route('service.milestones.release', $m->id) }}" class="d-inline">
                      @csrf
                      <button class="btn btn-sm btn-success">Release</button>
                    </form>
                    <form method="POST" action="{{ route('service.milestones.request_cancel', $m->id) }}" class="d-inline">
                      @csrf
                      <button class="btn btn-sm btn-outline-danger">Request Changes</button>
                    </form>
                  @endif
                </div>

                {{-- If there are submissions, show latest quick preview --}}
                @php $sub = $m->submissions()->latest('id')->first(); @endphp
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

          <div class="card">
            <div class="card-header">
              <h4 class="m-0">Terms & Conditions</h4>
            </div>
            <div class="card-body">{!! nl2br(e($order->terms ?: '—')) !!}</div>
          </div>
        </div>

        <div class="col-md-4">
          {{-- Seller-side summary (order currency) --}}
          <div class="card">
            <div class="card-header">
              <h4 class="m-0">Summary</h4>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>Hold</div>
                <div style="color:white !important;">{{ $order->currency_symbol }}{{ number_format($order->hold_amount,2) }}</div>
              </div>
              <div class="d-flex justify-content-between">
                <div>Released</div>
                <div style="color:white !important;">{{ $order->currency_symbol }}{{ number_format($order->released_amount,2) }}</div>
              </div>

              @isset($sellerPayoutInfo)
                <hr>
                <div class="d-flex justify-content-between">
                  <div>Platform Fee (per milestone)</div>
                  <div style="color:white !important;">{{ rtrim(rtrim(number_format($sellerPayoutInfo['per_milestone_percent'] ?? 0,2),'0'),'.') }}%</div>
                </div>
                <div class="d-flex justify-content-between font-weight-bold mt-2">
                  <div>Net if All Released</div>
                  <div style="color:white !important;">{{ $order->currency_symbol }}{{ number_format($sellerPayoutInfo['net_if_full'] ?? 0,2) }}</div>
                </div>
                <div class="small text-muted mt-2">
                  Net on remaining hold: {{ $order->currency_symbol }}{{ number_format($sellerPayoutInfo['net_on_hold'] ?? 0,2) }}
                </div>
              @endisset
            </div>
          </div>

          {{-- Buyer-side summary (full, if paid/quoted) --}}
          @php $bq = (array)($order->meta['buyer_quote'] ?? []); @endphp
          @if(!empty($bq))
            <div class="card">
              <div class="card-header">
                <h4 class="m-0">Buyer Charge Summary</h4>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>Subtotal</div>
                  <div>{{ $bq['currency_symbol'] ?? '$' }}{{ number_format($bq['subtotal'] ?? 0,2) }}</div>
                </div>
                <div class="d-flex justify-content-between">
                  <div>Platform Fee ({{ rtrim(rtrim(number_format($bq['platform_fee_percent'] ?? 0,2),'0'),'.') }}%)</div>
                  <div>{{ $bq['currency_symbol'] ?? '$' }}{{ number_format($bq['platform_fee_amount'] ?? 0,2) }}</div>
                </div>
                <div class="d-flex justify-content-between">
                  <div>GST ({{ rtrim(rtrim(number_format($bq['gst_percent'] ?? 0,2),'0'),'.') }}%)</div>
                  <div>{{ $bq['currency_symbol'] ?? '$' }}{{ number_format($bq['gst_amount'] ?? 0,2) }}</div>
                </div>
                <hr>
                <div class="d-flex justify-content-between font-weight-bold">
                  <div>Total</div>
                  <div>{{ $bq['currency_symbol'] ?? '$' }}{{ number_format($bq['total'] ?? 0,2) }}</div>
                </div>
                @if(!empty($bq['paid_at']))
                  <div class="small text-muted mt-2">Paid at: {{ \Illuminate\Support\Carbon::parse($bq['paid_at'])->toDayDateTimeString() }}</div>
                @endif
                @if(!empty($buyerPreview))
                  <hr>
                  <div class="d-flex justify-content-between">
                    <div>Hold</div>
                    <div>{{ $buyerPreview['symbol'] ?? '$' }}{{ number_format($buyerPreview['hold'] ?? ($buyerConverted['hold'] ?? 0),2) }}</div>
                  </div>
                  <div class="d-flex justify-content-between">
                    <div>Released</div>
                    <div>{{ $buyerPreview['symbol'] ?? '$' }}{{ number_format($buyerPreview['released'] ?? ($buyerConverted['released'] ?? 0),2) }}</div>
                  </div>
                @endif
              </div>
            </div>
          @endif

          <div class="card">
            <div class="card-header">
              <h4 class="m-0">Actions</h4>
            </div>
            <div class="card-body">
              @if(auth()->id() === $order->seller_id)
                @if(in_array($order->status,['draft','sent','reupdated']))
                  <a href="{{ route('service.contracts.build', $order->id) }}" class="btn btn-outline-secondary btn-block mb-2">Edit & Resend</a>
                  <form method="POST" action="{{ route('service.contracts.resend', $order->id) }}">
                    @csrf <button class="btn btn-primary btn-block">Send/Resend to Buyer</button>
                  </form>
                  {{-- Delete (seller only, before confirmation) --}}
                  <form method="POST" action="{{ route('service.contracts.destroy', $order->id) }}" onsubmit="return confirm('Delete this contract? This cannot be undone.');" class="mt-2">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-block">Delete Contract</button>
                  </form>
                @endif
              @endif

              @if(auth()->id() === $order->buyer_id)
                @if(in_array($order->status,['sent','reupdated']))
                  {{-- Open modal, don't submit directly --}}
                  <button class="btn btn-success btn-block mb-2" data-toggle="modal" data-target="#payModal">Approve & Pay</button>
                  <form method="POST" action="{{ route('service.contracts.buyer.cancel', $order->id) }}">
                    @csrf
                    <button class="btn btn-outline-danger btn-block">Cancel</button>
                  </form>
                @endif
              @endif

              {{-- Report (either party) --}}
              @if(!in_array($order->status,['completed','dispute_open','dispute_resolved_refund','dispute_resolved_release']))
                <form method="POST" action="{{ route('service.contracts.report', $order->id) }}" class="mt-3">
                  @csrf
                  <div class="form-group">
                    <label style="color: #CEFE1B">Report Reason</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                  </div>
                  <button class="btn btn-block">Report</button>
                </form>

                {{-- NEW: Cancel Project buttons (under Report), for ONGOING projects --}}
                @if(in_array($order->status, ['approved_paid','in_progress']))
                  @if(auth()->id() === $order->seller_id)
                    <form method="POST" action="{{ route('service.contracts.cancel.seller', $order->id) }}" class="mt-2" onsubmit="return confirm('Cancel this project now? The buyer will be able to report to admin.');">
                      @csrf
                      <button class="btn btn-outline-danger btn-block">Cancel Project (Seller)</button>
                    </form>
                  @endif

                  @if(auth()->id() === $order->buyer_id)
                    <form method="POST" action="{{ route('service.contracts.cancel.buyer', $order->id) }}" class="mt-2" onsubmit="return confirm('Cancel this project now? The seller will be able to report to admin.');">
                      @csrf
                      <button class="btn btn-outline-danger btn-block">Cancel Project (Buyer)</button>
                    </form>
                  @endif
                @endif
              @else
                <div class="alert alert-info mb-0">Current status: {{ str_replace('_',' ', $order->status) }}</div>
              @endif
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>

{{-- BUYER PAY MODAL (quote in buyer currency) --}}
@if(auth()->id() === $order->buyer_id && in_array($order->status,['sent','reupdated']))
<div class="modal fade" id="payModal" tabindex="-1" role="dialog" aria-labelledby="payModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form id="payForm" method="POST" action="{{ route('service.contracts.approve', $order->id) }}" class="modal-content">
      @csrf
      <input type="hidden" name="confirm" value="1">
      <div class="modal-header">
        <h5 class="modal-title" id="payModalLabel">Confirm Payment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="quoteBox" class="text-center text-muted">Loading…</div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Pay & Approve</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function() {
    // Elements
    const payBtn = document.querySelector('[data-target="#payModal"]'); // Approve & Pay button
    const modal  = document.getElementById('payModal');
    const box    = document.getElementById('quoteBox');

    // Fetch quote JSON and render it into the modal
    async function loadQuote() {
      if (!box) return;
      box.textContent = 'Loading…';
      try {
        const url = @json(route('service.contracts.quote', $order->id));
        const res = await fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        });

        let j = null;
        try { j = await res.json(); } catch (_e) {}

        if (!j || j.ok !== true || !j.data) {
          box.textContent = (j && j.message) ? j.message : 'Could not load payment estimate.';
          return;
        }

        const q = j.data;
        const money = (n) => (Number(n || 0)).toFixed(2);

        box.innerHTML = `
          <div class="d-flex justify-content-between"><div>Subtotal</div><div>${q.symbol}${money(q.subtotal)}</div></div>
          <div class="d-flex justify-content-between"><div>Platform Fee (${q.platform_fee_percent}%)</div><div>${q.symbol}${money(q.platform_fee_amount)}</div></div>
          <div class="d-flex justify-content-between"><div>GST (${q.gst_percent}%)</div><div>${q.symbol}${money(q.gst_amount)}</div></div>
          <hr>
          <div class="d-flex justify-content-between font-weight-bold"><div>Total</div><div>${q.symbol}${money(q.total)}</div></div>
        `;
      } catch (e) {
        console.error('quote error', e);
        box.textContent = 'Failed to load quote.';
      }
    }

    // When user clicks "Approve & Pay", preload the quote into the modal
    if (payBtn) {
      payBtn.addEventListener('click', function () {
        if (box) box.textContent = 'Loading…';
        loadQuote();
      });
    }

    // Also load when the modal becomes visible
    modal?.addEventListener('shown.bs.modal', loadQuote);
  })();
</script>
@endif

@include('UserAdmin.common.footer')

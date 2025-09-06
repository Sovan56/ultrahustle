{{-- resources/views/UserAdmin/wallet.blade.php --}}
@include('UserAdmin.common.header')
@section('title','Wallet')
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ---- NiceScroll no-op shim (load EARLY so theme scripts won't crash) ---- --}}
<script>
  (function(w) {
    if (!w.jQuery) {
      w.__needsNiceScrollShim__ = true;
    } else if (!jQuery.fn.niceScroll) {
      jQuery.fn.niceScroll = function(){ return this; };
    }
  })(window);
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<div class="main-content">
  <section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
      <h1 class="m-0">Wallet</h1>
    </div>
@php
  $avatar = $user->anotherDetail?->profile_picture
  ? url('/media/' . ltrim($user->anotherDetail->profile_picture, '/'))
  : asset('assets/img/users/user-1.png');
@endphp
    <div class="section-body">
      <div class="row">
        {{-- LEFT: User card --}}
        <div class="col-lg-4">
          <div class="card">
            <div class="card-body d-flex">
              <img src="{{ $avatar }}"
                   class="rounded-circle author-box-picture mr-3" style="height:64px; width:64px; object-fit:cover" alt="Profile">
              <div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <div class="text-muted small">{{ $user->email }}</div>
                @if($user->anotherDetail && $user->anotherDetail->location)
                  <div class="mt-1"><i class="fas fa-map-marker-alt"></i> {{ $user->anotherDetail->location }}</div>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- RIGHT: Wallet summary + actions --}}
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
              <div class="d-flex align-items-center mb-3">
                <div class="mr-3" style="font-size:28px;"><i class="fas fa-wallet"></i></div>
                <div>
                  <div class="text-muted small">Current Balance</div>
                  <div class="h4 mb-0">
                    <span class="mr-1" id="wallet-symbol">{{ $symbol }}</span>
                    <span id="wallet-balance">{{ number_format($user->wallet ?? 0, 2) }}</span>
                    <small class="text-muted ml-2" id="currency-code">{{ $currencyCode }}</small>
                  </div>

                  {{-- KYC / 2FA badges --}}
                  <div class="mt-2">
                    @php
                      $kycApproved = \Illuminate\Support\Facades\DB::table('user_kyc_submissions')
                                      ->where('user_id',$user->id)->where('status','approved')->exists();
                      $twofaEnabled = (int)($user->twofa_enabled ?? 0) === 1 && !empty($user->twofa_secret);
                    @endphp
                    @if($kycApproved)
                      <span class="badge badge-success">KYC: Approved</span>
                    @else
                      <span class="badge badge-warning">KYC: Pending</span>
                    @endif

                    @if($twofaEnabled)
                      <span class="badge badge-success">2FA: Enabled</span>
                    @else
                      <span class="badge badge-warning">2FA: Disabled</span>
                    @endif
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <button class="btn btn-primary mr-2" id="btnAddFunds"><i class="fas fa-plus-circle"></i> Add Funds</button>
                <button class="btn btn-outline-primary" id="btnWithdraw"><i class="fas fa-arrow-circle-up"></i> Withdraw</button>
              </div>
            </div>
          </div>

          {{-- ===== NEW: Payout Accounts (for withdrawals) ===== --}}
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="m-0">Payout account</h4>
              <button id="btnAddPayout" class="btn btn-sm btn-outline-primary">Add account</button>
            </div>
            <div class="card-body">
              <div id="payoutList" class="list-group"></div>
            </div>
          </div>
          {{-- ===== /NEW: Payout Accounts ===== --}}

          {{-- Filters --}}
          <div class="card">
            <div class="card-header">
              <h4 class="m-0">Transactions</h4>
              <div class="card-header-form">
                <form class="form-inline">
                  <div class="form-group mx-sm-2">
                    <select id="fType" class="form-control">
                      <option value="">All Types</option>
                      <option value="credit">Credit</option>
                      <option value="debit">Debit</option>
                    </select>
                  </div>
                  <div class="form-group mx-sm-2">
                    <select id="fStatus" class="form-control">
                      <option value="">All Status</option>
                      <option value="pending">Pending</option>
                      <option value="success">Success</option>
                      <option value="failed">Failed</option>
                    </select>
                  </div>
                  <div class="form-group mx-sm-2">
                    <input id="fFrom" type="date" class="form-control">
                  </div>
                  <div class="form-group mx-sm-2">
                    <input id="fTo" type="date" class="form-control">
                  </div>
                  <button id="btnApply" type="button" class="btn btn-primary ml-2">Apply</button>
                </form>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table id="txTable" class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Type</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Gateway</th>
                      <th>Reference</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </section>
</div>
@include('UserAdmin.common.footer')

{{-- ===== Modals ===== --}}

{{-- Add Funds Modal --}}
<div class="modal fade" id="modalAddFunds" tabindex="-1" role="dialog" aria-labelledby="modalAddFundsLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddFundsLabel">Add Funds</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <label class="small text-muted d-block mb-1">Enter amount</label>
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text" id="addSymbol">{{ $symbol }}</span>
          </div>
          <input type="number" class="form-control" id="addAmount" min="1" step="0.01" placeholder="0.00">
        </div>
        <small class="form-text text-muted mt-2">Minimum 1.00</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmAddFunds">Continue</button>
      </div>
    </div>
  </div>
</div>

{{-- Withdraw Modal (6 presets + optional custom) --}}
<div class="modal fade" id="modalWithdraw" tabindex="-1" role="dialog" aria-labelledby="modalWithdrawLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalWithdrawLabel">Withdraw Funds</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">

        <label class="small text-muted d-block mb-2">Choose an amount</label>
        <div class="d-flex flex-wrap mb-3" id="withdrawPresets">
          @php $preset = [100, 200, 500, 1000, 2000, 5000]; @endphp
          @foreach($preset as $amt)
            <button type="button" class="btn btn-outline-primary mr-2 mb-2 withdraw-preset" data-amount="{{ $amt }}">
              {{ $symbol }} {{ number_format($amt, 2) }}
            </button>
          @endforeach
          <button type="button" class="btn btn-outline-secondary mr-2 mb-2" id="presetCustom">Custom</button>
        </div>

        <div id="withdrawCustomWrap" class="d-none">
          <label class="small text-muted d-block mb-1">Enter custom amount</label>
          <div class="input-group mb-2">
            <div class="input-group-prepend">
              <span class="input-group-text">{{ $symbol }}</span>
            </div>
            <input type="number" class="form-control" id="withAmount" min="1" step="0.01" placeholder="0.00">
          </div>
          <small class="form-text text-muted">Minimum 1.00</small>
        </div>

        <small class="form-text text-muted mt-2">
          Withdrawals are recorded now and processed by payouts (e.g., RazorpayX) later.
        </small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="confirmWithdraw" disabled>Request Withdraw</button>
      </div>
    </div>
  </div>
</div>

{{-- ===== NEW: Payout Account Modal (CRUD) ===== --}}
<div class="modal fade" id="modalPayout" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Save payout account</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="formPayout">
          <input type="hidden" name="id" id="payoutId">
          <div class="form-group">
            <label>Type</label>
            <select name="type" id="payoutType" class="form-control">
              <option value="bank">Bank</option>
              <option value="upi">UPI</option>
              <option value="paypal">PayPal</option>
            </select>
          </div>
          <div class="form-group">
            <label>Account holder name</label>
            <input type="text" class="form-control" name="holder_name" id="holderName" required>
          </div>

          <div id="bankFields">
            <div class="form-group"><label>Account number</label><input type="text" class="form-control" name="account_number" id="accNumber"></div>
            <div class="form-group"><label>Confirm account</label><input type="text" class="form-control" name="confirm_account" id="accConfirm"></div>
            <div class="form-group"><label>IFSC</label><input type="text" class="form-control" name="ifsc" id="ifsc" placeholder="e.g. HDFC0001234"></div>
            <div class="form-row">
              <div class="form-group col"><label>Bank name</label><input type="text" class="form-control" name="bank_name" id="bankName"></div>
              <div class="form-group col"><label>Branch</label><input type="text" class="form-control" name="branch" id="branch"></div>
            </div>
          </div>

          <div id="upiFields" class="d-none">
            <div class="form-group"><label>UPI VPA</label><input type="text" class="form-control" name="upi_vpa" id="upiVpa" placeholder="name@upi"></div>
          </div>

          <div id="paypalFields" class="d-none">
            <div class="form-group"><label>PayPal email</label><input type="email" class="form-control" name="paypal_email" id="paypalEmail" placeholder="user@example.com"></div>
          </div>

          <div class="custom-control custom-checkbox mt-2">
            <input type="checkbox" class="custom-control-input" id="isDefault" name="is_default">
            <label class="custom-control-label" for="isDefault">Set as default</label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button id="btnSavePayout" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>
{{-- ===== /NEW: Payout Account Modal ===== --}}

{{-- If jQuery loaded AFTER our early shim, finish the no-op safely --}}
<script>
  (function(w){
    if (w.__needsNiceScrollShim__ && w.jQuery && !jQuery.fn.niceScroll) {
      jQuery.fn.niceScroll = function(){ return this; };
      delete w.__needsNiceScrollShim__;
    }
  })(window);
</script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Boot config for wallet.js (MUST come BEFORE wallet.js) --}}
<script>
  window.WALLET_PAGE = {
    symbol: @json($symbol),
    currency: @json($currencyCode),
    key: @json(config('services.razorpay.key')),
    paypalMode: @json(config('services.paypal.mode', 'sandbox')),
    routes: {
      txJson: @json(route('user.admin.wallet.transactions.json')),
      createOrder: @json(route('user.admin.wallet.add_funds.order')),
      callback: @json(route('user.admin.wallet.add_funds.callback')),
      withdraw: @json(route('user.admin.wallet.withdraw')),

      // NEW payout accounts + PayPal routes:
      payoutAccounts: @json(route('user.payout.accounts')),
      payoutSave: @json(route('user.payout.accounts.save')),
      payoutDelete: @json(route('user.payout.accounts.delete', ['acc' => ':id'])),
      payoutDefault: @json(route('user.payout.accounts.default', ['acc' => ':id'])),

      paypalOrder: @json(route('user.admin.wallet.paypal.order')),
      paypalCapture: @json(route('user.admin.wallet.paypal.capture')),
    },
    user: { name: @json($user->name), email: @json($user->email) },
    guards: {
      kycApproved: @json($kycApproved),
      twofaEnabled: @json($twofaEnabled)
    }
  };
</script>

<script src="{{ asset('assets/js/wallet.js') }}"></script>

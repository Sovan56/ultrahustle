{{-- resources/views/UserAdmin/profile.blade.php --}}
@include('UserAdmin.common.header')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

@php
  // ONE place to set active tab:
  $queryTab  = request('tab');            // ?tab=...
  $forcedTab = old('_target');            // from failed form
  $activeTab = $queryTab ?: ($forcedTab ?: (session('tab') ?: ($errors->any() ? 'security' : 'about')));
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

@php
use Illuminate\Support\Str;

$fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'User');

$avatar = $detail?->profile_picture
  ? url('/media/' . ltrim($detail->profile_picture, '/'))
  : asset('assets/img/users/user-1.png');

$bioFull  = $detail?->profile_description ?? '';
$bioShort = Str::limit(strip_tags($bioFull), 140);

$socials = $socials ?? [];

$mapIcon = [
  'facebook'  => 'fab fa-facebook-f',
  'twitter'   => 'fab fa-twitter',
  'x'         => 'fab fa-twitter',
  'github'    => 'fab fa-github',
  'instagram' => 'fab fa-instagram',
  'linkedin'  => 'fab fa-linkedin-in',
  'youtube'   => 'fab fa-youtube',
  'website'   => 'fas fa-globe',
];

// Compute once: is KYC approved?
$kycStatus     = strtolower((string)($kyc->status ?? ''));
$kycApproved   = $kycStatus === 'approved';
@endphp

<style>
  .profile-left .social-icons {
    display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center;
  }
  .profile-left .social-icons .btn, .profile-left .social-icons i { opacity: 1 !important; }
  .profile-left .platform-tags {
    display: flex; flex-wrap: wrap; gap: .4rem; justify-content: center;
  }
  .bio-content { word-break: break-word; }
  .bio-content p { margin-bottom: .5rem; }
  .badge-status { text-transform: capitalize; }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-body">
      <div class="row mt-sm-4">
        <!-- LEFT CARD -->
        <div class="col-12 col-md-12 col-lg-4">
          <div class="card author-box profile-left">
            <div class="card-body">
              <div class="author-box-center">
                <img alt="image" src="{{ $avatar }}" class="rounded-circle author-box-picture" style="height: 100px; object-fit: cover;" id="avatarPreview">
                <div class="clearfix"></div>
                <div class="author-box-name"><a href="#">{{ $fullName }}</a></div>
                <div class="author-box-job">
                  {{ $detail?->location ?? '—' }}
                  @if($country)
                    <span class="text-muted d-block" style="font-size: 12px;">{{ $country->name }} ({{ $country->code }})</span>
                  @endif
                </div>
              </div>

              <div class="text-center">
                <div class="author-box-description">
                  <p>{{ $bioShort ?: '—' }}</p>
                </div>

                @if(!empty($socials))
                  <div class="mb-2 mt-3">
                    <div class="text-small font-weight-bold">Connect</div>
                  </div>
                  <div class="social-icons mb-2">
                    @foreach($socials as $platform => $url)
                      @php $icon = $mapIcon[strtolower($platform)] ?? 'fas fa-link'; @endphp
                      <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-primary btn-icon" title="{{ ucfirst($platform) }}">
                        <i class="{{ $icon }}"></i>
                      </a>
                    @endforeach
                  </div>
                  <div class="platform-tags mb-2">
                    @foreach($socials as $platform => $url)
                      <span class="badge badge-light">{{ ucfirst($platform) }}</span>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          </div>

          @if($kyc)
            <div class="card">
              <div class="card-body">
                <div class="section-title d-flex justify-content-between align-items-center">
                  <span>KYC Status</span>
                  <span class="badge badge-{{ $kycApproved ? 'success' : ($kycStatus === 'rejected' ? 'danger' : 'warning') }} badge-status">
                    {{ $kyc->status }}
                  </span>
                </div>
                @if($kyc->review_notes)
                  <div class="text-muted" style="white-space:pre-line;">{{ $kyc->review_notes }}</div>
                @endif
              </div>
            </div>
          @endif
        </div>

        <!-- RIGHT CONTENT -->
        <div class="col-12 col-md-12 col-lg-8">
          <div class="card">
            <div class="padding-20">

              <ul class="nav nav-tabs" id="myTab2" role="tablist">
                <li class="nav-item"><a class="nav-link {{ $activeTab==='about'?'active':'' }}" data-toggle="tab" href="#about" role="tab">About</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab==='settings'?'active':'' }}" data-toggle="tab" href="#settings" role="tab">Setting</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab==='security'?'active':'' }}" data-toggle="tab" href="#security" role="tab">Security</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab==='kyc'?'active':'' }}" data-toggle="tab" href="#kyc" role="tab">KYC</a></li>
              </ul>

              <div class="tab-content tab-bordered" id="myTab3Content">
                {{-- ABOUT --}}
                <div class="tab-pane fade {{ $activeTab==='about'?'show active':'' }}" id="about" role="tabpanel">
                  <div class="row">
                    <div class="col-md-3 col-6 b-r"><strong>Full Name</strong>
                      <p class="text-muted">{{ $fullName }}</p>
                    </div>
                    <div class="col-md-3 col-6 b-r"><strong>Mobile</strong>
                      <p class="text-muted">{{ $user->phone_number ?? '—' }}</p>
                    </div>
                    <div class="col-md-3 col-6 b-r"><strong>Email</strong>
                      <p class="text-muted">{{ $user->email ?? '—' }}</p>
                    </div>
                    <div class="col-md-3 col-6"><strong>Location</strong>
                      <p class="text-muted">{{ $detail?->location ?? '—' }}</p>
                    </div>
                  </div>

                  <div class="section-title mt-4">Bio</div>
                  <div class="m-t-10 bio-content">{!! $bioFull ?: '—' !!}</div>
                </div>

                {{-- SETTINGS --}}
                <div class="tab-pane fade {{ $activeTab==='settings'?'show active':'' }}" id="settings" role="tabpanel">
                  @if(session('success') && $activeTab==='settings')
                    <div class="alert alert-success">{{ session('success') }}</div>
                  @endif
                  @if($errors->any() && $activeTab==='settings')
                    <div class="alert alert-danger">
                      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                  @endif

                  <form method="post" action="{{ route('user.admin.profile.update') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf
                    <div class="card-header">
                      <h4>Edit Profile</h4>
                    </div>

                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-md-6 col-12">
                          <label>First Name *</label>
                          <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                          <div class="invalid-feedback">Please fill in the first name</div>
                        </div>
                        <div class="form-group col-md-6 col-12">
                          <label>Last Name *</label>
                          <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
                          <div class="invalid-feedback">Please fill in the last name</div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-7 col-12">
                          <label>Email *</label>
                          <input type="email" name="email" class="form-control" value="{{ $user->email }}" readonly>
                          <small class="text-muted">Change email from the <strong>Security</strong> tab.</small>
                        </div>
                        <div class="form-group col-md-5 col-12">
                          <label>Phone *</label>
                          <input type="tel" name="phone" class="form-control" value="{{ old('phone', $user->phone_number) }}" required>
                          <div class="invalid-feedback">Please fill in the phone</div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-6 col-12">
                          <label>Profile Picture</label>
                          <input type="file" name="avatar" id="avatarInput" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                          <small class="text-muted d-block mt-1">Max 2MB. JPG/PNG/WebP.</small>
                        </div>
                        <div class="form-group col-md-6 col-12">
                          <label>Location *</label>
                          <input type="text" name="location" class="form-control" value="{{ old('location', $detail?->location) }}" required>
                          <div class="invalid-feedback">Please fill in your location</div>
                        </div>
                      </div>

                      {{-- Country + derived Currency --}}
                      <div class="row">
                        <div class="form-group col-md-6 col-12">
                          <label>Country</label>
                          <select id="countrySelect" name="country_id" class="form-control">
                            @if($country)
                              <option value="{{ $country->id }}" selected>
                                {{ $country->name }} ({{ $country->code }}) +{{ $country->phone }}
                              </option>
                            @endif
                          </select>
                          <small class="text-muted">Search by name/code/currency.</small>
                        </div>
                        <div class="form-group col-md-6 col-12">
                          <label>Currency</label>
                          <input type="text" id="currencyDisplay" class="form-control" value="{{ $country?->currency }}" readonly>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-12">
                          <label>Bio *</label>
                          <textarea name="bio" class="form-control summernote-simple" required>{{ old('bio', $bioFull) }}</textarea>
                          <div class="invalid-feedback">Please write your bio</div>
                        </div>
                      </div>

                      {{-- Socials (existing) --}}
                      <div class="row">
                        <div class="form-group col-12">
                          <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <label class="mb-2">Social Media Links</label>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="btnAddSocial">+ Add Link</button>
                          </div>

                          <div id="socialRows" class="mt-1">
                            @php
                              $initial = old('socials', []);
                              if (empty($initial) && !empty($socials)) {
                                foreach($socials as $p => $u) $initial[] = ['platform' => $p, 'url' => $u];
                              }
                            @endphp

                            @forelse($initial as $idx => $row)
                              <div class="input-group mb-2 social-row flex-nowrap">
                                <div class="input-group-prepend">
                                  <select name="socials[{{ $idx }}][platform]" class="form-control" required>
                                    @php $val = strtolower($row['platform'] ?? 'website'); @endphp
                                    @foreach(['facebook','twitter','github','instagram','linkedin','youtube','website'] as $p)
                                      <option value="{{ $p }}" @selected($val===$p)>{{ ucfirst($p) }}</option>
                                    @endforeach
                                  </select>
                                </div>
                                <input type="url" class="form-control" name="socials[{{ $idx }}][url]" placeholder="https://..." value="{{ $row['url'] ?? '' }}" required>
                                <div class="input-group-append">
                                  <button type="button" class="btn btn-danger btnRemoveSocial">&times;</button>
                                </div>
                              </div>
                            @empty
                              <div class="input-group mb-2 social-row flex-nowrap">
                                <div class="input-group-prepend">
                                  <select name="socials[0][platform]" class="form-control" required>
                                    @foreach(['facebook','twitter','github','instagram','linkedin','youtube','website'] as $p)
                                      <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                                    @endforeach
                                  </select>
                                </div>
                                <input type="url" class="form-control" name="socials[0][url]" placeholder="https://..." required>
                                <div class="input-group-append">
                                  <button type="button" class="btn btn-danger btnRemoveSocial">&times;</button>
                                </div>
                              </div>
                            @endforelse
                          </div>
                          <small class="text-muted">Only rows with a URL are saved.</small>
                        </div>
                      </div>
                    </div>

                    <div class="card-footer text-right">
                      <button class="btn btn-primary">Save Changes</button>
                    </div>
                  </form>
                </div>

                {{-- SECURITY --}}
                <div class="tab-pane fade {{ $activeTab==='security'?'show active':'' }}" id="security" role="tabpanel">
                  @if(session('success') && $activeTab==='security')
                    <div class="alert alert-success">{{ session('success') }}</div>
                  @endif
                  @if($errors->any() && $activeTab==='security')
                    <div class="alert alert-danger">
                      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                  @endif

                  <div class="row">
                    {{-- Change Password --}}
                    <div class="col-lg-6">
                      <div class="card">
                        <div class="card-header">
                          <h4>Change Password</h4>
                        </div>
                        <div class="card-body">
                          <form method="post" action="{{ route('user.admin.security.password') }}" class="needs-validation" novalidate>
                            @csrf
                            <input type="hidden" name="_target" value="security">

                            <div class="form-group">
                              <label>Old Password *</label>
                              <input type="password" name="old_password" class="form-control" required>
                            </div>

                            <div class="form-group">
                              <label>New Password *</label>
                              <input type="password" name="new_password" class="form-control" required>
                              <small class="text-muted">At least 8 characters, include a number.</small>
                            </div>

                            <div class="form-group">
                              <label>Confirm Password *</label>
                              <input type="password" name="confirm_password" class="form-control" required>
                            </div>

                            <button class="btn btn-primary">Change Password</button>
                          </form>

                        </div>
                      </div>
                    </div>

                    {{-- Change Email + OTP --}}
                    <div class="col-lg-6">
                      <div class="card">
                        <div class="card-header">
                          <h4>Change Email (OTP verification)</h4>
                        </div>
                        <div class="card-body">
                          <form method="post" action="{{ route('user.admin.security.email.request') }}" class="mb-3">
                            @csrf
                            <div class="form-group">
                              <label>New Email *</label>
                              <input type="email" name="new_email" class="form-control" placeholder="name@example.com" required>
                            </div>
                            <button class="btn btn-outline-primary">Send OTP</button>
                          </form>

                          <form method="post" action="{{ route('user.admin.security.email.verify') }}">
                            @csrf
                            <div class="form-group">
                              <label>New Email *</label>
                              <input type="email" name="new_email" class="form-control" placeholder="same new email" required>
                            </div>
                            <div class="form-group">
                              <label>OTP Code *</label>
                              <input type="text" name="code" class="form-control" maxlength="6" required>
                            </div>
                            <button class="btn btn-primary">Verify & Change Email</button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  {{-- 2FA (TOTP) --}}
                  <div class="card">
                    <div class="card-header">
                      <h4>Two-Factor Authentication (TOTP)</h4>
                    </div>
                    <div class="card-body">
                      @if($user->twofa_enabled)
                        <div class="alert alert-success">2FA is <strong>Enabled</strong>.</div>
                        <form method="post" action="{{ route('user.admin.security.2fa.disable') }}">
                          @csrf
                          <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="password" class="form-control" required>
                          </div>
                          <button class="btn btn-danger">Disable 2FA</button>
                        </form>
                      @else
                        <div class="alert alert-warning">2FA is <strong>Disabled</strong>.</div>

                        <div id="twofaSetupBox" class="mb-3" style="display:none;">
                          <div class="d-flex align-items-center flex-wrap">
                            <div id="twofaQr" class="mr-3 mb-3" style="width:220px;height:220px;border:1px solid #eee;border-radius:8px; display:flex; align-items:center; justify-content:center;"></div>

                            <div>
                              <div class="mb-2"><strong>Secret:</strong> <span id="twofaSecret"></span></div>
                              <small class="text-muted d-block">Scan the QR with Google Authenticator (or similar).</small>
                            </div>
                          </div>
                          <form method="post" action="{{ route('user.admin.security.2fa.enable') }}">
                            @csrf
                            <input type="hidden" name="secret" id="twofaSecretInput">

                            <div class="form-group mt-3">
                              <label>Enter 6-digit code</label>
                              <input type="text" name="code" class="form-control" maxlength="6" required>
                            </div>
                            <button class="btn btn-primary">Enable 2FA</button>
                          </form>

                        </div>

                        <button id="btnGenQr" class="btn btn-outline-primary">Generate QR</button>
                      @endif
                    </div>

                    {{-- ================== MANAGE RECOVERY CODES ================== --}}
                    <div class="card mt-3">
                      <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Manage recovery codes</h4>
                        @if(!empty($user->twofa_enabled))
                          <span class="badge badge-success">2FA Enabled</span>
                        @else
                          <span class="badge badge-warning">2FA Required</span>
                        @endif
                      </div>
                      <div class="card-body">
                        <p class="text-muted mb-2">
                          Recovery codes let you log in if you lose your phone. Each code works once.
                        </p>

                        @error('codes')
                          <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        @if(session('recovery_plain'))
                          <div class="alert alert-info">
                            <div class="mb-2"><strong>New codes generated:</strong></div>
                            <pre class="mb-2" style="white-space:pre-wrap">@foreach(session('recovery_plain') as $c){{ $c."\n" }}@endforeach</pre>
                            <a class="btn btn-sm btn-primary" href="{{ route('user.admin.security.2fa.recovery.download') }}">Download as .txt</a>
                          </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                          {{-- Regenerate (password confirm modal) --}}
                          <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#regenCodesModal" @disabled(empty($user->twofa_enabled))>
                            Regenerate codes
                          </button>
                        </div>

                        @if(empty($user->twofa_enabled))
                          <div class="alert alert-warning mt-3 mb-0">
                            <strong>Hint:</strong> Enable 2FA first to manage recovery codes and to change your email or password.
                          </div>
                        @else
                          <div class="alert alert-light mt-3 mb-0">
                            <strong>Tip:</strong> Store your codes offline (printed or in a password manager). Anyone with these codes can access your account.
                          </div>
                        @endif
                      </div>
                    </div>
                  </div> {{-- /card body (2FA) --}}
                </div> {{-- /tab-pane security --}}

                {{-- ===================== KYC ===================== --}}
                <div class="tab-pane fade {{ $activeTab==='kyc'?'show active':'' }}" id="kyc" role="tabpanel">
                  @if($kycApproved)
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                      <div>
                        <strong>KYC Approved.</strong>
                        Your verification has been approved.
                      </div>
                    </div>

                    {{-- (Optional) Read-only summary --}}
                    <div class="card">
                      <div class="card-header"><h4>Your KYC</h4></div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-6"><strong>Legal Name:</strong> {{ $kyc->legal_name }}</div>
                          <div class="col-md-6"><strong>DOB:</strong> {{ optional($kyc->dob)->format('Y-m-d') }}</div>
                        </div>
                        <div class="mt-2"><strong>Address:</strong><div style="white-space:pre-line">{{ $kyc->address }}</div></div>
                        <div class="mt-2"><strong>ID Type/Number:</strong> {{ $kyc->id_type }} / {{ $kyc->id_number }}</div>
                        @if($kyc->review_notes)
                          <div class="mt-2"><strong>Review Notes:</strong><div class="text-muted" style="white-space:pre-line">{{ $kyc->review_notes }}</div></div>
                        @endif
                      </div>
                    </div>
                  @else
                    @if(session('success') && $activeTab==='kyc')
                      <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any() && $activeTab==='kyc')
                      <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                      </div>
                    @endif

                    <form method="post" action="{{ route('user.admin.kyc.save') }}" enctype="multipart/form-data" id="kycForm">
                      @csrf
                      <input type="hidden" name="_target" value="kyc">

                      <div class="row">
                        <div class="form-group col-md-6">
                          <label>Legal Name *</label>
                          <input type="text" name="legal_name" class="form-control" value="{{ old('legal_name', $kyc?->legal_name) }}" required>
                        </div>
                        <div class="form-group col-md-6">
                          <label>DOB *</label>
                          <input type="date" name="dob" class="form-control" value="{{ old('dob', $kyc?->dob?->format('Y-m-d')) }}" required>
                        </div>
                      </div>

                      <div class="form-group">
                        <label>Address *</label>
                        <textarea name="address" class="form-control" rows="3" required>{{ old('address', $kyc?->address) }}</textarea>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-4">
                          <label>ID Type *</label>
                          <select name="id_type" class="form-control" required>
                            @foreach(['Aadhaar','PAN','Passport','Other'] as $opt)
                              <option value="{{ $opt }}" @selected(old('id_type', $kyc?->id_type)===$opt)>{{ $opt }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="form-group col-md-8">
                          <label>ID Number *</label>
                          <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $kyc?->id_number) }}" required>
                        </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-4">
                          <label>ID Front (≤4MB) *</label>
                          <input type="file" name="id_front" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                        </div>
                        <div class="form-group col-md-4">
                          <label>ID Back (≤4MB) *</label>
                          <input type="file" name="id_back" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                        </div>
                        <div class="form-group col-md-4">
                          <label>Selfie (≤4MB) *</label>
                          <input type="file" name="selfie" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                        </div>
                      </div>

                      <div class="text-right">
                        <button class="btn btn-primary">Submit KYC</button>
                      </div>
                    </form>
                  @endif
                </div>
                {{-- =================== /KYC =================== --}}
              </div> {{-- /tab-content --}}
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  @include('UserAdmin.common.settingbar')
</div>

{{-- Regenerate modal (password confirm) --}}
<div class="modal fade" id="regenCodesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Regenerate recovery codes</h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
    </div>
    <form method="POST" action="{{ route('user.admin.security.2fa.recovery.regen') }}">
      @csrf
      <div class="modal-body">
        <p class="mb-2">This will invalidate your existing recovery codes and create a new set.</p>
        <div class="form-group">
          <label>Confirm your password</label>
          <input type="password" name="password" class="form-control" required>
          @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning">Regenerate</button>
      </div>
    </form>
  </div></div>
</div>

@include('UserAdmin.common.footer')

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Keep active tab on reload (server passes session('tab'))
    const activeTab = "{{ $activeTab }}";
    if (activeTab) {
      const link = document.querySelector(`a[href="#${activeTab}"]`);
      link && link.click();
    }

    // Avatar preview
    const input = document.getElementById('avatarInput');
    const preview = document.getElementById('avatarPreview');
    if (input) {
      input.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => { preview.src = reader.result; };
        reader.readAsDataURL(file);
      });
    }

    // Social rows
    const rowsWrap = document.getElementById('socialRows');
    const addBtn = document.getElementById('btnAddSocial');

    function rowTemplate(idx) {
      return `
    <div class="input-group mb-2 social-row flex-nowrap">
      <div class="input-group-prepend">
        <select name="socials[${idx}][platform]" class="form-control" required>
          <option value="facebook">Facebook</option>
          <option value="twitter">Twitter</option>
          <option value="github">Github</option>
          <option value="instagram">Instagram</option>
          <option value="linkedin">Linkedin</option>
          <option value="youtube">YouTube</option>
          <option value="website">Website</option>
        </select>
      </div>
      <input type="url" class="form-control" name="socials[${idx}][url]" placeholder="https://..." required>
      <div class="input-group-append">
        <button type="button" class="btn btn-danger btnRemoveSocial">&times;</button>
      </div>
    </div>`;
    }

    if (addBtn) {
      addBtn.addEventListener('click', () => {
        const idx = rowsWrap.querySelectorAll('.social-row').length;
        rowsWrap.insertAdjacentHTML('beforeend', rowTemplate(idx));
      });
    }

    rowsWrap?.addEventListener('click', (e) => {
      if (e.target.classList.contains('btnRemoveSocial')) {
        const row = e.target.closest('.social-row');
        row?.remove();
      }
    });

    // Bootstrap client-side validation
    const forms = document.getElementsByClassName('needs-validation');
    Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    // -------- 2FA Generate QR --------
    const btnGenQr   = document.getElementById('btnGenQr');
    const setupBox   = document.getElementById('twofaSetupBox');
    const qrBox      = document.getElementById('twofaQr');
    const secretSpan = document.getElementById('twofaSecret');
    const secretInput= document.getElementById('twofaSecretInput');

    btnGenQr?.addEventListener('click', function() {
      fetch(`{{ route('user.admin.security.2fa.setup') }}`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
          if (!data || !data.secret || !data.otpauth) return;
          setupBox.style.display = 'block';
          secretSpan.textContent = data.secret;
          secretInput.value      = data.secret;

          qrBox.innerHTML = '';
          new QRCode(document.getElementById('twofaQr'), {
            text: data.otpauth, width: 220, height: 220
          });
        })
        .catch(() => {});
    });
  });
</script>

<script>
(function () {
  /* ========= Config ========= */
  var SELECTOR_COUNTRY = '#countrySelect';
  var SELECTOR_SEARCH  = '#countrySearch';    // optional text input for searching
  var SELECTOR_CURR    = '#currencyDisplay';  // optional (input or span)
  var SELECTOR_W_SYM   = '#wallet-symbol';    // optional on-page wallet symbol
  var SELECTOR_W_BAL   = '#wallet-balance';   // optional on-page wallet balance
  var SELECTOR_W_CODE  = '#currency-code';    // optional on-page currency code

  /* ========= Elements / jQuery ========= */
  var $doc = window.jQuery ? jQuery(document) : null;
  if (!$doc) {
    console.error('jQuery is required for the profile country script.');
    return;
  }

  var csrf = jQuery('meta[name="csrf-token"]').attr('content') || '';
  var countrySelect   = document.querySelector(SELECTOR_COUNTRY);
  var countrySearch   = document.querySelector(SELECTOR_SEARCH);
  var currencyDisplay = document.querySelector(SELECTOR_CURR);
  var $walletSymbol   = jQuery(SELECTOR_W_SYM);
  var $walletBalance  = jQuery(SELECTOR_W_BAL);
  var $currencyCode   = jQuery(SELECTOR_W_CODE);

  if (!countrySelect) return;

  /* ========= AJAX headers ========= */
  var ajaxHeaders = { 'Accept': 'application/json' };
  if (csrf) {
    ajaxHeaders['X-CSRF-TOKEN'] = csrf;
    jQuery.ajaxSetup({ headers: ajaxHeaders });
  }

  var ROUTES = window.PROFILE_COUNTRY_ROUTES || {
    countries: (window.PROFILE_COUNTRIES_URL || '{{ route("user.admin.countries") }}'),
    update:    (window.PROFILE_COUNTRY_UPDATE_URL || '{{ route("user.admin.profile.country.update") }}')
  };

  /* ========= Helpers ========= */
  function safeText($el, val) { if ($el && $el.length) $el.text(val); }
  function safeVal(el, val)    { if (!el) return; if ('value' in el) el.value = val; else el.textContent = val; }
  function toCurrencyString(n) { var num = parseFloat(n); if (isNaN(num)) num = 0; return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","); }

  function fillCountryOptions(items) {
    var current = String(countrySelect.value || '');
    countrySelect.innerHTML = '';
    (items || []).forEach(function (i) {
      var opt = document.createElement('option');
      opt.value = i.id;
      opt.textContent = i.name + ' (' + i.code + ') +' + i.phone;
      opt.dataset.currency = i.currency || '';
      if (String(i.id) === current) opt.selected = true;
      countrySelect.appendChild(opt);
    });
  }

  function fetchCountries(q) {
    q = q || '';
    var url = ROUTES.countries + (ROUTES.countries.indexOf('?') === -1 ? '?q=' : '&q=') + encodeURIComponent(q);

    return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (items) {
        if (!Array.isArray(items)) return;
        var hadSelection = !!countrySelect.value;
        fillCountryOptions(items);
        if (!hadSelection && items[0]) {
          if (currencyDisplay) safeVal(currencyDisplay, items[0].currency || '');
        }
      })
      .catch(function () { /* ignore */ });
  }

  function postCountryUpdate(countryId) {
    return jQuery.ajax({
      url: ROUTES.update, type: 'POST', dataType: 'json', headers: ajaxHeaders, data: { country_id: countryId }
    });
  }

  /* ========= Events ========= */
  $doc.on('change', SELECTOR_COUNTRY, function () {
    var opt = this.options[this.selectedIndex];
    var codeFromOption = (opt && opt.dataset) ? (opt.dataset.currency || '') : '';
    if (currencyDisplay) safeVal(currencyDisplay, codeFromOption);

    var id = this.value; if (!id) return;

    postCountryUpdate(id)
      .done(function (res) {
        if (res && res.success) {
          safeText($walletSymbol,  res.currency_symbol || '$');
          safeText($walletBalance, toCurrencyString(res.wallet || '0.00'));
          safeText($currencyCode,  res.currency || 'USD');
          if (currencyDisplay && res.currency) safeVal(currencyDisplay, res.currency);
        } else {
          alert('Could not update country. Please try again.');
        }
      })
      .fail(function () { alert('Failed to update country. Please try again.'); });
  });

  // Debounced searching
  var debounceTimer = null;
  function debounce(fn, wait) { clearTimeout(debounceTimer); debounceTimer = setTimeout(fn, wait || 350); }

  if (countrySearch) {
    countrySearch.addEventListener('keyup', function (e) {
      var val = e.target.value || '';
      debounce(function () { fetchCountries(val); }, 350);
    });
  } else {
    countrySelect.addEventListener('keyup', function (e) {
      var val = e.target.value || '';
      debounce(function () { fetchCountries(val); }, 350);
    });
  }

  /* ========= Initial load ========= */
  fetchCountries('');
})();
</script>

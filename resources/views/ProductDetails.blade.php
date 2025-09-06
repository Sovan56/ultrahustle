@include('common.header')
<link rel="stylesheet" href="{{ asset('customcss/productdetails.css') }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@php
  use Illuminate\Support\Str;

  // Fallbacks so blade never errors if controller didn't pass these
  $isLogged       = $isLogged       ?? (auth()->check() || session('user_id'));
  $alreadyWished  = $alreadyWished  ?? false;

  // --- Robust seller avatar fix ---
  $__sellerRaw = $sellerAvatar ?? '';
  $__sellerUrl = $__sellerRaw;

  $starts = fn($p) => \Illuminate\Support\Str::startsWith($p, ['http://','https://','/media/','/storage/']);
  if (! $starts($__sellerUrl)) {
      $__sellerUrl = route('media.pass', ['path' => ltrim($__sellerUrl,'/')]);
  } else {
      // If it's "/storage/..." convert to /media/storage/.. which our route normalizes
      if (str_starts_with($__sellerUrl, '/storage/')) {
          $__sellerUrl = route('media.pass', ['path' => substr($__sellerUrl, 1)]); // remove leading slash
      }
  }
@endphp

<div class="container py-5">
  <div class="row">
    <!-- Left -->
    <div class="col-lg-8">
      <!-- Breadcrumbs -->
      <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb mb-2">
          <li class="breadcrumb-item">
  <a href="{{ route('marketplace', [], false) }}?type_id={{ $product->type->id ?? '' }}">
    {{ $product->type->name ?? 'Category' }}
  </a>
</li>
<li class="breadcrumb-item">
  <a href="{{ route('marketplace', [], false) }}?type_id={{ $product->type->id ?? '' }}&sub_id={{ $product->subcategory->id ?? '' }}">
    {{ $product->subcategory->name ?? 'Subcategory' }}
  </a>
</li>

          <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
      </nav>

      <h4 class="fw-bold">{{ $product->name }}</h4>

      {{-- Wishlist heart (kept separate so original <h4> stays intact) --}}
      <div class="d-flex justify-content-end mb-2">
        @if($isLogged)
          <button id="btnWishlist" class="btn wishlist-btn" data-wished="{{ $alreadyWished ? '1':'0' }}" title="{{ $alreadyWished ? 'Remove from wishlist' : 'Add to wishlist' }}">
            {{-- Solid when wished, regular (outline) when not --}}
            @if($alreadyWished)
              <i id="wishIcon" class="fa-solid fas fa-heart" style="color:#CEFF1B !important;"></i>
            @else
              <i id="wishIcon" class="fa-regular far fa-heart" style="color:#CEFF1B !important;"></i>
            @endif
          </button>
        @else
          <a class="btn wishlist-btn" href="{{ route('login') }}?redirect={{ urlencode(request()->fullUrl()) }}" title="Login to wishlist">
            <i class="fa-regular far fa-heart" style="color:#CEFF1B !important;"></i>
          </a>
        @endif
      </div>

      <!-- Seller strip -->
      <div class="d-flex align-items-center gap-2 my-2">
        <img src="{{ user_avatar_url($product->user) }}" class="rounded-circle" alt="User"
             style="width:35px;height:35px;object-fit:cover"
             onerror="this.src='https://placehold.co/35x35?text=U'">
        <strong>{{ $sellerName }}</strong>

        @if($rating >= 4.7 && $reviewsCount >= 50)
          <span class="badge badge-top px-2 py-1 rounded">Top Rated <i class="fa fa-gem ms-1"></i></span>
        @endif

        <span class="small d-flex align-items-center gap-1">
          <i class="fa fa-star text-warning"></i>
          {{ $rating }} ({{ $reviewsCount }})
        </span>
      </div>

      <!-- Image Slider -->
      <div id="mainCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
        <div class="carousel-inner rounded">
          @foreach($images as $idx => $url)
            <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}">
              <img src="{{ $url }}" class="d-block w-100" alt="{{ $product->name }} - image {{ $idx+1 }}" @if($idx>0) loading="lazy" @endif onerror="this.src='https://placehold.co/750x400?text=Image+Not+Found'">
            </div>
          @endforeach
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
        </button>
      </div>

      <!-- Thumbs -->
      <div class="carousel-thumbs d-flex gap-2">
        @foreach($images as $i => $url)
          <img src="{{ $url }}" class="img-thumbnail" style="width:100px;height:60px;object-fit:cover"
               onclick="bootstrap.Carousel.getOrCreateInstance(document.getElementById('mainCarousel')).to({{ $i }})"
               alt="Thumb {{ $i+1 }}">
        @endforeach
      </div>

      <!-- About -->
      <div class="mt-4">
        <h5 class="fw-bold mt-4">About this gig</h5>
        <div>{!! $product->description !!}</div>
      </div>
    </div>

    <!-- Right Pricing Box -->
    <div class="col-lg-4 mt-5">
      <div class="price-box">
        @if(empty($tiers))
          <div class="p-3 border rounded text-center text-muted">Pricing not available.</div>
        @else
          <ul class="nav nav-tabs mb-3" id="packageTabs" role="tablist">
            @foreach($tiers as $k => $tier)
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $k===0 ? 'active' : '' }}"
                        id="{{ $tier['key'] }}-tab" data-bs-toggle="tab"
                        data-bs-target="#{{ $tier['key'] }}" type="button" role="tab">
                  {{ ucfirst($tier['label']) }}
                </button>
              </li>
            @endforeach
          </ul>

          <div class="tab-content" id="packageTabsContent">
            @foreach($tiers as $k => $tier)
              <div class="tab-pane fade {{ $k===0 ? 'show active' : '' }}" id="{{ $tier['key'] }}" role="tabpanel">
                <h4>{{ $tier['price_display'] }}</h4>
                @if(!empty($tier['details']))
                  <p class="small mb-2">{!! $tier['details'] !!}</p>
                @endif
                <ul class="list-unstyled small">
                  <li><i class="fa fa-clock me-2"></i>{{ $tier['delivery_days'] }}-day delivery</li>
                </ul>

                {{-- CTA logic --}}
@unless($isService)
  @if($alreadyPurchased)
    <div class="d-grid gap-2">
      <button class="btn w-100 mb-2" style="background-color: #CEFF1B; color: black;" disabled>Purchased</button>
      <a class="btn w-100" style="background-color: black; color: #CEFF1B; border: 1px solid #CEFF1B;" href="{{ route('user.myorders.page') }}">Go to My Orders</a>
    </div>
  @else
    <a class="btn btn-dark w-100 mb-2"
       @if($isDigitalOrCourse)
          href="javascript:void(0)"
          onclick="openBuySidebar('{{ $tier['key'] }}')"
       @else
          href="{{ route('checkout.show', $product->id) }}"
       @endif
       data-analytics="checkout">
       Continue
    </a>
  @endif
@endunless

                @if($isService)
                  <a class="btn btn-outline-dark w-100 my-2" href="javascript:void(0)" onclick="openChat()">Contact me</a>
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- ======== SELLER PUCK (bottom-left) ======== --}}
<style>
.seller-puck {
  position: fixed; left: 16px; bottom: 16px; z-index: 1050;
  background:#fff; border:1px solid #e5e7eb; border-radius: 16px; padding:10px 12px;
  display:flex; align-items:center; gap:10px; box-shadow:0 6px 16px rgba(0,0,0,.08);
  cursor:pointer;
}
.seller-status-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.seller-status-online { background:#22c55e; } /* green */
.seller-status-away   { background:#f59e0b; } /* amber */

/* ===== Floating Chat (Fiverr-like) ===== */
.chatbox {
  position: fixed; left: 16px; bottom: 84px; width: 380px; max-width: 95vw; z-index: 1051;
  background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 14px 28px rgba(0,0,0,.16);
  display:none; flex-direction:column; overflow:hidden;
}
.chatbox-banner {
  background:#111827; color:#fff; padding:8px 12px; font-size:13px;
}
.chatbox-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:10px 12px; border-bottom:1px solid #f1f5f9; background:#f8fafc;
}
.chatbox-body { height: 300px; overflow:auto; padding:12px; }
.chatbox-input { display:flex; gap:8px; padding:10px; border-top:1px solid #f1f5f9; align-items:center; }
.quick-chip {
  color: black; border:1px solid #e5e7eb; border-radius:18px; padding:8px 12px; font-size:13px; cursor:pointer;
  max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.icon-btn { color: black; border:1px solid #e5e7eb; border-radius:8px; width:36px; height:36px; display:flex; align-items:center; justify-content:center; }
.icon-btn:hover { background:#f8fafc; }

/* Small bubbles for appended mini-thread */
.bubble-me {
  align-self: flex-end;
  background: #e5f0ff;
  border: 1px solid #d7e6ff;
  color: black;
  border-radius: 12px 12px 4px 12px;
  padding: 8px 10px;
  max-width: 85%;
  word-break: break-word;
}
.bubble-file a { text-decoration: none; }

/* Wishlist + Stars */
.wishlist-btn i{ font-size:20px; line-height:1; }
#starWrap .star-rate { font-size:26px; cursor:pointer; margin-right:4px; }
#starWrap .text-warning { color:#CEFF1B !important; } /* theme yellow */
#starWrap .unfilled { color:#6c757d; }

/* Review images: same height & contain */
.review-img {
  width: 120px;
  height: 90px;
  object-fit: contain;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 4px;
}
.preview-img {
  width: 70px; height: 70px; object-fit: contain;
  background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:2px;
}
</style>

{{-- ======== SELLER PUCK (bottom-left) ======== --}}
<div class="seller-puck" onclick="openChat()">
  <img src="{{ user_avatar_url($product->user) }}" class="rounded-circle"
       style="width:36px;height:36px;object-fit:cover"
       onerror="this.src='https://placehold.co/36x36?text=U'">
  <div>
    <div class="fw-semibold" style="color: black;">{{ $sellerName }}</div>
    <div class="small text-muted">
      <span id="seller-puck-dot"
            class="seller-status-dot {{ $sellerOnline ? 'seller-status-online' : 'seller-status-away' }}"></span>
      <span id="seller-status-text">{{ $sellerOnline ? 'Online' : 'Away' }}</span>
      • Avg. response: <span id="seller-avg-text">{{ $avgResponseHuman }}</span>
    </div>
  </div>
</div>

{{-- ======== FLOATING CHAT BOX (Fiverr-style) ======== --}}
<div id="chatBox" class="chatbox">
  <!-- <div class="chatbox-banner">It’s {{ now()->format('h:i A') }} for {{ $sellerName }}. It might take some time to get a response</div> -->

  <div class="chatbox-header">
    <div class="d-flex align-items-center gap-2">
      <img src="{{ $__sellerUrl }}" class="rounded-circle" style="width:28px;height:28px;object-fit:cover"
           onerror="this.src='https://placehold.co/28x28.svg?text=U'">
      <div>
        <div class="fw-semibold m-0" style="line-height:1; color:black;">Message {{ $sellerName }}</div>
        <div class="small text-muted" style="line-height:1">
          {{-- INITIAL (server-side) dynamic presence + avg response; JS will sync on load --}}
          <span id="seller-header-dot"
                class="seller-status-dot {{ $sellerOnline ? 'seller-status-online' : 'seller-status-away' }}"></span>
          <span id="seller-header-status">{{ $sellerOnline ? 'Online' : 'Offline' }}</span>
          • Avg. response time: <span id="seller-header-avg">{{ $avgResponseHuman }}</span>
        </div>
      </div>
    </div>
    <button class="btn btn-sm btn-light" onclick="toggleChat(false)"><i class="fa fa-times"></i></button>
  </div>

  <div id="chatBody" class="chatbox-body">
    <div class="text-muted mb-2" style="font-size:13px;">
      Ask {{ $sellerName }} a question or share your project details (requirements, timeline, budget, etc.)
    </div>

    <div class="d-flex flex-column gap-2">
      <div class="quick-chip" onclick="chipToInput(this)">
        💻 Hey {{ $sellerName }}, I'm looking for website development work for...
      </div>
      <div class="quick-chip" onclick="chipToInput(this)">
        Hey {{ $sellerName }}, I'm looking for someone who has experience with platforms like...
      </div>
      <div class="quick-chip" onclick="chipToInput(this)">
        Hey {{ $sellerName }}, I've got a website design, can you help me with...
      </div>
    </div>

    <hr class="my-3">
    <!-- Mini chat thread (appends *your* just-sent bubbles so you see them immediately) -->
    <div id="chatThread" class="d-flex flex-column gap-2"></div>
  </div>

  <div class="chatbox-input">
    <button class="icon-btn" title="Attach file" onclick="document.getElementById('chatFile').click()">
      <i class="fa fa-paperclip"></i>
    </button>
    <input id="chatFile" type="file" style="display:none" onchange="onSelectFile(event)">

    <input id="chatInput" type="text" style="background-color: white !important;color: black !important" class="form-control" placeholder="Type your message…" oninput="toggleSend()">

    {{-- Open full chat (always visible; service and digital/course) --}}
    <a class="icon-btn" title="Open full chat"
       href="{{ route('user.messages', ['partner' => $product->user->id]) }}?product={{ $product->id }}&is_service={{ $isService ? 1 : 0 }}">
      <i class="fa fa-external-link-alt"></i>
    </a>

    <button id="chatSend" class="btn btn-primary" disabled>Send</button>
  </div>
</div>

{{-- ======== CHAT DRAWER (kept as-is, unused now) ======== --}}
<div class="offcanvas offcanvas-start" tabindex="-1" id="chatDrawer">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Chat with {{ $sellerName }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <div class="alert alert-info">Chat feature coming next. For now, this is a UI shell.</div>
    <textarea class="form-control" rows="3" placeholder="Type a message..."></textarea>
    <div class="d-flex gap-2 mt-2">
      <button class="btn btn-primary" disabled>Send</button>
      <button class="btn btn-outline-secondary" onclick="openFullChat()"
              title="Open full chat (messages page)">
        <i class="fa fa-comments"></i>
      </button>
    </div>
  </div>
</div>

<script>
function openFullChat() {
  requireLogin(() => {
    const base = @json(route('user.messages', ['partner' => $product->user->id]));
    const url  = `${base}?product={{ (int)$product->id }}&is_service={{ $isService ? 1 : 0 }}`;
    window.location.href = url;
  });
}
</script>

{{-- ======== RIGHT OFFCANVAS: ORDER SUMMARY (Digital/Course only) ======== --}}
@if($isDigitalOrCourse)
<div class="offcanvas offcanvas-end" tabindex="-1" id="buySidebar">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Order options</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <div id="buySidebarContent">
      <div class="text-center text-muted">Select a package to continue.</div>
    </div>
    <hr>
    <button id="btnSidebarContinue" class="btn btn-dark w-100" disabled>Continue</button>
  </div>
</div>

{{-- Confirm Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Confirm your order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="lineItems"></div>
        {{-- Removed per request (kept in codebase but not rendered) --}}
        {{-- <div class="alert alert-warning mt-3 mb-0">
          The total will be <b>deducted from your wallet</b>. Make sure you have sufficient balance.
        </div> --}}
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="btnPayWallet" class="btn btn-primary">Pay from Wallet</button>
      </div>
    </div>
  </div>
</div>
@endif

{{-- =========================
     DYNAMIC FAQs (same as yours)
   ========================= --}}
@php
  $faqGroups = collect($product->faqs ?? [])->groupBy(function($f){
      $h = trim((string)($f->faq_heading ?? ''));
      return $h !== '' ? $h : 'General';
  });
  $slug = function(string $text) { return \Illuminate\Support\Str::slug($text, '-'); };
@endphp

@if($faqGroups->isNotEmpty())
<section class="flat-spacing-11">
  <div class="container">
    <div class="tf-accordion-wrap d-flex justify-content-between">
      {{-- Left: headings list --}}
      <div class="box">
        <div class="tf-accordion-link-list w-100 sticky-top radius-10 border-line">
          @foreach($faqGroups as $heading => $list)
            <div class="tf-link-item">
              <a class="d-flex justify-content-between align-items-center line"
                 href="#faq-{{ $slug($heading) }}-{{ $loop->iteration }}">
                <h6 class="fw-5">{{ $heading }}</h6>
                <div class="icon"><i class="icon-arrow1-top-left"></i></div>
              </a>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Right: grouped accordions --}}
      <div class="content">
        @foreach($faqGroups as $heading => $list)
          <h5 id="faq-{{ $slug($heading) }}-{{ $loop->iteration }}" class="mb_24">{{ $heading }}</h5>
          <div class="flat-accordion style-default has-btns mb_60">
            @foreach($list as $i => $faq)
              <div class="flat-toggle {{ $loop->first ? 'active' : '' }}">
                <div class="toggle-title {{ $loop->first ? 'active' : '' }}">
                  {{ $faq->question ?: ($faq->faq_heading ?? 'FAQ') }}
                </div>
                <div class="toggle-content" @if($loop->first) style="display:block" @endif>
                  <p>{!! nl2br(e($faq->faq_answer ?? '')) !!}</p>
                </div>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>
@endif

{{-- =========================
     REVIEWS (Otika-styled + star UI + AJAX)
   ========================= --}}
<section id="reviews" class="mt-3 flat-spacing-11 container">
  <div class="section-header">
    <h1 class="h5 mb-2">Reviews ({{ $reviewsCount }})</h1>
  </div>

  {{-- ✅ Inline flash (no SweetAlert) --}}
  <div id="reviewsFlash" class="alert d-none mb-3" role="alert"></div>

  <div class="section-body" id="reviewsBody">

    {{-- Write a review (only if bought and not reviewed yet) --}}
    @if($isLogged && $alreadyPurchased && !$alreadyReviewed)
      <div class="card mb-4" style="background-color:black; color:white !important;">
        <div class="card-header">
          <h5 class="m-0" style="color:#CEFF1B !important;">Write a Review</h5>
        </div>
        <div class="card-body">
          <form id="reviewForm" method="POST" action="{{ route('product.review.store', $product->id) }}" enctype="multipart/form-data" class="needs-validation" novalidate>
            @csrf

            {{-- Star UI --}}
            <div class="form-group mb-3">
              <label>Your rating <span class="text-danger">*</span></label>
              <div id="starWrap" class="d-inline-block">
                @for($i=1;$i<=5;$i++)
                  <i class="fa-regular far fa-star star-rate unfilled" data-val="{{ $i }}"></i>
                @endfor
              </div>
              <div class="invalid-feedback d-block" id="ratingError" style="display:none">Please click a star.</div>

              {{-- Keep original select (hidden) so nothing is removed; we sync it via JS --}}
              <select name="rating_number" id="ratingSelect" class="form-control d-none" required>
                <option value="">Select…</option>
                @for($i=5;$i>=1;$i--)
                  <option value="{{ $i }}">{{ $i }} ★</option>
                @endfor
              </select>
            </div>

            <div class="form-group mb-3">
              <label>Your review (optional)</label>
              <textarea name="review" style="background-color:black; outline:none; color:white;" class="form-control" rows="3" maxlength="2000" placeholder="Share details about your experience…"></textarea>
            </div>

            {{-- Custom file picker with previews --}}
            <div class="form-group mb-4">
              <label class="d-block mb-1">Images (optional)</label>
              <input type="file" name="images[]" id="reviewImages" class="d-none" accept="image/*" multiple>
              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn" style="background-color:#000000 !important;color:#CEFF1B !important;border-color:#CEFF1B !important;" onclick="document.getElementById('reviewImages').click()">
                  <i class="fa fa-upload me-1"></i> Choose images
                </button>
                <span id="reviewImagesText" class="text-muted small">No files selected</span>
              </div>
              <div id="reviewPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
              <small class="form-text text-muted">Up to 6 images, max 4MB each.</small>
            </div>

            <button class="tf-btn animate-hover-btn btn-fill" type="submit">Submit review</button>
          </form>
        </div>
      </div>
    @endif


   {{-- List reviews --}}
@forelse($reviews as $rev)
  @php
    /** @var \App\Models\User|null $u */
    $u = $rev->user;
    $first = trim((string)($u->first_name ?? ''));
    $last  = trim((string)($u->last_name ?? ''));
    $full  = trim($first.' '.$last);
    if ($full === '') {
        $full = ($u->name ?? '') !== ''
          ? $u->name
          : (($u && $u->email) ? \Illuminate\Support\Str::before($u->email, '@') : 'User');
    }

    $dp = user_avatar_url($u);
    $imgs = is_array($rev->images) ? $rev->images : (json_decode($rev->images ?? '[]', true) ?: []);
  @endphp

  <div class="card mb-3">
    <div class="card-body" style="background-color:black; color:white !important;">
      <div class="media">
       <div class="d-flex align-items-center flex-wrap gap-2">
         <img src="{{ $dp }}" class="mr-3 rounded-circle" width="44" height="44" style="object-fit:cover"
             onerror="this.src='https://placehold.co/44x44?text=U'">
             <div>
               <h6 class="media-title mb-0" style="color: white !important;">{{ $full }}</h6>
              <div class="ml-2">
              @for($i=1;$i<=5;$i++)
                @if($i <= (int)$rev->rating_number)
                  <i class="fa-solid fas fa-star text-warning"></i>
                @else
                  <i class="fa-regular far fa-star text-secondary"></i>
                @endif
              @endfor
            </div>
            <span class="text-white small ml-2">{{ optional($rev->created_at)->diffForHumans() }}</span>
             </div>
       </div>
        <div class="media-body">
          @if($rev->review)
            <p class="mb-2 mt-2">{!! nl2br(e($rev->review)) !!}</p>
          @endif

          @if(!empty($imgs))
            <div class="d-flex flex-wrap gap-2">
              @foreach($imgs as $img)
                <a href="{{ $img }}" target="_blank">
                  <img src="{{ $img }}" class="review-img" alt="review image">
                </a>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@empty
  <div class="alert alert-dark text-center text-muted">No reviews yet.</div>
@endforelse


  </div>
</section>

<script>
/* Preview for custom file input */
document.addEventListener('DOMContentLoaded', function () {
  const input   = document.getElementById('reviewImages');
  const label   = document.getElementById('reviewImagesText');
  const preview = document.getElementById('reviewPreview');

  if (!input) return;

  input.addEventListener('change', function () {
    const files = Array.from(this.files || []);
    const count = files.length;

    label.textContent = count ? `${count} image${count > 1 ? 's' : ''} selected` : 'No files selected';

    preview.innerHTML = '';
    files.slice(0, 6).forEach(file => {
      const url = URL.createObjectURL(file);
      const img = document.createElement('img');
      img.src = url;
      img.className = 'preview-img';
      preview.appendChild(img);
      img.onload = () => URL.revokeObjectURL(url);
    });
  });
});
</script>

{{-- Otika/BS custom-file label helper (kept to not remove anything) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('reviewImages');
  if (!input) return;
  input.addEventListener('change', function () {
    var count = this.files ? this.files.length : 0;
    var label = this.nextElementSibling;
    if (label && label.classList.contains('custom-file-label')) {
      label.textContent = count ? (count + ' file(s) selected') : 'Choose images…';
    }
  });
});
</script>

@include('common.footer')

<script>
const IS_LOGGED_IN = {{ auth()->check() || session('user_id') ? 'true' : 'false' }};
const PRODUCT_ID   = {{ (int)$product->id }};
const IS_DIGITAL_OR_COURSE = {{ $isDigitalOrCourse ? 'true' : 'false' }};
const IS_SERVICE   = {{ $isService ? 'true' : 'false' }};
const PRODUCT_NAME = @json($product->name);
const SELLER_NAME  = @json($sellerName);
const SELLER_ID    = {{ (int)($product->user->id ?? 0) }};

// --- Echo helper & channel tracker (prevents "join" error if Echo isn't loaded) ---
function hasEcho() {
  return !!(window.Echo && typeof window.Echo.join === 'function' && typeof window.Echo.leave === 'function');
}
let MINI_CHANNEL = null;

function requireLogin(thenCb) {
  if (IS_LOGGED_IN) return thenCb();
  window.location.href = "{{ route('login') }}?redirect={{ urlencode(request()->fullUrl()) }}";
}

/* ---------- Chat (Fiverr-like) ---------- */
let MINI_CONV_ID = 0;
let MINI_TYPING = false, MINI_TYP_TIMER = null;

function openChat() { requireLogin(() => {
  toggleChat(true);
  loadMiniHistory();
}); }

function toggleChat(show){
  const el = document.getElementById('chatBox');
  el.style.display = show ? 'flex' : 'none';
}

function chipToInput(el){
  const inp = document.getElementById('chatInput');
  inp.value = el.innerText.trim();
  inp.focus();
  toggleSend();
}
function onSelectFile(e){
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > (5 * 1024 * 1024 * 1024)) {
    e.target.value = ''; return;
  }
  toggleSend();
}
function toggleSend(){
  const hasText = (document.getElementById('chatInput').value || '').trim().length > 0;
  const hasFile = (document.getElementById('chatFile').files || []).length > 0;
  document.getElementById('chatSend').disabled = !(hasText || hasFile);
}

// render bubble
function renderBubble(m) {
  const mine = Number(m.sender_id) === Number(@json(auth()->id() ?? 0));
  const cls  = mine ? 'text-end' : 'text-start';
  const wrap = document.createElement('div');
  wrap.className = cls + ' mb-2';

  let html = '';
  // enforce wrapping + readable colors
  const boxStyle = 'max-width:80%;word-break:break-word;white-space:pre-wrap;';
  const boxClass = 'd-inline-block p-2 rounded ' + (mine
    ? 'bg-primary text-white'
    : 'bg-dark text-white');

  if (m.body) {
    html += `<div class="${boxClass}" style="${boxStyle}">${escapeHtml(m.body)}</div>`;
  }

  if (m.file && m.file.url) {
    if (m.file.is_image) {
      html += `<div class="mt-1">
        <a href="${m.file.url}" target="_blank">
          <img src="${m.file.url}" style="max-width:220px;border-radius:8px"
               onerror="this.src='https://placehold.co/220x160?text=Img'">
        </a>
      </div>`;
    } else {
      html += `<div class="mt-1">
        <a class="btn btn-sm btn-outline-secondary" href="${m.file.url}" target="_blank" download>
          ${m.file.name || 'Download file'}
        </a>
      </div>`;
    }
  }

  if (mine) {
    const st = m.status || '';
    html += `<div class="small text-muted mt-1">${st}</div>`;
  }

  wrap.innerHTML = html;
  return wrap;
}

function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function scrollMini(){ const b=document.getElementById('chatBody'); b.scrollTop = b.scrollHeight; }

/* --------- Unified meta setter (puck + header) ---------- */
function setSellerMeta(online, avgHuman) {
  const onlineBool = !!online;
  const avg = (avgHuman || '—').toString();

  // Puck
  const puckDot = document.getElementById('seller-puck-dot');
  const puckStatus = document.getElementById('seller-status-text');
  const puckAvg = document.getElementById('seller-avg-text');
  if (puckDot) {
    puckDot.classList.toggle('seller-status-online', onlineBool);
    puckDot.classList.toggle('seller-status-away', !onlineBool);
  }
  if (puckStatus) puckStatus.textContent = onlineBool ? 'Online' : 'Away';
  if (puckAvg) puckAvg.textContent = avg;

  // Mini header
  const hdrDot = document.getElementById('seller-header-dot');
  const hdrStatus = document.getElementById('seller-header-status');
  const hdrAvg = document.getElementById('seller-header-avg');
  if (hdrDot) {
    hdrDot.classList.toggle('seller-status-online', onlineBool);
    hdrDot.classList.toggle('seller-status-away', !onlineBool);
  }
  if (hdrStatus) hdrStatus.textContent = onlineBool ? 'Online' : 'Offline';
  if (hdrAvg) hdrAvg.textContent = avg;
}

/** Lightweight refresh of online + avg without opening chat; also captures MINI_CONV_ID */
async function refreshSellerMeta() {
  try {
    const url = new URL(@json(route('chat.history')), window.location.origin);
    url.searchParams.set('partner_id', SELLER_ID);
    url.searchParams.set('product_id', PRODUCT_ID);
    url.searchParams.set('from_service', IS_SERVICE ? 1 : 0);
    url.searchParams.set('limit', '1');
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    const js  = await res.json();
    if (!js?.ok) return;

    if (!MINI_CONV_ID && js.conversation_id) MINI_CONV_ID = js.conversation_id;

    setSellerMeta(!!(js.partner && js.partner.online), js.partner?.avg_response || '—');
  } catch (_) {}
}

/* preserve for internal calls (used by presence join/leave) */
function setMiniPresence(online){
  const currentAvg =
    (document.getElementById('seller-header-avg')?.textContent || '').trim() ||
    (document.getElementById('seller-avg-text')?.textContent || '').trim() ||
    '—';
  setSellerMeta(!!online, currentAvg);
}

async function loadMiniHistory(){
  // get/create conv + last 50
  const url = new URL(@json(route('chat.history')), window.location.origin);
  url.searchParams.set('partner_id', SELLER_ID);
  url.searchParams.set('product_id', PRODUCT_ID);
  url.searchParams.set('from_service', IS_SERVICE ? 1 : 0);
  const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const js  = await res.json().catch(()=>({ ok:false }));
  if (!js.ok) return;

  MINI_CONV_ID = js.conversation_id;

  // Sync presence + avg in both header and puck
  setSellerMeta(!!(js.partner && js.partner.online), js.partner?.avg_response || '—');

  // render messages
  const body = document.getElementById('chatBody');
  body.innerHTML = `
    <div class="text-muted mb-2" style="font-size:13px;">
      Ask ${SELLER_NAME} a question or share your project details (requirements, timeline, budget, etc.)
    </div>
    <div class="d-flex flex-column gap-2">
      <div class="quick-chip" onclick="chipToInput(this)">
        💻 Hey ${SELLER_NAME}, I'm looking for website development work for...
      </div>
      <div class="quick-chip" onclick="chipToInput(this)">
        Hey ${SELLER_NAME}, I'm looking for someone who has experience with platforms like...
      </div>
      <div class="quick-chip" onclick="chipToInput(this)">
        Hey ${SELLER_NAME}, I'm looking for a developer who can help me with...
      </div>
    </div>
  `;
  (js.messages || []).forEach(m => body.appendChild(renderBubble(m)));
  scrollMini();

  // subscribe presence & events (SAFE if Echo isn't present)
  const channelName = `presence-chat.conversation.${MINI_CONV_ID}`;
  if (!hasEcho()) {
    console.warn('[chat-mini] Echo not available on this page; presence disabled.');
  } else {
    if (MINI_CHANNEL && MINI_CHANNEL !== channelName) {
      try { window.Echo.leave(MINI_CHANNEL); } catch (_) {}
    }

    window.Echo
      .join(channelName)
      .here(updateMiniPresence)
      .joining(updateMiniPresence)
      .leaving(updateMiniPresence)
      .listen('.chat.new', (e) => {
        const msg = { id:e.id, sender_id:e.sender_id, body:e.body, file:e.file, status:'delivered' };
        body.appendChild(renderBubble(msg));
        scrollMini();
        fetch(`/chat/${MINI_CONV_ID}/delivered`, { method:'POST', headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' }});
        if (document.hasFocus()) {
          fetch(`/chat/${MINI_CONV_ID}/seen`, { method:'POST', headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' }});
        }
      })
      .listen('.chat.typing', () => { /* optional mini typing UI */ })
      .listen('.chat.delivered', () => {})
      .listen('.chat.seen', () => {});
    MINI_CHANNEL = channelName;
  }
}

function updateMiniPresence(members){
  const online = (members||[]).length > 1;
  const currentAvg =
    (document.getElementById('seller-header-avg')?.textContent || '').trim() ||
    (document.getElementById('seller-avg-text')?.textContent || '').trim() ||
    '—';
  setSellerMeta(online, currentAvg);
}

document.getElementById('chatSend')?.addEventListener('click', sendQuickMessage);
document.getElementById('chatInput')?.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if (!document.getElementById('chatSend').disabled) sendQuickMessage();
  }
});

async function sendQuickMessage(){
  requireLogin(async () => {
    const text = (document.getElementById('chatInput').value || '').trim();
    const file = (document.getElementById('chatFile').files || [])[0] || null;

    const hasEmail = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i.test(text);
    const hasPhone = /\+?\d[\d\-\s()]{7,}\d/.test(text);
    if (text && (hasEmail || hasPhone)) { return; }
    if (!text && !file) { return; }
    if (file && file.size > (5 * 1024 * 1024 * 1024)) { return; }

    const fd = new FormData();
    fd.append('partner_id', SELLER_ID);  // correct key
    fd.append('product_id', PRODUCT_ID);
    fd.append('from_service', IS_SERVICE ? 1 : 0);
    if (text) fd.append('body', text);
    if (file) fd.append('file', file);

    const res = await fetch(@json(route('chat.seed')), {
      method: 'POST', headers: {'X-CSRF-TOKEN': @json(csrf_token())}, body: fd
    });
    const js = await res.json().catch(()=>({ ok:false }));

    if (!js.ok) return;

    // render my bubble immediately (no alerts, no redirects)
    const my = js.message || {body:text, file:null};
    my.sender_id = Number(@json(auth()->id() ?? 0)); my.status = 'sent';
    document.getElementById('chatBody').appendChild(renderBubble(my));
    document.getElementById('chatInput').value='';
    document.getElementById('chatFile').value='';
    toggleSend();

    // ensure we have conv id & presence
    if (!MINI_CONV_ID && js.conversation_id) {
      MINI_CONV_ID = js.conversation_id;
      loadMiniHistory();
    }
  });
}

/* ---------- Buying (offcanvas + modal) ---------- */
let selectedTier = null; let cachedQuote = null;
function openBuySidebar(tierKey) {
  if (IS_SERVICE) { openChat(); return; }
  requireLogin(() => {
    selectedTier = tierKey;
    const cap = tierKey.charAt(0).toUpperCase() + tierKey.slice(1);
    const template = `Hey ${SELLER_NAME}, I'm interested in the ${cap} package for "${PRODUCT_NAME}". My requirements are: [brief goals, pages/features, timeline, budget].`;
    document.getElementById('chatInput').value = template;
    toggleSend();

    fetch("{{ route('checkout.quote') }}", {
      method:'POST',
      headers:{'X-CSRF-TOKEN': '{{ csrf_token() }}','Content-Type':'application/json'},
      body: JSON.stringify({ product_id: PRODUCT_ID, tier: tierKey })
    })
    .then(async (r) => {
      if (!r.ok) throw new Error(await r.text());
      return r.json();
    })
    .then((d) => {
      // 🔒 CURRENCY / PROFILE GUARD — insert here
      if (d.can_pay === false) {
        alert(d.block_reason || 'Please set your country first.');
        return;
      }

      // ✅ proceed to render the sidebar
      cachedQuote = d;
      const cur = (d.currency_symbol || d.currency || '');
      document.getElementById('buySidebarContent').innerHTML = `
        <div class="card border"><div class="card-body">
          <div class="d-flex justify-content-between">
            <div><b>${d.tier.toUpperCase()}</b></div>
            <div>${cur}${d.base.toFixed(2)}</div>
          </div>
          <div class="text-muted small mb-2">${PRODUCT_NAME}</div>
          <hr>
          <div class="d-flex justify-content-between">
            <div>Platform fee (${d.platform_fee_percent}%)</div>
            <div>${cur}${d.platform_fee_amount.toFixed(2)}</div>
          </div>
          <div class="d-flex justify-content-between">
            <div>GST (${d.gst_percent}%)</div>
            <div>${cur}${d.gst_amount.toFixed(2)}</div>
          </div>
          <hr>
          <div class="d-flex justify-content-between fw-bold">
            <div>Total</div>
            <div>${cur}${d.total.toFixed(2)}</div>
          </div>
        </div></div>
      `;
      document.getElementById('btnSidebarContinue').disabled = false;
      bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('buySidebar')).show();
    })
    .catch(() => {
      alert('Could not load quote');
    });
  });
}

document.getElementById('btnSidebarContinue')?.addEventListener('click', () => {
  const oc = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('buySidebar'));
  oc.hide();
  setTimeout(()=> {
    document.getElementById('lineItems').innerHTML =
      document.getElementById('buySidebarContent').innerHTML;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')).show();
  }, 250);
});
document.getElementById('btnPayWallet')?.addEventListener('click', () => {
  if (!selectedTier) return;
  const btn = document.getElementById('btnPayWallet');
  btn.disabled = true; btn.innerText = 'Processing...';
  fetch("{{ route('checkout.wallet') }}", {
    method: 'POST',
    headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
    body: JSON.stringify({ product_id: PRODUCT_ID, tier: selectedTier })
  }).then(async r => {
    if (!r.ok) throw new Error(await r.text());
    return r.json();
  }).then(res => {
    if (res.ok && res.redirect) window.location.href = res.redirect;
    else throw new Error('Failed');
  }).catch(() => {
    btn.disabled = false; btn.innerText = 'Pay from Wallet';
  });
});

// Mark seen when user returns focus to the page
window.addEventListener('focus', () => {
  if (MINI_CONV_ID) {
    fetch(`/chat/${MINI_CONV_ID}/seen`, { method:'POST', headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' }});
  }
});

/* Initial meta fetch + keep fresh even if chat isn't opened */
document.addEventListener('DOMContentLoaded', () => {
  refreshSellerMeta();         // immediately get true online/avg
  setInterval(refreshSellerMeta, 25000); // keep it fresh like the rest of the app
});

/* ------- Helpers to flash messages in Reviews section & reload it ------- */
function flashReviews(message, kind='success') {
  const box = document.getElementById('reviewsFlash');
  if (!box) return;
  box.classList.remove('d-none','alert-success','alert-danger','alert-warning','alert-info');
  box.classList.add('alert-' + (kind || 'success'));
  box.textContent = message;
  // auto-hide after a bit
  setTimeout(()=>{ box.classList.add('d-none'); }, 4500);
}

/** Reload only the reviews section (no full page refresh). */
async function reloadReviewsSection() {
  try {
    const res  = await fetch(window.location.href, { credentials:'same-origin' });
    const html = await res.text();
    const doc  = new DOMParser().parseFromString(html, 'text/html');

    const newHeader = doc.querySelector('#reviews .section-header');
    const newBody   = doc.querySelector('#reviews .section-body');

    if (newHeader && document.querySelector('#reviews .section-header')) {
      document.querySelector('#reviews .section-header').innerHTML = newHeader.innerHTML;
    }
    if (newBody && document.getElementById('reviewsBody')) {
      document.getElementById('reviewsBody').innerHTML = newBody.innerHTML;
    }
  } catch (e) {
    console.warn('Could not reload reviews section', e);
  }
}

/* ------- Wishlist toggle + Review Star UI + AJAX submit ------- */
document.addEventListener('DOMContentLoaded', function () {
  // Wishlist toggle (NO SweetAlert)
  const btnWish = document.getElementById('btnWishlist');
  const wishIcon = document.getElementById('wishIcon');
  if (btnWish && wishIcon) {
    btnWish.addEventListener('click', function () {
      requireLogin(async () => {
        try {
          const res = await fetch(@json(route('wishlist.toggle')), {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Content-Type': 'application/json'},
            body: JSON.stringify({ product_id: {{ (int)$product->id }} })
          });
          const js = await res.json();
          if (js.ok) {
            const wished = !!js.wished;
            btnWish.dataset.wished = wished ? '1' : '0';

            // Flip icon between regular/solid + color
            wishIcon.classList.toggle('fa-solid', wished);
            wishIcon.classList.toggle('fas', wished);
            wishIcon.classList.toggle('fa-regular', !wished);
            wishIcon.classList.toggle('far', !wished);
            wishIcon.classList.toggle('text-danger', wished);
            wishIcon.classList.toggle('text-secondary', !wished);

            // 🔄 Refresh header wishlist badge silently
            try {
              const cRes = await fetch(@json(route('wishlist.count')));
              const cJs  = await cRes.json();
              const b    = document.getElementById('wishlistCountBadge') || document.querySelector('.nav-wishlist .count-box');
              if (b && cJs && typeof cJs.count === 'number') b.textContent = cJs.count;
            } catch(_){}
          }
        } catch (_) {}
      });
    });
  }

  // Review star select
  const stars = document.querySelectorAll('.star-rate');
  const ratingSelect = document.getElementById('ratingSelect');
  const ratingError  = document.getElementById('ratingError');

  function setStarIcon(el, filled){
    el.classList.toggle('fa-solid', filled);
    el.classList.toggle('fas', filled);
    el.classList.toggle('fa-regular', !filled);
    el.classList.toggle('far', !filled);
    el.classList.toggle('text-warning', filled);
    el.classList.toggle('text-secondary', !filled);
    el.classList.toggle('unfilled', !filled);
  }
  function paintStars(n) {
    stars.forEach(function (el) {
      const v = parseInt(el.getAttribute('data-val'), 10);
      setStarIcon(el, v <= n);
    });
  }
  stars.forEach(function (el) {
    el.addEventListener('mouseover', function () { paintStars(parseInt(this.dataset.val,10)); });
    el.addEventListener('click', function () {
      const v = parseInt(this.dataset.val,10);
      if (ratingSelect) ratingSelect.value = String(v);
      paintStars(v);
      if (ratingError) ratingError.style.display = 'none';
    });
  });
  document.getElementById('starWrap')?.addEventListener('mouseleave', function () {
    const v = parseInt(ratingSelect?.value || '0', 10);
    paintStars(v);
  });

  // AJAX submit review — NO SweetAlert; uses inline flash + partial reload
  const form = document.getElementById('reviewForm');
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      if (!ratingSelect || !ratingSelect.value) {
        if (ratingError) ratingError.style.display = 'block';
        flashReviews('Please select a star rating.', 'warning');
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('button');
      const prevHtml = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = 'Submitting…'; }

      const fd = new FormData(form);
      try {
        const res = await fetch(@json(route('product.review.store', $product->id)), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': @json(csrf_token()) },
          body: fd
        });

        const ct = (res.headers.get('content-type') || '').toLowerCase();
        let js = null;
        if (ct.includes('application/json')) {
          try { js = await res.json(); } catch(_) { js = null; }
        }

        if (res.status === 403) {
          const msg = (js && (js.message || js.error)) || 'You can only review items you purchased.';
          flashReviews(msg, 'danger');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = prevHtml; }
          return;
        }
        if (res.status === 422 && js && js.errors) {
          const msg = Object.values(js.errors).flat().join(' ') || 'Validation failed.';
          flashReviews(msg, 'danger');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = prevHtml; }
          return;
        }
        if (!res.ok) {
          const msg = (js && (js.message || js.error)) || 'Could not submit review.';
          flashReviews(msg, 'danger');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = prevHtml; }
          return;
        }

        // ✅ Success — show flash, reset form, reload only the reviews list
        flashReviews('Your review has been submitted.', 'success');

        // reset form fields
        form.reset();
        document.getElementById('reviewPreview')?.replaceChildren();
        // reset stars to unfilled
        paintStars(0);

        // reload reviews section
        await reloadReviewsSection();

        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = prevHtml; }
      } catch (_) {
        flashReviews('Network error. Please try again.', 'danger');
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = prevHtml; }
      }
    });
  }
});
</script>

@include('common.header')
<link rel="stylesheet" href="{{ asset('rebuildfrontend/css/productdetails.css') }}">


@php
  use Illuminate\Support\Str;

  $isLogged = $isLogged ?? (auth()->check() || session('user_id'));
  $productUrl = route('product.details', $product->id);
  $breadcrumbs = [
    [
      'label' => $product->type->name ?? 'Category',
      'href'  => route('marketplace', [], false) . '?type_id=' . ($product->type->id ?? '')
    ],
    [
      'label' => $product->subcategory->name ?? 'Subcategory',
      'href'  => route('marketplace', [], false) . '?type_id=' . ($product->type->id ?? '') . '&sub_id=' . ($product->subcategory->id ?? '')
    ]
  ];
@endphp

<style>
/* ----- Toast (theme) ----- */
.uhs-toast-wrap{position:fixed;right:16px;bottom:16px;z-index:1060;display:flex;flex-direction:column;gap:10px;}
.uhs-toast{background:#0b0b0b;border:1px solid rgba(206,255,27,.25);color:#fff;padding:12px 14px;border-radius:10px;box-shadow:0 10px 24px rgba(0,0,0,.2);display:flex;gap:10px;align-items:flex-start;max-width:360px}
.uhs-toast.ok{border-color:#CEFF1B}
.uhs-toast.err{border-color:#ff4d4f}
.uhs-toast i{margin-top:2px}
.uhs-toast .msg{flex:1}
.uhs-toast .x{border:none;background:transparent;color:#fff;opacity:.6}
.uhs-toast .x:hover{opacity:1}
/* make sticky summary truly sticky on desktop */
.uhs-sticky{position:sticky;top:16px}
.uhs-heart{font-size:18px}
</style>

<!-- ================= ULTRA HUSTLE — SERVICE LISTING PAGE ================= -->
<section id="serviceListingPage" class="uhs-root">
  <div class="uhs-wrap">
    <div class="uhs-container">

      <!-- ===== Breadcrumbs ===== -->
      <nav class="uhs-row uhs-gap2 uhs-text-sm uhs-white70 uhs-mb3" aria-label="breadcrumb">
        <a class="uhs-link" href="{{ $breadcrumbs[0]['href'] }}">{{ $breadcrumbs[0]['label'] }}</a>
        <span>›</span>
        <a class="uhs-link" href="{{ $breadcrumbs[1]['href'] }}">{{ $breadcrumbs[1]['label'] }}</a>
        <span>›</span>
        <span class="uhs-white">{{ $product->name }}</span>
      </nav>

      <div class="uhs-grid">
        <!-- =========== MEDIA (Left) =========== -->
        <div class="uhs-media-col">
          <div class="uhs-card uhs-p3">
            <div class="uhs-stage">
              <img id="uhsStageImg" class="uhs-stage-img" alt="{{ $product->name }}">
              <button class="uhs-stage-nav uhs-left" id="uhsPrev" aria-label="Previous">
                <i class="fa-solid fa-chevron-left"></i>
              </button>
              <button class="uhs-stage-nav uhs-right" id="uhsNext" aria-label="Next">
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>
            <div class="uhs-thumbs" id="uhsThumbs"></div>
          </div>
        </div>

        <!-- =========== HERO DETAILS (Right) =========== -->
        <div class="uhs-hero-col">
          <div class="uhs-card uhs-p5">
            <h1 class="uhs-title">{{ $product->name }}</h1>

            <div class="uhs-row uhs-gap3 uhs-mt2 uhs-wrap">
              <div class="uhs-rating" id="uhsRating"></div>
              <span class="uhs-white90">• {{ $reviewsCount }} reviews</span>
            </div>

            <div class="uhs-row uhs-between uhs-mt3 uhs-gap3">
              <div class="uhs-row uhs-gap3">
                <div class="uhs-avatar lg">
                  <img class="useravatarlg" src="{{ user_avatar_url($product->user) }}" alt="{{ $sellerName }}" onerror="this.src='https://placehold.co/36x36?text=U'">
                </div>
                <div class="uhs-white90">
                  <div class="uhs-row uhs-gap2 uhs-text-sm">
                    <span class="uhs-body">{{ $sellerName }}</span>
                    <span class="uhs-white60">•</span>
                    <span class="uhs-row uhs-gap1 uhs-white60">
                      <span class="seller-dot" style="width:8px;height:8px;border-radius:50%;display:inline-block;background:{{ $sellerOnline ? '#22c55e' : '#f59e0b' }}"></span>
                      {{ $sellerOnline ? 'Online' : 'Away' }}
                    </span>
                  </div>
                  <div class="uhs-white60 uhs-text-xs">Safe checkout • Message first for custom needs</div>
                </div>
              </div>

              <div class="uhs-row uhs-gap2">
                <button id="btnShare" class="uhs-ghost" title="Share"><i class="fa-solid fa-share-nodes"></i></button>

                @if($isLogged)
                  <button id="btnWishlist" class="uhs-ghost uhs-heart" title="{{ $alreadyWished ? 'Remove from wishlist' : 'Save' }}">
                    @if($alreadyWished)
                      <i id="wishIcon" class="fa-solid fa-heart"></i>
                    @else
                      <i id="wishIcon" class="fa-regular fa-heart"></i>
                    @endif
                  </button>
                @else
                  <a class="uhs-ghost uhs-heart" href="{{ route('login') }}?redirect={{ urlencode(request()->fullUrl()) }}" title="Login to save">
                    <i class="fa-regular fa-heart"></i>
                  </a>
                @endif
              </div>
            </div>
            <div class="uhp-tags" id="uhpRelatedTags">
              @if($product->uses_ai)
              <span class="uhp-tag compact">#AI-Powered</span>
              @endif
              @if($product->has_team)
              <span class="uhp-tag compact">#Team</span>
              @endif
            </div>

            <style>
.uhp-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.uhp-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid rgba(255, 255, 255, .10);
    background: #00000066;
    color: #fff;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    transition: color .15s;
}
            </style>
            

            <div class="uhs-row uhs-gap3 uhs-mt4">
              @unless($isService)
                @if($alreadyPurchased)
                  <button class="uhs-accent btn-glow" disabled>Purchased</button>
                  <a class="uhs-ghost uhs-px5" href="{{ route('user.myorders.page') }}">Go to My Orders</a>
                @endif
              @endunless

              @if($isService)
                <button id="btnChat" class="uhs-ghost uhs-px5">
                  <i class="fa-regular fa-message uhs-mr2"></i> Contact Seller
                </button>
              @endif
            </div>
          </div>

          <!-- Sticky Summary -->
          <aside class="uhs-card uhs-p5 uhs-sticky">
            <div class="uhs-between">
              <div class="uhs-display">Package</div>
              <div class="uhs-white80" id="uhsPrice">{{ $targetCurrencySymbol ?? '$' }}0.00</div>
            </div>

            <div class="uhs-tierbar" id="uhsTiers"></div>
            <ul class="uhs-ul uhs-mt3" id="uhsTierFacts"></ul>

            
            @unless($isService)
              <button id="btnBook" class="uhs-accent btn-glow uhs-block uhs-mt4">{{ $isDigitalOrCourse ? 'Buy Now' : 'Book this service' }}</button>
            @else
              <button id="btnBookChat" class="uhs-ghost uhs-block uhs-mt4"><i class="fa-regular fa-message"></i> Chat first</button>
            @endunless
          </aside>
        </div>
      </div>

      <!-- =========== Sections =========== -->
      <div class="uhs-sections">

        <!-- About This Gig -->
        <section class="uhs-card uhs-p5" data-open="true">
          <button class="uhs-sec-head" data-toggle>
            <h2 class="uhs-h2">About This {{ $isService ? 'Service' : 'Gig' }}</h2>
            <span class="uhs-white70"><i class="fa-solid fa-minus"></i></span>
          </button>
          <div class="uhs-collapsing">
            <div class="uhs-prose">{!! $product->description !!}</div>
          </div>
        </section>

        <!-- About the Seller (compact) -->
        <section class="uhs-card uhs-p5">
          <button class="uhs-sec-head" data-toggle>
            <h2 class="uhs-h2">About the Seller</h2>
            <span class="uhs-white70"><i class="fa-solid fa-minus"></i></span>
          </button>
          <div class="uhs-collapsing">
            <div class="uhs-row uhs-gap4 uhs-col-sm">
              <div class="uhs-avatar xl">
                <img src="{{ user_avatar_url($product->user) }}" alt="{{ $sellerName }}" onerror="this.src='https://placehold.co/60x60?text=U'">
              </div>
              <div class="uhs-flex1 uhs-white90">
                <div class="uhs-body">{{ $sellerName }}</div>
                <div class="uhs-white60 uhs-text-xs">
                  Avg response: {{ $avgResponseHuman }} • {{ $sellerOnline ? 'Online now' : 'Usually replies quickly' }}
                </div>
                <a class="uhs-ghost uhs-mt2 uhs-px4 uhs-py15 uhs-text-sm"
                   href="{{ route('user.details', ['id' => $product->user->id]) }}">
                  View Profile
                </a>
              </div>
            </div>
          </div>
        </section>

        <!-- FAQs (from DB) -->
        @php
  $faqGroups = collect($product->faqs ?? [])->groupBy(function($f){
    $h = trim((string)($f->faq_heading ?? ''));
    return $h !== '' ? $h : 'General';
  });
@endphp

@if($faqGroups->isNotEmpty())
<section class="uhs-card uhs-p5">
  <button class="uhs-sec-head" data-toggle type="button" aria-expanded="true">
    <h2 class="uhs-h2" style="margin:0">FAQs</h2>
    <span class="uhs-white70"><i class="fa-solid fa-minus"></i></span>
  </button>

  <div class="uhs-collapsing" id="uhsFaq" style="height: auto !important;">
    @foreach($faqGroups as $heading => $list)
      <h4 class="uhs-h4 uhs-mt4 uhs-faq-group">{{ $heading }}</h4>

      <div class="uhs-faq-group-list">
        @foreach($list as $i => $faq)
          <div class="uhs-faq" data-open="{{ $i===0 ? 'true':'false' }}">
            <button class="uhs-faq-head" data-toggle type="button" aria-expanded="{{ $i===0 ? 'true':'false' }}">
              <div class="uhs-faq-q">
                {{ $faq->question ?: ($faq->faq_heading ?? 'FAQ') }}
              </div>
              <span class="uhs-faq-icon" aria-hidden="true">
                <i class="fa-solid {{ $i===0 ? 'fa-minus' : 'fa-plus' }}"></i>
              </span>
            </button>

            <div class="uhs-collapsing">
              <div class="uhs-faq-body">
                {!! nl2br(e($faq->faq_answer ?? '')) !!}
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endforeach
  </div>
</section>
@endif


        <!-- Reviews -->
        <section class="uhs-card uhs-p5" id="reviews">
          <button class="uhs-sec-head" data-toggle>
            <h2 class="uhs-h2">Reviews</h2>
            <span class="uhs-white70"><i class="fa-solid fa-minus"></i></span>
          </button>
          <div class="uhs-collapsing" id="uhsReviews">
            {{-- Inline flash --}}
            <div id="reviewsFlash" class="d-none"></div>

            {{-- Write a review (if eligible) --}}
            @if($isLogged && $alreadyPurchased && !$alreadyReviewed)
              <form id="reviewForm" class="uhs-mt3" method="POST" action="{{ route('product.review.store', $product->id) }}" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="uhs-row uhs-gap2 uhs-align-center">
                  <div id="starWrap" class="uhs-row uhs-gap1">
                    @for($i=1;$i<=5;$i++)
                    <button type="button" class="star" data-val="{{ $i }}" aria-label="{{ $i }} star">
                      <i class="fa-regular fa-star"></i>
                    </button>
                    @endfor
                  </div>
                  <input type="hidden" name="rating_number" id="ratingSelect" required>
                </div>
                <textarea name="review" class="uhs-input uhs-mt2" rows="3" maxlength="2000" placeholder="Share your experience…"></textarea>

                <div class="uhs-row uhs-gap2 uhs-mt2">
                  <input type="file" name="images[]" id="reviewImages" accept="image/*" multiple hidden>
                  <button type="button" class="uhs-ghost" onclick="document.getElementById('reviewImages').click()">
                    <i class="fa-solid fa-upload"></i> Add images
                  </button>
                  <span id="reviewImagesText" class="uhs-white60 uhs-text-sm">No files selected</span>
                </div>
                <div id="reviewPreview" class="uhs-row uhs-gap2 uhs-mt2 uhs-wrap"></div>

                <button class="uhs-accent btn-glow uhs-mt3" type="submit">Submit review</button>
              </form>
            @endif

            {{-- List existing reviews --}}
            <div class="uhs-rlist uhs-mt4" id="uhsRListExisting">
              @forelse($reviews as $rev)
                @php
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
                <div class="uhs-review uhs-mb3">
                  <div class="uhs-row uhs-gap3">
                    <div class="uhs-avatar"><img src="{{ $dp }}" alt="{{ $full }}" onerror="this.src='https://placehold.co/32x32?text=U'"></div>
                    <div class="uhs-white80 uhs-text-sm">
                      <div class="uhs-body uhs-white">{{ $full }}</div>
                      <div class="uhs-accent-text" aria-hidden="true">{{ str_repeat('★', (int)$rev->rating_number) }}</div>
                    </div>
                    <div class="uhs-white60 uhs-text-xs" style="margin-left:auto">{{ optional($rev->created_at)->diffForHumans() }}</div>
                  </div>
                  @if($rev->review)
                  <p class="uhs-body uhs-white90 uhs-text-sm uhs-mt1">{!! nl2br(e($rev->review)) !!}</p>
                  @endif
                  @if(!empty($imgs))
                    <div class="uhs-rmedia">
                      @foreach($imgs as $img)
                        <a href="{{ $img }}" target="_blank"><img src="{{ $img }}" alt="review image"></a>
                      @endforeach
                    </div>
                  @endif
                </div>
              @empty
                <div class="uhs-white60 uhs-text-sm">No reviews yet.</div>
              @endforelse
            </div>
          </div>
        </section>
      </div>

      <!-- Carousels -->
      <section class="uhs-card uhs-p5" style="margin-top: 20px;">
        <div class="uhs-between uhs-mb3">
          <h3 class="uhs-display">Recommended for you</h3>
          <div class="uhs-white70 uhs-text-sm">Scroll</div>
        </div>
        <div class="uhs-hscroll" id="uhsReco"></div>
      </section>

      <section class="uhs-card uhs-p5" style="margin-top: 20px;">
        <div class="uhs-between uhs-mb3">
          <h3 class="uhs-display">More from this seller</h3>
          <div class="uhs-white70 uhs-text-sm">Scroll</div>
        </div>
        <div class="uhs-hscroll" id="uhsMore"></div>
      </section>
    </div>
  </div>
</section>

@include('common.footer')

<!-- ===== Toast container ===== -->
<div class="uhs-toast-wrap" id="uhsToasts"></div>

@if($isDigitalOrCourse)
<style>
/* ====== Drawer (right) ====== */
.uhs-drawer{position:fixed;right:0;top:0;height:100vh;width:380px;max-width:95vw;background:#0b0b0b;color:#fff;border-left:1px solid rgba(206,255,27,.2);transform:translateX(100%);transition:transform .28s ease;z-index:1051;display:flex;flex-direction:column}
.uhs-drawer.open{transform:translateX(0)}
.uhs-drawer-head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08)}
.uhs-drawer-body{padding:14px 16px;overflow:auto;flex:1}
.uhs-drawer-foot{display:flex;gap:8px;padding:12px 16px;border-top:1px solid rgba(255,255,255,.08)}
.uhs-btn{appearance:none;border:1px solid rgba(255,255,255,.16);background:#111827;color:#fff;padding:10px 14px;border-radius:10px;cursor:pointer}
.uhs-btn[disabled]{opacity:.5;cursor:not-allowed}
.uhs-btn.primary{background:#CEFF1B;border-color:#CEFF1B;color:#0b0b0b;font-weight:700}

/* ====== Modal (center) ====== */
.uhs-mask{position:fixed;inset:0;background:rgba(0,0,0,.55);opacity:0;pointer-events:none;transition:opacity .2s;z-index:1052}
.uhs-mask.open{opacity:1;pointer-events:auto}
.uhs-modal{position:fixed;left:50%;top:50%;transform:translate(-50%,-44%) scale(.98);min-width:500px;max-width:min(560px,92vw);background:#0b0b0b;color:#fff;border:1px solid rgba(206,255,27,.25);border-radius:14px;box-shadow:0 18px 60px rgba(0,0,0,.45);opacity:0;transition:transform .22s,opacity .22s;z-index:1053}
.uhs-mask.open .uhs-modal{opacity:1;transform:translate(-50%,-50%) scale(1)}
.uhs-modal-head,.uhs-modal-foot{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08)}
.uhs-modal-foot{border-top:1px solid rgba(255,255,255,.08);border-bottom:0}
.uhs-modal-body{padding:14px}
</style>

<!-- Drawer -->
<div id="buyDrawer" class="uhs-drawer" aria-hidden="true">
  <div class="uhs-drawer-head">
    <strong>Order options</strong>
    <button class="uhs-btn" id="btnDrawerClose" aria-label="Close"><i class="fa-regular fa-circle-xmark"></i></button>
  </div>
  <div class="uhs-drawer-body">
    <div id="buySidebarContent"><div class="uhs-white60">Select a package to continue.</div></div>
  </div>
  <div class="uhs-drawer-foot">
    <button class="uhs-btn" id="btnDrawerClose2">Cancel</button>
    <button id="btnSidebarContinue" class="uhs-btn primary" disabled>Continue</button>
  </div>
</div>

<!-- Modal -->
<div id="confirmMask" class="uhs-mask" aria-hidden="true">
  <div class="uhs-modal" role="dialog" aria-modal="true">
    <div class="uhs-modal-head">
      <strong>Confirm your order</strong>
      <button class="uhs-btn" id="confirmClose"><i class="fa-regular fa-circle-xmark"></i></button>
    </div>
    <div class="uhs-modal-body">
      <div id="lineItems"></div>
    </div>
    <div class="uhs-modal-foot">
      <button class="uhs-btn" id="confirmCancel">Cancel</button>
      <button id="btnPayWallet" class="uhs-btn primary">Pay from Wallet</button>
    </div>
  </div>
</div>
@endif


<!-- ===== Mini Chat (themed, lightweight) ===== -->
<style>
  /* quick chips (match theme) */
  .quick-chip{
    display:inline-block; white-space:wrap; cursor:pointer;
    padding:6px 10px; border-radius:999px; font-size:12px;
    background:#0f0f10; color:#fff;
    border:1px solid rgba(206,255,27,.25);
    transition:border-color .18s, background .18s;
  }
  .quick-chip:hover{ border-color:#CEFF1B; background:#111827; }
  .icon-btn{
    display:inline-grid; place-items:center; width:38px; height:38px;
    border-radius:10px; border:1px solid rgba(255,255,255,.16);
    background:#111827; color:#fff; text-decoration:none;
  }
  .icon-btn:hover{ border-color:#CEFF1B; }
</style>

<div id="chatBox" class="uhs-card" style="position:fixed;left:16px;bottom:16px;width:360px;max-width:95vw;display:none;z-index:1051;padding:16px;box-shadow:0 10px 24px rgba(0,0,0,.2);border:1px solid rgba(206,255,27,.25);border-radius:14px;background:#0b0b0b;">
  <!-- header -->
  <div class="uhs-between uhs-align-center">
    <div class="uhs-row uhs-gap2 uhs-align-center">
      <div class="uhs-avatar">
        <img src="{{ user_avatar_url($product->user) }}" onerror="this.src='https://placehold.co/32x32?text=U'">
      </div>
      <div>
        <div class="uhs-body">Message {{ $sellerName }}</div>
        <div class="uhs-text-xs uhs-white60">
          <span id="sellerHeaderOnline">{{ $sellerOnline ? 'Online' : 'Offline' }}</span>
          • Avg: <span id="sellerHeaderAvg">{{ $avgResponseHuman }}</span>
        </div>
      </div>
    </div>
    <button class="uhs-ghost" onclick="toggleChat(false)" aria-label="Close mini chat">
      <i class="fa-regular fa-circle-xmark"></i>
    </button>
  </div>

  <!-- messages -->
  <div id="chatBody" class="uhs-mt2"
       style="height:280px;overflow:auto;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:8px"></div>

  <!-- composer -->
<div class="uhs-composer uhs-mt2">
  <input id="chatFile" type="file" hidden onchange="onSelectFile(event)">
  <button class="uhs-ghost" title="Attach file" aria-label="Attach file"
          onclick="document.getElementById('chatFile').click()">
    <i class="fa-solid fa-paperclip"></i>
  </button>

  <input id="chatInput" type="text" class="uhs-input"
         placeholder="Type your message…" oninput="toggleSend()">

  <button id="chatSend" class="btn-glow" disabled>Send</button>

  <a class="icon-btn" title="Open full chat" aria-label="Open full chat"
     href="{{ route('user.messages', ['partner' => $product->user->id]) }}?product={{ $product->id }}&is_service={{ $isService ? 1 : 0 }}">
    <i class="fa fa-external-link-alt"></i>
  </a>
</div>

</div>

<script>
  function chipToInput(el){
    const v = (el?.textContent || '').trim();
    const inp = document.getElementById('chatInput');
    if (!inp) return;
    inp.value = v;
    inp.focus();
    if (typeof toggleSend === 'function') toggleSend();
  }
</script>

<script>
// Builds the helper header + quick chips block
function buildQuickSectionHtml(){
  return `
    <div id="uhsQuickHelp" class="uhs-quick-help">
      <div class="uhs-text-xs uhs-white60" style="margin-bottom:6px;">
        Ask {{ $sellerName }} anything about requirements, scope or price.
      </div>
      <div class="uhs-row uhs-wrap uhs-gap1">
        <span class="quick-chip" onclick="chipToInput(this)">💻 Hey {{ $sellerName }}, I'm looking for website development work for...</span>
        <span class="quick-chip" onclick="chipToInput(this)">Hey {{ $sellerName }}, I'm looking for someone who has experience with platforms like...</span>
        <span class="quick-chip" onclick="chipToInput(this)">Hey {{ $sellerName }}, I've got a website design, can you help me with...</span>
      </div>
    </div>
  `;
}

// Ensure quick section exists at the very top of #chatBody
function ensureQuickSection(){
  const body = document.getElementById('chatBody');
  if (!body) return;
  if (!body.querySelector('#uhsQuickHelp')) {
    body.insertAdjacentHTML('afterbegin', buildQuickSectionHtml());
  }
}
</script>



<script>
// Drawer helpers
const buyDrawer = document.getElementById('buyDrawer');
function drawerOpen(){ buyDrawer?.classList.add('open'); buyDrawer?.setAttribute('aria-hidden','false'); }
function drawerClose(){ buyDrawer?.classList.remove('open'); buyDrawer?.setAttribute('aria-hidden','true'); }
document.getElementById('btnDrawerClose')?.addEventListener('click', drawerClose);
document.getElementById('btnDrawerClose2')?.addEventListener('click', drawerClose);

// Modal helpers
const confirmMask = document.getElementById('confirmMask');
function confirmOpen(){ confirmMask?.classList.add('open'); confirmMask?.setAttribute('aria-hidden','false'); }
function confirmClose(){ confirmMask?.classList.remove('open'); confirmMask?.setAttribute('aria-hidden','true'); }
document.getElementById('confirmClose')?.addEventListener('click', confirmClose);
document.getElementById('confirmCancel')?.addEventListener('click', confirmClose);

// close on ESC
document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape'){ confirmClose(); drawerClose(); }
});
// close modal clicking the dim (not the dialog)
confirmMask?.addEventListener('click', (e)=>{ if(e.target === confirmMask) confirmClose(); });
</script>


<script>
(function ensureAccentVar(){
  const r = document.documentElement;
  const cur = getComputedStyle(r).getPropertyValue("--accent").trim();
  if (!cur) r.style.setProperty("--accent", "#CEFF1B");
})();

const IS_LOGGED_IN = {{ $isLogged ? 'true':'false' }};
const PRODUCT_ID   = {{ (int)$product->id }};
const PRODUCT_NAME = @json($product->name);
const IS_SERVICE   = {{ $isService ? 'true':'false' }};
const IS_DIGITAL_OR_COURSE = {{ $isDigitalOrCourse ? 'true':'false' }};
const SELLER_NAME  = @json($sellerName);
const SELLER_ID    = {{ (int)($product->user->id ?? 0) }};
const CSRF         = @json(csrf_token());
const CUR_SYMBOL   = @json($targetCurrencySymbol ?? '$');

const $  = (s,r=document)=>r.querySelector(s);
const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));

/* ===== Toast ===== */
function toast(msg, ok=true){
  const wrap = $("#uhsToasts"); if (!wrap) return alert(msg);
  const el = document.createElement("div");
  el.className = "uhs-toast " + (ok ? "ok":"err");
  el.innerHTML = `<i class="fa-solid ${ok?'fa-circle-check':'fa-circle-exclamation'}"></i><div class="msg">${msg}</div><button class="x"><i class="fa-regular fa-circle-xmark"></i></button>`;
  wrap.appendChild(el);
  const close = ()=>{ el.remove(); };
  el.querySelector(".x").onclick = close;
  setTimeout(close, 4200);
}

/* ===== Media (images only) ===== */
const MEDIA_IMAGES = @json(array_values($images ?? []));
let imgIndex = 0;
function uhsUpdateStage(){
  const img = $("#uhsStageImg");
  img.src = MEDIA_IMAGES[imgIndex] || 'https://placehold.co/900x540?text=No+Image';
  $$(".uhs-thumb").forEach((t, i) => t.classList.toggle("active", i === imgIndex));
}
function uhsBuildThumbs(){
  const host = $("#uhsThumbs"); if (!host) return;
  host.innerHTML = MEDIA_IMAGES.map((src,i)=>`
    <button class="uhs-thumb ${i===imgIndex?'active':''}" data-i="${i}" type="button" aria-label="Preview ${i+1}">
      <img src="${src}" alt="preview ${i+1}">
    </button>
  `).join("");
  host.onclick = (e)=>{
    const b = e.target.closest(".uhs-thumb"); if(!b) return;
    imgIndex = +b.dataset.i; uhsUpdateStage();
  };
}
$("#uhsPrev")?.addEventListener("click", ()=>{ imgIndex = (imgIndex - 1 + MEDIA_IMAGES.length) % MEDIA_IMAGES.length; uhsUpdateStage(); });
$("#uhsNext")?.addEventListener("click", ()=>{ imgIndex = (imgIndex + 1) % MEDIA_IMAGES.length; uhsUpdateStage(); });

/* ===== Rating ===== */
function renderRating(hostSel, rating, count){
  const host = $(hostSel); if (!host) return;
  const full = Math.floor(rating || 0), half = (rating - full) >= .5;
  host.innerHTML = `<span class="uhs-accent-text">${"★".repeat(full)}${half?"⯪":""}</span>
    <span class="uhs-white90 uhs-text-sm" style="margin-left:6px">{{ number_format((float)($rating??0),1) }} ({{ $reviewsCount }})</span>`;
}

/* ===== Share ===== */
$("#btnShare")?.addEventListener("click", async ()=>{
  try{
    if (navigator.share) {
      await navigator.share({ title: PRODUCT_NAME, url: @json(request()->fullUrl()) });
      toast("Link shared.");
    } else {
      await navigator.clipboard.writeText(@json(request()->fullUrl()));
      toast("Link copied to clipboard.");
    }
  }catch(_){ toast("Could not share.", false); }
});

/* ===== Wishlist ===== */
(function wireWishlist(){
  const btn = $("#btnWishlist"), icon = $("#wishIcon");
  if(!btn || !icon) return;

  btn.addEventListener("click", async ()=>{
    if(!IS_LOGGED_IN){
     goHomeAndOpenLogin(); return;
    }
    try{
      const res = await fetch(@json(route('wishlist.toggle')), {
        method:'POST',
        headers:{
          'X-CSRF-TOKEN':CSRF,
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest'
        },
        body: JSON.stringify({ product_id: PRODUCT_ID })
      });
      const js = await res.json().catch(()=>null);
      if(js && js.ok){
        icon.className = js.wished ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
        toast(js.wished ? "Saved to wishlist." : "Removed from wishlist.");
        // refresh header badges via the function exposed in header
        if (typeof window.refreshWishlistBadge === 'function') {
          window.refreshWishlistBadge();
        }
      } else {
        toast((js && js.message) || "Could not update wishlist.", false);
      }
    }catch(_){
      toast("Network error.", false);
    }
  });
})();
/* ===== Sticky Summary: Tiers (from server) ===== */
const TIERS = @json($tiers ?? []);
let selectedTier = TIERS[0]?.key || null;
function currentTier(){ return TIERS.find(t=>t.key===selectedTier); }
function renderTiers(){
  const host = $("#uhsTiers"); if(!host) return;
  host.innerHTML = TIERS.map(t=>`
    <button class="uhs-tierbtn ${t.key===selectedTier?'active':''}" data-k="${t.key}" type="button">${t.label}</button>
  `).join("");
  host.onclick = (e)=>{
    const b = e.target.closest(".uhs-tierbtn"); if(!b) return;
    selectedTier = b.dataset.k;
    renderTiers(); renderTierFacts(); updatePrice();
  };
}
function renderTierFacts(){
  const t = currentTier(), host = $("#uhsTierFacts"); if(!t || !host) return;
  host.innerHTML = `
    <li><i class="fa-regular fa-clock"></i> ${t.delivery_days}-day delivery</li>
    ${t.details ? `<li class="uhs-white70 uhs-text-sm">${t.details}</li>`:''}
  `;
}
function updatePrice(){
  const t = currentTier(); if (!t) return;
  $("#uhsPrice").textContent = t.price_display || (CUR_SYMBOL + '0.00');
}

/* ===== Buttons (Continue/Book/Chat) ===== */

function openBuySidebar(tierKey){
  if (IS_SERVICE){ toggleChat(true); return; }

  // NOT LOGGED IN → go home and open login modal
  if (!IS_LOGGED_IN){ goHomeAndOpenLogin(); return; }

  selectedTier = tierKey || selectedTier || (TIERS[0]?.key);
  if(!selectedTier){ toast("No package available.", false); return; }

  fetch(@json(route('checkout.quote')), {
    method:'POST',
    headers:{
      'X-CSRF-TOKEN':CSRF,
      'Accept':'application/json',
      'Content-Type':'application/json',
      'X-Requested-With':'XMLHttpRequest'
    },
    body: JSON.stringify({ product_id: PRODUCT_ID, tier: selectedTier })
  })
  .then(async r=>{
    // if backend ever returns 401/419 by mistake, treat as not logged
    if (r.status === 401 || r.status === 419){ goHomeAndOpenLogin(); throw new Error('AUTH'); }
    let d=null; try{ d=await r.json(); }catch{}
    if (!d || d.ok === false){ throw new Error((d && (d.error||d.message)) || 'Could not load quote.'); }
    return d;
  })
  .then(d=>{
    if (d.can_pay === false){
      toast(d.block_reason || 'Please set your country first in Profile.', false);
      return;
    }
    const cur = d.currency_symbol || d.currency || '';
    document.getElementById('buySidebarContent').innerHTML = `
      <div class="uhs-ul">
        <div class="uhs-between"><b>${String(d.tier).toUpperCase()}</b><span>${cur}${Number(d.base).toFixed(2)}</span></div>
        <div class="uhs-white60 uhs-text-sm uhs-mb2">${PRODUCT_NAME}</div>
        <div class="uhs-between"><span>Platform fee (${d.platform_fee_percent}%)</span><span>${cur}${Number(d.platform_fee_amount).toFixed(2)}</span></div>
        <div class="uhs-between"><span>GST (${d.gst_percent}%)</span><span>${cur}${Number(d.gst_amount).toFixed(2)}</span></div>
        <div class="uhs-divider uhs-mt2"></div>
        <div class="uhs-between"><b>Total</b><b>${cur}${Number(d.total).toFixed(2)}</b></div>
      </div>`;
    document.getElementById('btnSidebarContinue').disabled = false;
    drawerOpen();
  })
  .catch(err=>{
    if (String(err.message) !== 'AUTH') toast(err.message || 'Could not load quote.', false);
  });
}




$("#btnContinue")?.addEventListener("click", ()=> openBuySidebar(selectedTier));
$("#btnBook")?.addEventListener("click", ()=> openBuySidebar(selectedTier));
$("#btnBookChat")?.addEventListener("click", ()=> toggleChat(true));


document.getElementById('btnSidebarContinue')?.addEventListener('click', ()=>{
  drawerClose();
  setTimeout(()=>{
    document.getElementById('lineItems').innerHTML =
      document.getElementById('buySidebarContent').innerHTML;
    confirmOpen();
  }, 220);
});



document.getElementById('btnPayWallet')?.addEventListener('click', ()=>{
  if (!selectedTier) return;
  const btn = document.getElementById('btnPayWallet');
  btn.disabled = true; btn.innerText = 'Processing…';
  fetch(@json(route('checkout.wallet')), {
    method:'POST',
    headers:{
      'X-CSRF-TOKEN':CSRF,
      'Content-Type':'application/json',
      'Accept':'application/json',
      'X-Requested-With':'XMLHttpRequest'
    },
    body: JSON.stringify({ product_id: PRODUCT_ID, tier: selectedTier })
  })
  .then(async r=>{
    let data=null; try{ data=await r.json(); }catch{}
    if (!r.ok || !data || !data.ok){
      const msg = (data && (data.message||data.error)) || 'Payment failed.';
      throw new Error(msg);
    }
    if (data.redirect) window.location.href = data.redirect;
    else throw new Error('Payment succeeded, but no redirect.');
  })
  .catch(err=>{
    btn.disabled = false; btn.innerText = 'Pay from Wallet';
    toast(err.message || 'Payment failed.', false);
  });
});



/* ===== Mini Chat (Echo optional) ===== */
let MINI_CONV_ID = 0;
function toggleChat(show){
  const el = $("#chatBox"); if (!el) return;
  el.style.display = show ? 'block' : 'none';
  if (show){
    // show quick chips immediately
    ensureQuickSection();
    // then try to load history
    loadMiniHistory();
  }
}
$("#btnChat")?.addEventListener("click", ()=> toggleChat(true));

function hasEcho(){ return !!(window.Echo && typeof window.Echo.join==='function'); }

function setSellerMeta(online, avg){
  $("#sellerHeaderOnline").textContent = online ? 'Online' : 'Offline';
  $("#sellerHeaderAvg").textContent = avg || '—';
}

async function loadMiniHistory(){
  const body = $("#chatBody");
  // Always reset to quick section first (keeps chips at top)
  if (body) body.innerHTML = buildQuickSectionHtml();

  try{
    const url = new URL(@json(route('chat.history')), window.location.origin);
    url.searchParams.set('partner_id', SELLER_ID);
    url.searchParams.set('product_id', PRODUCT_ID);
    url.searchParams.set('from_service', IS_SERVICE ? 1 : 0);

    const res = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
    const js  = await res.json().catch(()=>null);

    // If backend returns unauth / error, just keep quick section visible
    if (!js || !js.ok) return;

    MINI_CONV_ID = js.conversation_id;
    setSellerMeta(!!(js.partner && js.partner.online), js.partner?.avg_response || '—');

    // Append messages AFTER the quick section
    (js.messages || []).forEach(m => body.appendChild(renderBubble(m)));
    body.scrollTop = body.scrollHeight;

    if (hasEcho()){
      const ch = `presence-chat.conversation.${MINI_CONV_ID}`;
      window.Echo.join(ch)
        .here(updatePresence).joining(updatePresence).leaving(updatePresence)
        .listen('.chat.new', (e)=>{
          body.appendChild(renderBubble({
            id:e.id, sender_id:e.sender_id, body:e.body, file:e.file, status:'delivered'
          }));
          body.scrollTop = body.scrollHeight; delivered(); seenIfFocused();
        });
    }
  }catch(_){
    // keep quick section — nothing else to do
  }
}

function updatePresence(members){
  const online = (members||[]).length > 1;
  setSellerMeta(online, $("#sellerHeaderAvg").textContent || '{{ $avgResponseHuman }}');
}
function delivered(){ if (!MINI_CONV_ID) return; fetch(`/chat/${MINI_CONV_ID}/delivered`, { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF }}); }
function seenIfFocused(){ if (!MINI_CONV_ID) return; if (document.hasFocus()) fetch(`/chat/${MINI_CONV_ID}/seen`, { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF }}); }
window.addEventListener('focus', seenIfFocused);

function renderBubble(m){
  const mine = Number(m.sender_id) === Number(@json(auth()->id() ?? 0));
  const wrap = document.createElement('div');
  wrap.className = 'uhs-msg' + (mine ? ' mine' : '');

  const hasImage = !!(m.file && m.file.url && m.file.is_image);
  const hasText  = !!(m.body && String(m.body).trim().length);

  // Only show a text bubble if there is text AND there is NO image
  if (hasText && !hasImage){
    const box = document.createElement('div');
    box.className = 'bubble';
    box.textContent = m.body;
    wrap.appendChild(box);
  }

  // File block (image or other)
  if (m.file && m.file.url){
    const c = document.createElement('div');
    c.className = 'file';
    if (m.file.is_image){
      c.innerHTML = `<a href="${m.file.url}" target="_blank"><img src="${m.file.url}" alt="" style="max-width:220px;border-radius:8px"></a>`;
    } else {
      c.innerHTML = `<a class="uhs-link" href="${m.file.url}" target="_blank">${m.file.name || 'Download file'}</a>`;
    }
    wrap.appendChild(c);
  }
  return wrap;
}


function toggleSend(){
  const hasText = ($("#chatInput").value || '').trim().length > 0;
  const hasFile = ($("#chatFile").files || []).length > 0;
  $("#chatSend").disabled = !(hasText || hasFile);
}
function onSelectFile(){ toggleSend(); }

$("#chatSend")?.addEventListener("click", async ()=>{
  if (!IS_LOGGED_IN){
    goHomeAndOpenLogin(); // go home and open login modal
    return;
  }
  const text = ($("#chatInput").value || '').trim();
  const file = ($("#chatFile").files || [])[0] || null;

  if (!text && !file){ return; }
  if (text && (/\b[\w\.-]+@[\w\.-]+\.\w{2,}\b/i.test(text) || /\+?\d[\d\-\s()]{7,}\d/.test(text))){ 
    toast("Sharing contact info is not allowed.", false); 
    return; 
  }

  const fd = new FormData();
  fd.append('partner_id', SELLER_ID);
  fd.append('product_id', PRODUCT_ID);
  fd.append('from_service', IS_SERVICE ? 1 : 0);
  if (text) fd.append('body', text);
  if (file) fd.append('file', file);

  try{
    const res = await fetch(@json(route('chat.seed')), { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF }, body: fd });
    const js = await res.json();
    if (!js.ok){ toast(js.message || "Could not send.", false); return; }
    const my = js.message || { body:text, file:null }; my.sender_id = Number(@json(auth()->id() ?? 0));
    $("#chatBody").appendChild(renderBubble(my));
    $("#chatInput").value=''; $("#chatFile").value=''; toggleSend();
    if (!MINI_CONV_ID && js.conversation_id){ MINI_CONV_ID = js.conversation_id; loadMiniHistory(); }
  }catch(_){ toast("Network error.", false); }
});
/* ===== Reviews (star UI + AJAX + partial swap) ===== */
(function wireReviews(){
  const stars = $$("#starWrap .star"); const ratingSel = $("#ratingSelect");
  stars.forEach(btn=>{
    btn.addEventListener("mouseenter", ()=> paint(+btn.dataset.val));
    btn.addEventListener("click", ()=>{ ratingSel.value = btn.dataset.val; paint(+btn.dataset.val); });
  });
  $("#starWrap")?.addEventListener("mouseleave", ()=> paint(parseInt(ratingSel.value||'0',10)));

  function paint(n){
    stars.forEach((b)=>{ const v = +b.dataset.val; const i = b.querySelector('i'); i.className = (v <= n) ? 'fa-solid fa-star' : 'fa-regular fa-star'; });
  }

  const input = $("#reviewImages"), label = $("#reviewImagesText"), preview = $("#reviewPreview");
  if (input){
    input.addEventListener('change', function(){
      const files = Array.from(this.files||[]); const count = files.length;
      if (label) label.textContent = count ? `${count} image${count>1?'s':''} selected` : 'No files selected';
      if (preview){ preview.innerHTML=''; files.slice(0,6).forEach(f=>{ const url=URL.createObjectURL(f); const img=document.createElement('img'); img.src=url; img.style.width='70px'; img.style.height='70px'; img.style.objectFit='contain'; img.style.border='1px solid rgba(255,255,255,.12)'; img.style.borderRadius='8px'; img.onload=()=>URL.revokeObjectURL(url); preview.appendChild(img); }); }
    });
  }

  const form = $("#reviewForm");
  if (form){
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      if (!ratingSel.value){ toast("Please select a star rating.", false); return; }
      const btn = form.querySelector('button[type="submit"]'); const prev = btn?.innerHTML || '';
      if (btn){ btn.disabled = true; btn.innerHTML = 'Submitting…'; }
      try{
        const fd = new FormData(form);
        const res = await fetch(@json(route('product.review.store', $product->id)), { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF }, body: fd });
        const ct = (res.headers.get('content-type')||'').toLowerCase();
        let js = null; if (ct.includes('application/json')){ try{ js = await res.json(); }catch(_){ js=null; } }
        if (res.status === 403){ toast((js && (js.message||js.error)) || 'Not allowed.', false); if(btn){btn.disabled=false;btn.innerHTML=prev;} return; }
        if (res.status === 422){ const msg = (js && js.errors) ? Object.values(js.errors).flat().join(' ') : 'Validation failed.'; toast(msg, false); if(btn){btn.disabled=false;btn.innerHTML=prev;} return; }
        if (!res.ok){ toast((js && (js.message||js.error)) || 'Could not submit review.', false); if(btn){btn.disabled=false;btn.innerHTML=prev;} return; }
        toast('Your review has been submitted.');
        form.reset(); if (preview) preview.innerHTML=''; paint(0);
        // partial swap: refetch same page and replace only #uhsRListExisting
        const html = await (await fetch(window.location.href, { credentials:'same-origin' })).text();
        const doc  = new DOMParser().parseFromString(html, 'text/html');
        const newer = doc.querySelector('#uhsRListExisting');
        if (newer) $('#uhsRListExisting').innerHTML = newer.innerHTML;
        if(btn){btn.disabled=false;btn.innerHTML=prev;}
      }catch(_){ toast('Network error. Try again.', false); if(btn){btn.disabled=false;btn.innerHTML=prev;} }
    });
  }
})();

/* ===== Recommendations ===== */
function renderCard(it){
  return `
    <a class="uhs-card-mini" href="${it.url}">
      <img src="${it.image}" alt="">
      <div class="uhs-body uhs-white90 uhs-text-sm uhs-mt1">${it.name}</div>
      <div class="uhs-accent-text uhs-text-sm">${it.price_display || ''}</div>
    </a>
  `;
}
async function loadReco(){
  try{
    const r = await fetch(@json(route('product.recommended', $product->id)));
    const js = await r.json();
    if (js.ok){ $("#uhsReco").innerHTML = (js.items||[]).map(renderCard).join(""); }
  }catch(_){}
}
async function loadMoreFromSeller(){
  try{
    const r = await fetch(@json(route('product.more_from_seller', $product->id)));
    const js = await r.json();
    if (js.ok){ $("#uhsMore").innerHTML = (js.items||[]).map(renderCard).join(""); }
  }catch(_){}
}

/* ===== Collapsibles ===== */
function wireCollapsibles(root = document){
  root.querySelectorAll("[data-toggle]").forEach((btn)=>{
    if (btn.__bound) return; btn.__bound = true;

    const wrap = btn.closest("[data-open]") || btn.parentElement;
    if (!wrap) return;

    const body =
      wrap.querySelector(":scope > .uhs-collapsing") ||
      wrap.querySelector(".uhs-collapsing");
    if (!body) return;

    // default-open if not specified
    if (!wrap.hasAttribute("data-open")) wrap.setAttribute("data-open","true");

    // Ensure transition is applied only to height
    body.style.overflow = "hidden";
    body.style.willChange = "height";

    const isOpen = () => wrap.getAttribute("data-open") === "true";

    const expand = ()=>{
      // from current (0 or fixed px) → target px, then auto
      const target = body.scrollHeight;
      body.style.height = target + "px";

      const onEnd = (e)=>{
        if (e && e.propertyName !== "height") return;
        body.removeEventListener("transitionend", onEnd);
        // allow natural growth after animation completes
        body.style.height = "auto";
      };
      body.addEventListener("transitionend", onEnd);
    };

    const collapse = ()=>{
      // from auto → fixed px → 0 (to animate)
      if (getComputedStyle(body).height === "auto") {
        body.style.height = body.scrollHeight + "px";
      }
      // force reflow so the next line transitions
      // eslint-disable-next-line no-unused-expressions
      body.offsetHeight;
      body.style.height = "0px";
    };

    const sync = ()=>{
      if (isOpen()) {
        // open state should end at auto (no stuck height)
        body.style.height = "auto";
      } else {
        body.style.height = "0px";
      }
      // ARIA (if present on button)
      const expandedAttr = isOpen() ? "true" : "false";
      btn.setAttribute("aria-expanded", expandedAttr);
      // Icon
      const icon = btn.querySelector("i");
      if (icon) icon.className = `fa-solid fa-${isOpen() ? "minus" : "plus"}`;
    };

    // Initialize after layout
    requestAnimationFrame(sync);

    // Toggle
    btn.addEventListener("click", ()=>{
      const open = isOpen();
      wrap.setAttribute("data-open", open ? "false" : "true");
      // animate appropriately
      if (open) collapse(); else {
        // start from 0 if needed
        const h = getComputedStyle(body).height;
        if (h !== "0px") { body.style.height = "0px"; /* reflow */ body.offsetHeight; }
        expand();
      }
      // sync ARIA + icon
      const icon = btn.querySelector("i");
      if (icon) icon.className = `fa-solid fa-${open ? "plus" : "minus"}`;
      btn.setAttribute("aria-expanded", open ? "false" : "true");
    });

    // Resize → if open, keep auto so it can adapt; if closed, keep 0
    let rid;
    const onResize = ()=>{
      cancelAnimationFrame(rid);
      rid = requestAnimationFrame(()=>{
        if (isOpen()) {
          body.style.height = "auto";
        } else {
          body.style.height = "0px";
        }
      });
    };
    window.addEventListener("resize", onResize, { passive:true });

    // Content changes inside body → if open, briefly set to px, then back to auto to smooth reflow
    const mo = new MutationObserver(()=>{
      if (!isOpen()) return;
      // measure, animate to new height, then set auto
      const currentAuto = getComputedStyle(body).height === "auto";
      if (currentAuto) {
        // lock to px, then back to auto next frame to avoid jump
        const h = body.scrollHeight;
        body.style.height = h + "px";
        requestAnimationFrame(()=>{ body.style.height = "auto"; });
      } else {
        // if mid-animation, let transitionend handler set auto
        expand();
      }
    });
    mo.observe(body, { childList:true, subtree:true, characterData:true });

    // Media loads can change height
    body.querySelectorAll("img, video").forEach(m=>{
      m.addEventListener("load", ()=>{
        if (isOpen()) {
          // same idea: settle to auto
          const h = body.scrollHeight;
          body.style.height = h + "px";
          requestAnimationFrame(()=>{ body.style.height = "auto"; });
        }
      }, { passive:true });
    });
  });
}

/* ===== Init ===== */
(function init(){
  const y = $("#uhsYear"); if (y) y.textContent = new Date().getFullYear();

  // media
  uhsBuildThumbs(); uhsUpdateStage();

  // rating + tiers
  renderRating("#uhsRating", {{ (float)($rating ?? 0) }}, {{ (int)$reviewsCount }});
  renderTiers(); renderTierFacts(); updatePrice();

  // collapsibles
  wireCollapsibles(document);

  // recommendations
  loadReco(); loadMoreFromSeller();
})();
</script>

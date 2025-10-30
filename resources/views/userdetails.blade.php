{{-- resources/views/userdetails.blade.php --}}
@include('common.header')

<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('rebuildfrontend/css/userdetails.css') }}">

@php
    // Derive a display name safely (kept same)
    $fullName   = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
    $display    = $fullName !== '' ? $fullName : ($user->name ?? $user->unique_id ?? 'User #'.$user->id);

    // Avatar (fixed: use $avatarUrl passed from controller)
    $avatar = $avatarUrl ?? null;

    // Bio plain text from detail.profile_description (HTML -> text)
    $raw = (string) ($detail->profile_description ?? '');
    $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $spaced = preg_replace(
        '/\s*<(\/?(p|div|br|li|ul|ol|h[1-6]|tr|td|th))\b[^>]*>\s*/i',
        ' ',
        $decoded
    );
    $bioText = strip_tags($spaced);
    $bioText = trim(preg_replace('/\s+/u', ' ', $bioText));

    // Country name (already passed)
    $countryName = $country->name ?? null;

    // Social icon map (kept same)
    $socialIconMap = [
        'facebook'  => ['fa-brands fa-facebook', 'Facebook'],
        'instagram' => ['fa-brands fa-instagram', 'Instagram'],
        'twitter'   => ['fa-brands fa-x-twitter', 'X (Twitter)'],
        'x'         => ['fa-brands fa-x-twitter', 'X (Twitter)'],
        'linkedin'  => ['fa-brands fa-linkedin', 'LinkedIn'],
        'youtube'   => ['fa-brands fa-youtube', 'YouTube'],
        'github'    => ['fa-brands fa-github', 'GitHub'],
        'behance'   => ['fa-brands fa-behance', 'Behance'],
        'dribbble'  => ['fa-brands fa-dribbble', 'Dribbble'],
        'website'   => ['fa-solid fa-globe',     'Website'],
        'site'      => ['fa-solid fa-globe',     'Website'],
        'portfolio' => ['fa-solid fa-globe',     'Portfolio'],
    ];
@endphp

<!-- =================== ULTRA HUSTLE PROFILE (content only) =================== -->
<main id="uhProfile" class="uh-wrapper">
  <!-- Sticky profile strip -->
  <header class="uh-sticky">
    <div class="uh-sticky-inner">
      <div class="uh-sticky-left">
        <div class="uh-avatar uh-avatar-56" style="background-image: url({{ $avatar }}); background-position: center; background-size: cover;">
        </div>
        <div>
          <div class="uh-row">
            <h1 class="uh-title">{{ $display }}</h1>
            <span class="uh-badge-accent">Premium User</span>
            <span class="uh-badge-verify"><i class="fa-solid fa-shield"></i> Verified</span>
          </div>
          <div class="uh-meta">
            @if($countryName)
              <span><i class="fa-solid fa-location-dot"></i> {{ $countryName }}</span>
            @endif
            @if(!empty($user->created_at))
              <span><i class="fa-regular fa-calendar"></i> Joined: {{ optional($user->created_at)->format('M Y') }}</span>
            @endif
            <span class="uh-hide-md">{{ $bioText }}</span>
          </div>
        </div>
      </div>

      @if(!empty($socials))
      <div class="uh-social uh-hide-sm">
        @foreach($socials as $key => $url)
          @php $meta = $socialIconMap[strtolower($key)] ?? ['fa-solid fa-link', ucfirst($key)]; @endphp
          <a href="{{ $url }}" class="uh-chip" target="_blank" rel="noopener noreferrer" title="{{ $meta[1] }}">
            <i class="{{ $meta[0] }}"></i>
            <span>{{ $meta[1] }}</span>
          </a>
        @endforeach
      </div>
      @endif

      <div class="uh-toggle">
        <button class="uh-pill uh-pill-on" data-role="creator">Creator</button>
        <button class="uh-pill" data-role="client">Client</button>
      </div>
    </div>
  </header>

  <div class="uh-container">
    <!-- Top Profile Strip -->
    <section class="uh-grid-12 uh-gap-3 uh-mb-6">
      <!-- left -->
      <div class="uh-col-3">
        <div class="uh-card uh-flex uh-gap-4 uh-p-4 uh-center-v">
          <div class="uh-avatar uh-avatar-72" style="background-image: url({{ $avatar }}); background-position: center; background-size: cover;"></div>
          <div>
            <div class="uh-name">{{ $display }}</div>
            <div class="uh-badge-accent uh-inline">Premium User</div>
          </div>
        </div>
      </div>

      <!-- right -->
      <div class="uh-col-9">
        <div class="uh-card uh-grid-2 uh-gap-3 uh-p-4">
          <!-- <div class="uh-field">
            <div class="uh-label">Preferred Stack</div>
            <div class="uh-value">
              {{-- Not in DB: add columns later (e.g., user_meta.preferred_stack) --}}
              {{ $profileMeta['preferred_stack'] ?? '—' }}
            </div>
          </div>
          <div class="uh-field">
            <div class="uh-label">Languages</div>
            <div class="uh-value">
              {{-- Not in DB: add columns later (e.g., user_meta.languages) --}}
              {{ $profileMeta['languages'] ?? '—' }}
            </div>
          </div>
          <div class="uh-field">
            <div class="uh-label">Work Style</div>
            <div class="uh-value">
              {{-- Not in DB: add columns later (e.g., user_meta.work_style) --}}
              {{ $profileMeta['work_style'] ?? '—' }}
            </div>
          </div>
          <div class="uh-field">
            <div class="uh-label">Keywords</div>
            <div class="uh-value">
              {{-- Not in DB: add columns later (e.g., user_meta.keywords) --}}
              {{ $profileMeta['keywords'] ?? '—' }}
            </div>
          </div> -->
          <div class="uh-field">
            <div class="uh-label">Location</div>
            <div class="uh-value">{{ $profileMeta['location_text'] ?? '—' }}</div>
          </div>

          <!-- Availability -->
          <div class="uh-box">
            <div class="uh-label">Availability</div>
            <div class="uh-row uh-gap-2 uh-mt-1">
              <span class="uh-dot {{ $profileMeta['availability'] ? 'uh-dot-on' : '' }}"></span>
              <span class="uh-pill-mini {{ $profileMeta['availability'] ? 'uh-pill-mini-on' : '' }}">
                Available for Collaboration
              </span>
            </div>
          </div>

          <!-- Badges -->
          <div class="uh-box">
            <div class="uh-label">Badges</div>
            <div class="uh-tagrow">
              {{-- Not in DB: render dynamic badges when you add a table like user_badges --}}
              @forelse(($profileMeta['badges'] ?? []) as $badge)
                <span class="uh-tag">{{ $badge }}</span>
              @empty
                <span class="uh-tag">Top Collaborator</span> {{-- placeholder --}}
              @endforelse
            </div>
          </div>

          <!-- Tags -->
          <div class="uh-box">
            <div class="uh-label">Tags</div>
            <div class="uh-tagrow">
              {{-- Not in DB: render dynamic tags when you add a table like user_tags --}}
              @forelse(($profileMeta['tags'] ?? []) as $tag)
                <span class="uh-tag">{{ $tag }}</span>
              @empty
                <span class="uh-tag">#CreatorOps</span>
                <span class="uh-tag">#Systems</span>
                <span class="uh-tag">#Templates</span>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="uh-grid-4 uh-gap-3">
      <!-- Creator stats (default visible) -->
      <div class="uh-stat uh-creator-only">
        <div class="uh-stat-label">Listings</div>
        <div class="uh-stat-value">{{ $listingsCount }}</div>
      </div>
      <div class="uh-stat uh-creator-only">
        <div class="uh-stat-label">Projects Completed</div>
        <div class="uh-stat-value">{{ $completedProjects }}</div>
      </div>
      <div class="uh-stat uh-creator-only">
        <div class="uh-stat-label">Avg Rating</div>
        <div class="uh-stat-value">
          {{ $avgRating ? number_format($avgRating, 1) : '—' }} @if($avgRating) ★ @endif
        </div>
      </div>
      <div class="uh-stat uh-creator-only">
        <div class="uh-stat-label">XP Level</div>
        <div class="uh-stat-value">{{ $xpLevel }}</div>
        {{-- Derived; if you add a "xp_level" column, render that here --}}
      </div>

      <!-- Client stats -->
      <div class="uh-stat uh-client-only">
        <div class="uh-stat-label">Orders Placed</div>
        <div class="uh-stat-value">{{ $ordersPlaced }}</div>
      </div>
      <div class="uh-stat uh-client-only">
        <div class="uh-stat-label">Avg Response</div>
        <div class="uh-stat-value">{{ $avgResponseHuman }}</div>
      </div>
      <div class="uh-stat uh-client-only">
        <div class="uh-stat-label">Reviews Given</div>
        <div class="uh-stat-value">{{ $reviewsGiven }}</div>
      </div>
      <div class="uh-stat uh-client-only">
        <div class="uh-stat-label">Verified Buyer</div>
        <div class="uh-stat-value">{{ $verifiedBuyer ? 'Yes' : '--' }}</div>
      </div>
    </section>

    <!-- Listings (Creator only) -->
    <section class="uh-mt-6 uh-creator-only">
      <div class="uh-card uh-p-5">
        <button class="uh-card-toggle" data-card>
          <h2 class="uh-card-title">My listings</h2>
          <span class="uh-card-icon">−</span>
        </button>
        <div class="uh-card-body">
          <div class="uh-tabs">
            <button class="uh-tab uh-tab-on" data-tab="All">All</button>
            <button class="uh-tab" data-tab="Services">Services</button>
            <button class="uh-tab" data-tab="Products">Products</button>
            <button class="uh-tab" data-tab="Courses">Courses</button>
            <button class="uh-tab" data-tab="Webinars">Webinars</button>
            <button class="uh-tab" data-tab="Teams">Teams</button>
          </div>

          <div class="uh-grid-3 uh-gap-3" id="uhListings">
            @forelse($products as $p)
              <article class="uh-listing" data-cat="{{ $p->cat }}">
                <img class="uh-listing-img" src="{{ $p->img ?? 'https://picsum.photos/seed/'.$p->id.'/900/600' }}" alt="">
                <div class="uh-listing-body">
                  <div class="uh-flex-split">
                    <div>
                      <div class="uh-listing-title">{{ $p->title }}</div>
                      <div class="uh-listing-sub">{{ $p->cat }}</div>
                    </div>
                    <span class="uh-badge-accent">XP {{ max(1, (int)ceil(($p->sold + 1)/100)) }}</span>
                  </div>
                  <div class="uh-flex-split uh-mt-2">
                    <div class="uh-stars">
                      {{-- No per-product rating aggregate in page scope; add if needed --}}
                      ★★★★☆ <span class="uh-dim">· {{ $p->sold }} views</span>
                      {{-- "sold" not present in schema; using views as placeholder --}}
                    </div>
                    <div class="uh-price">
                     @if(!is_null($p->price_n))
    {{ $viewerCurrencySymbol }} {{ number_format($p->price_n, 2) }}
  @else
                        {{-- Price not retrievable: add correct column in product_pricings and wire here --}}
                        —
                      @endif
                    </div>
                  </div>
                  <a href="{{ route('product.details', ['id' => $p->id]) }}">
                    <button class="uh-btn ghost uh-w-100">View Listing</button>
                  </a>
                </div>
              </article>
            @empty
              <div class="uh-dim">No listings yet.</div>
            @endforelse
          </div>
        </div>
      </div>
    </section>

    <!-- Reviews -->
    <section class="uh-mt-6">
      <div class="uh-card uh-p-5">
        <button class="uh-card-toggle" data-card>
          <h2 class="uh-card-title">Reviews</h2>
          <span class="uh-card-icon">−</span>
        </button>

        <!-- Creator view -->
        <div class="uh-card-body uh-grid-2 uh-gap-6 uh-creator-only">
          <div class="uh-ratingbox">
            <div class="uh-rating-num">{{ $avgRating ? number_format($avgRating, 1) : '—' }}</div>
            <div class="uh-dim">Average rating</div>
            <div class="uh-bars uh-mt-3">
              {{-- If you later store per-star histograms, render widths/counts here --}}
              <div class="uh-bar"><span>5★</span><div class="uh-track"><div class="uh-fill" style="width:60%"></div></div><span class="uh-count">—</span></div>
              <div class="uh-bar"><span>4★</span><div class="uh-track"><div class="uh-fill" style="width:25%"></div></div><span class="uh-count">—</span></div>
              <div class="uh-bar"><span>3★</span><div class="uh-track"><div class="uh-fill" style="width:8%"></div></div><span class="uh-count">—</span></div>
              <div class="uh-bar"><span>2★</span><div class="uh-track"><div class="uh-fill" style="width:5%"></div></div><span class="uh-count">—</span></div>
              <div class="uh-bar"><span>1★</span><div class="uh-track"><div class="uh-fill" style="width:2%"></div></div><span class="uh-count">—</span></div>
            </div>
          </div>

          <div class="uh-reviews uh-stack-3">
  @forelse($creatorReviews as $r)
    <article class="uh-review">
      <div class="uh-review-head">
        <div class="uh-avatar" style="background-image:url('{{ $r->reviewer_avatar }}'); background-size:cover; background-position:center;">
        </div>
        <div class="uh-reviewer">{{ $r->reviewer_name }}</div>
        <div class="uh-stars ml-auto">
          @for($i=1; $i<=5; $i++){!! $i <= (int)$r->rating_number ? '★' : '☆' !!}@endfor
          <span class="uh-dim">({{ $r->rating_number }})</span>
        </div>
      </div>
      <div class="uh-review-sub">review on <a href="{{ route('product.details', ['id' => $r->product_id]) }}">{{ $r->product_name ? Str::limit($r->product_name, 40) : ('Product #'.$r->product_id) }}</a></div>
      <p class="uh-review-text">{{ $r->review }}</p>
    </article>
  @empty
    <div class="uh-dim">No reviews yet.</div>
  @endforelse
</div>
        </div>

        <!-- Client view -->
        <div class="uh-card-body uh-stack-3 uh-client-only">
  @forelse($clientReviews as $r)
    <article class="uh-review">
      <div class="uh-review-head">
        <div class="uh-avatar" style="background-image:url('{{ $r->reviewer_avatar }}'); background-size:cover; background-position:center;">
        </div>
        <div class="uh-reviewer">{{ $r->reviewer_name }}</div>
        <div class="uh-stars ml-auto">
          @for($i=1; $i<=5; $i++){!! $i <= (int)$r->rating_number ? '★' : '☆' !!}@endfor
         <span class="uh-dim">({{ $r->rating_number }})</span>
        </div>
      </div>
      <div class="uh-review-sub">review on <a href="{{ route('product.details', ['id' => $r->product_id]) }}">{{ $r->product_name ? Str::limit($r->product_name, 40) : ('Product #'.$r->product_id) }}</a></div>
      <p class="uh-review-text">{{ $r->review }}</p>
      <div class="uh-dim">{{ optional($r->created_at)->format('M Y') }}</div>
    </article>
  @empty
    <div class="uh-dim">You haven’t written any reviews yet.</div>
  @endforelse
</div>

    </section>

    <!-- Teams -->
    <section class="uh-mt-6">
      <div class="uh-card uh-p-5">
        <button class="uh-card-toggle" data-card>
          <h2 class="uh-card-title">Teams</h2>
          <span class="uh-card-icon">−</span>
        </button>
        <div class="uh-card-body">
          <div class="uh-grid-3 uh-gap-3">
            @forelse($teams as $t)
              <div class="uh-box">
                <div class="uh-name">{{ $t->name }}</div>
                <div class="uh-dim uh-mt-1">Members: {{ $t->members }} • Role: {{ $t->role }}</div>
                <div class="uh-badge-accent uh-inline uh-mt-2"><i class="fa-solid fa-shield"></i> Open to Collab</div>
                <button
  class="uh-btn ghost uh-w-100 uh-mt-3"
  onclick="window.location.href='{{ $t->url }}'">
  View Team
</button>
              </div>
            @empty
              <div class="uh-dim">You’re not part of any team yet.</div>
            @endforelse
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Sticky CTA footer -->
  <!-- <div class="uh-cta">
    <div class="uh-card uh-cta-inner">
      <div class="uh-hide-sm uh-dim">Available for: Full-time • Part-time • Gigs</div>
      <div class="uh-actions">
        <button class="uh-btn ghost"><i class="fa-regular fa-message"></i> Message</button>
        <button class="uh-btn ghost"><i class="fa-solid fa-paper-plane"></i> Request Offer</button>
        <button class="uh-btn primary"><i class="fa-solid fa-handshake"></i> Invite to Team</button>
      </div>
    </div>
  </div>

  
  <div class="uh-mobilebar">
    <button class="uh-btn ghost"><i class="fa-regular fa-message"></i> Message</button>
    <button class="uh-btn primary"><i class="fa-solid fa-handshake"></i> View Listings</button>
  </div> -->
</main>


<script>
(function UHProfile(){
  const root = document.getElementById('uhProfile');
  if(!root) return;

  // --- Role toggles
  const btnCreator = root.querySelector('.uh-toggle [data-role="creator"]');
  const btnClient  = root.querySelector('.uh-toggle [data-role="client"]');
  function setRole(role){
    const isClient = role === 'client';
    root.classList.toggle('role-client', isClient);
    btnCreator.classList.toggle('uh-pill-on', !isClient);
    btnClient.classList.toggle('uh-pill-on',  isClient);
  }
  btnCreator?.addEventListener('click', ()=> setRole('creator'));
  btnClient?.addEventListener('click',  ()=> setRole('client'));
  setRole('creator'); // default like React

  // --- Card collapse
  root.querySelectorAll('[data-card]').forEach(btn=>{
    const body = btn.parentElement.querySelector('.uh-card-body');
    let open = true;
    btn.addEventListener('click', ()=>{
      open = !open;
      btn.querySelector('.uh-card-icon').textContent = open ? '−' : '+';
      body.style.height = open ? 'auto' : '0';
      body.style.overflow = open ? 'hidden visible' : 'hidden';
      body.style.display = open ? '' : 'none';
    });
  });

  // --- Listings filter (no JS data load; filters existing DOM)
  const TABS_KEY = 'uh.profile.tab';
  const tabs = root.querySelectorAll('.uh-tab');
  const cards = root.querySelectorAll('#uhListings .uh-listing');
  function applyTab(tab){
    tabs.forEach(t=>t.classList.toggle('uh-tab-on', t.dataset.tab===tab));
    cards.forEach(c=>{
      const cat = c.getAttribute('data-cat') || 'All';
      c.style.display = (tab==='All' || tab===cat) ? '' : 'none';
    });
    try { localStorage.setItem(TABS_KEY, tab); } catch {}
  }
  tabs.forEach(t=> t.addEventListener('click', ()=> applyTab(t.dataset.tab)));
  let saved = 'All';
  try { saved = localStorage.getItem(TABS_KEY) || 'All'; } catch {}
  if (root.querySelector(`.uh-tab[data-tab="${saved}"]`)) applyTab(saved); else applyTab('All');

  // --- Animate rating fills (progress bars)
  root.querySelectorAll('.uh-fill').forEach(bar=>{
    const w = bar.style.width || '0%';
    bar.style.width = '0%';
    requestAnimationFrame(()=>{ bar.style.transition='width .6s ease'; bar.style.width = w; });
  });
})();
</script>

@include('common.footer')

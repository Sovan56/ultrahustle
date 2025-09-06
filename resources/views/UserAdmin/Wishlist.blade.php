{{-- resources/views/UserAdmin/Wishlist.blade.php --}}
@include('UserAdmin.common.header')
@section('title','Wishlist')
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

<style>
  /* ===== Responsive thumb height presets ===== */
  :root {
    --wl-thumb-h-xs: 150px; /* very small phones */
    --wl-thumb-h-sm: 170px; /* phones */
    --wl-thumb-h-md: 200px; /* tablets / default */
    --wl-thumb-h-lg: 220px; /* desktops+ */
  }

  /* ===== Wishlist cards (Flipkart-like) ===== */
  .wl-grid .card {
    transition: box-shadow .2s ease, transform .2s ease;
    height: 100%;
  }
  .wl-grid .card:hover {
    box-shadow: 0 10px 24px rgba(0,0,0,.08);
    transform: translateY(-1px);
  }

  .wl-thumb {
    height: var(--wl-thumb-h-md);
    background: #fff;
    display: block;
    overflow: hidden;
    border-bottom: 1px solid rgba(0,0,0,.05);
  }
  .wl-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* keep aspect ratio, consistent height across cards */
    background: #fff;
  }

  .wl-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;       /* 2 lines max */
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 44px;
    margin-bottom: .35rem;
    line-height: 1.2;
  }

  .wl-meta {
    font-size: .875rem;
  }
  .wl-meta .rating-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 4px;
    background: #fef3c7; /* light amber */
    color: #92400e;
    font-weight: 600;
  }

  .wl-price {
    font-weight: 700;
    font-size: 1rem;
  }
  .wl-price small {
    color: #6c757d;
    font-weight: 400;
  }

  .wl-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    white-space: nowrap;
  }

  /* Empty state */
  .wl-empty .icon {
    font-size: 40px;
    line-height: 1;
    display: inline-block;
    margin-bottom: .5rem;
  }

  /* ===== Breakpoints ===== */
  @media (max-width: 575.98px) {           /* xs: small phones */
    .wl-thumb { height: var(--wl-thumb-h-xs); }
    .wl-title { -webkit-line-clamp: 2; font-size: .92rem; }
    .wl-meta  { font-size: .84rem; }
    .wl-price { font-size: .98rem; }
    .wl-actions .btn { font-size: .85rem; padding: .35rem .5rem; }
  }
  @media (min-width: 576px) and (max-width: 767.98px) { /* sm: phones */
    .wl-thumb { height: var(--wl-thumb-h-sm); }
  }
  @media (min-width: 1200px) {             /* xl+ desktops */
    .wl-thumb { height: var(--wl-thumb-h-lg); }
  }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
      <h1 class="m-0">My Wishlist</h1>
      @if(!$items->isEmpty())
        <div class="text-muted small">{{ $items->count() }} item{{ $items->count() > 1 ? 's' : '' }}</div>
      @endif
    </div>

    <div class="section-body">
      @if($items->isEmpty())
        <div class="card wl-empty">
          <div class="card-body text-center py-5">
            <div class="icon text-danger"><i class="far fa-heart"></i></div>
            <div class="h6 mb-2">Your wishlist is empty</div>
            <a href="{{ route('marketplace') }}" class="btn btn-primary">
              <i class="fas fa-compass mr-1"></i> Explore products
            </a>
          </div>
        </div>
      @else
        <div id="wlGrid" class="row wl-grid">
          @foreach($items as $it)
            {{-- On very small screens: 2-up; sm: 2-up; md: 3-up; lg: 4-up --}}
            <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-4 wishlist-card" data-product-id="{{ $it['product_id'] }}">
              <div class="card h-100">
                <a href="{{ route('product.details', $it['product_id']) }}" class="wl-thumb">
                  <img src="{{ $it['image'] }}"
                       alt="{{ $it['name'] }}"
                       onerror="this.src='https://placehold.co/300x200?text=Image'">
                </a>

                <div class="card-body d-flex flex-column">
                  <h6 class="wl-title">
                    <a class="text-dark" href="{{ route('product.details', $it['product_id']) }}">{{ $it['name'] }}</a>
                  </h6>

                  <div class="wl-meta mb-2">
                    <span class="rating-badge">
                      <i class="fas fa-star" style="color: #92400e !important;"></i> {{ $it['rating'] }}
                    </span>
                    <span class="text-muted ml-2">({{ $it['reviews'] }})</span>
                  </div>

                  <div class="mb-3">
                    @if(!is_null($it['price_from']))
                      <div class="wl-price">
                        {{ $it['symbol'] }}{{ number_format($it['price_from'], 2) }}
                        <small class="ml-1">Starting at</small>
                      </div>
                    @else
                      <div class="text-muted small">Price not available</div>
                    @endif
                  </div>

                  <div class="mt-auto d-flex wl-actions">
                    <a href="{{ route('product.details', $it['product_id']) }}" class="btn btn-primary btn-sm flex-grow-1 mr-2">
                      View
                    </a>
                    <button class="btn btn-outline-danger btn-sm"
                            onclick="toggleWishlistPage({{ $it['product_id'] }}, this)"
                            aria-label="Remove from wishlist">
                      <i class="fas fa-heart"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>
</div>

@include('UserAdmin.common.footer')

<script>
async function toggleWishlistPage(productId, btn){
  try {
    const res = await fetch(@json(route('wishlist.toggle')), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type' : 'application/json'
      },
      body: JSON.stringify({ product_id: productId })
    });
    const js = await res.json().catch(()=> ({}));

    if (!js || !js.ok) {
      Swal.fire({ icon:'error', title:'Error', text: (js && js.message) || 'Could not update wishlist.' });
      return;
    }

    // Remove the card smoothly
    const cardCol = btn.closest('.wishlist-card');
    if (cardCol) {
      cardCol.style.transition = 'opacity .18s ease, transform .18s ease';
      cardCol.style.opacity = '0';
      cardCol.style.transform = 'scale(.98)';
      setTimeout(() => {
        cardCol.remove();

        // If grid is now empty, show empty state without full reload
        const grid = document.getElementById('wlGrid');
        if (!document.querySelector('.wishlist-card')) {
          if (grid) {
            grid.outerHTML = `
              <div class="card wl-empty">
                <div class="card-body text-center py-5">
                  <div class="icon text-danger"><i class="far fa-heart"></i></div>
                  <div class="h6 mb-2">Your wishlist is empty</div>
                  <a href="{{ route('marketplace') }}" class="btn btn-primary">
                    <i class="fas fa-compass mr-1"></i> Explore products
                  </a>
                </div>
              </div>`;
          }
        }
      }, 180);
    }

    // Update header wishlist badge count
    try {
      const cRes = await fetch(@json(route('wishlist.count')));
      const cJs  = await cRes.json();
      const badge = document.getElementById('wishlistCountBadge') ||
                    document.querySelector('.nav-wishlist .count-box');
      if (badge && cJs && typeof cJs.count === 'number') {
        badge.textContent = cJs.count;
      }
    } catch(_){}

    // Toast (top-right)
    Swal.fire({
      toast: true, position: 'top-end', timer: 1200, showConfirmButton: false,
      icon: 'success', title: 'Removed from wishlist'
    });

  } catch (e) {
    Swal.fire({ icon:'error', title:'Network error' });
  }
}
</script>

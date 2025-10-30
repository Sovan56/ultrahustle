<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBoost;
use App\Models\ProductPricing;
use App\Models\ProductType;
use App\Models\ProductSubcategory;
use App\Models\UserAdminAnotherDetail;
use App\Models\Country;
use App\Models\Faq;
use App\Services\Currency\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
   public function welcome()
{
    $viewer = auth()->user();
    if ($viewer) {
        $country = $viewer->country_id ? Country::find($viewer->country_id) : null;
        $targetCurrencyCode   = $country?->currency ?? 'USD';
        $targetCurrencySymbol = $country?->currency_symbol ?? '$';
    } else {
        $targetCurrencyCode   = 'USD';
        $targetCurrencySymbol = '$';
    }

    $now = now();
    $types = ProductType::where('is_active', 1)->orderBy('name')->get(['id','name']);      
    
    $faqs = Faq::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id',
                'quote',
                'author_name',
                'author_role',
                'author_location',
            ]);

    // Gather ALL active boosts, newest first, one per product (latest wins)
    $boostRows = ProductBoost::query()
        ->where('is_active', 1)
        ->where('start_at', '<=', $now)
        ->where('end_at', '>=', $now)
        ->orderByDesc('id')
        ->get(['id','product_id']);

    $seen = [];
    $boostedProductIds = [];
    foreach ($boostRows as $r) {
        if (!isset($seen[$r->product_id])) {
            $seen[$r->product_id] = true;
            $boostedProductIds[]  = (int)$r->product_id;
        }
    }

    if (empty($boostedProductIds)) {
        return view('welcome', [
            'boostedCards'         => collect(),
            'boostedCount'         => 0,
            'targetCurrencyCode'   => $targetCurrencyCode,
            'targetCurrencySymbol' => $targetCurrencySymbol,
            'types'                 => $types,
            'faqs'                  => $faqs,
        ]);
    }

    $products = Product::query()
        ->whereIn('id', $boostedProductIds)
        ->where('status', 'published')
        ->with([
            'user:id,first_name,last_name,unique_id',
            'pricings.country:id,currency,currency_symbol',
            'type:id,name',
        ])
        ->withCount('reviews')
        ->withAvg('reviews as reviews_avg', 'rating_number')
        ->orderByRaw('FIELD(id,'.implode(',', $boostedProductIds).')')
        ->get();

    [$cards, $count] = $this->buildCardsForProducts($products, $targetCurrencyCode, $targetCurrencySymbol);

    return view('welcome', [
        'boostedCards'         => $cards,
        'boostedCount'         => $count,
        'targetCurrencyCode'   => $targetCurrencyCode,
        'targetCurrencySymbol' => $targetCurrencySymbol,
        'types'                 => $types,
        'faqs'                  => $faqs,
    ]);
}

private function buildCardsForProducts($products, string $targetCurrencyCode, string $targetCurrencySymbol): array
{
    $fx = new CurrencyConverter();

    $isValid = fn(ProductPricing $pp) =>
        (is_numeric($pp->price) && (float)$pp->price > 0) &&
        (is_numeric($pp->delivery_days) && (int)$pp->delivery_days > 0);

    $cards = $products->map(function (Product $p) use ($fx, $targetCurrencyCode, $targetCurrencySymbol, $isValid) {
        $picked = $p->pricings->where('tier','basic')->filter($isValid)
            ->sortBy(fn(ProductPricing $pp) => $pp->country_id === $p->country_id ? 0 : 1)
            ->first();

        $priceText = null;
        $priceNum  = null;
        if ($picked) {
            $from = $picked->country?->currency;
            $amt  = (float)$picked->price;
            if ($from && $from !== $targetCurrencyCode) {
                $amt = $fx->convert($amt, $from, $targetCurrencyCode);
            }
            $priceNum  = round($amt, 2);
            $priceText = $targetCurrencySymbol . number_format($priceNum, 2);
        }

        // Cover
        $cover = $p->images[0] ?? null;
        if ($cover && !Str::startsWith($cover, ['http://','https://','/media/','/storage/'])) {
            $cover = route('media.pass', ['path' => ltrim($cover, '/')]);
        }
        if (!$cover) $cover = asset('images/slider/baby-slide1.jpg');

        // Seller + avatar
        $sellerName = trim(($p->user->first_name ?? '').' '.($p->user->last_name ?? ''));
        $avatar = null;
        if ($p->user?->unique_id) {
            $rec = UserAdminAnotherDetail::where('user_admin_id', $p->user->unique_id)->first();
            $avatar = $rec?->profile_picture;
        }
        if (!$avatar && $p->user?->id) {
            $rec = UserAdminAnotherDetail::where('user_admin_id', (string)$p->user->id)->first();
            $avatar = $rec?->profile_picture;
        }
        if ($avatar && !Str::startsWith($avatar, ['http://','https://','/media/','/storage/'])) {
            $avatar = route('media.pass', ['path' => ltrim($avatar, '/')]);
        }
        if (!$avatar) $avatar = 'https://placehold.co/40x40.png?text=Img';

        // Wishlist count
        $wishlistCount = DB::table('wishlists')->where('product_id', $p->id)->count();

        // Reviews: prefer eager-loaded aggregates, else cheap fallbacks
        // (withAvg('reviews as reviews_avg','rating') + withCount('reviews as reviews_count'))
        $reviewsCount = $p->reviews_count
            ?? ($p->relationLoaded('reviews') ? $p->reviews->count() : $p->reviews()->count());

        $ratingAvg = $p->reviews_avg
            ?? ($p->relationLoaded('reviews') ? (float)$p->reviews->avg('rating_number') : (float)$p->reviews()->avg('rating_number'));

        // Plain-text description
        $desc = trim(preg_replace('/\s+/', ' ', strip_tags((string)$p->description)));

        return [
            'id'       => $p->id,
            'name'     => $p->name,
            'cover'    => $cover,
            'seller'   => $sellerName,
            'avatar'   => $avatar,
            'price'    => $priceText,
            'price_n'  => $priceNum,
            'rating'   => number_format((float)$ratingAvg, 1),     // ✅ now populated
            'reviews'  => (int)$reviewsCount,                      // ✅ now populated
            'url'      => route('product.details', ['id' => $p->id]),
            'desc'     => $desc,
            'wishlist_count' => $wishlistCount,
        ];
    })->values();

    return [$cards, $cards->count()];
}


    public function marketplace(Request $request){

    $viewer = auth()->user() ?? \App\Models\User::find(session('user_id'));
$viewerName = $viewer ? trim(($viewer->first_name ?? '').' '.($viewer->last_name ?? '')) : null;

    if ($viewer) {
        $country = $viewer->country_id ? Country::find($viewer->country_id) : null;
        $targetCurrencyCode   = $country?->currency ?? 'USD';
        $targetCurrencySymbol = $country?->currency_symbol ?? '$';
    } else {
        $targetCurrencyCode   = 'USD';
        $targetCurrencySymbol = '$';
    }

    $types = ProductType::where('is_active', 1)->orderBy('name')->get(['id','name']);
    $subs  = ProductSubcategory::where('is_active', 1)->orderBy('name')->get(['id','name','product_type_id']);

    // Initial boosted (filtered like the grid)
    [$boostedCards, $boostedCount] = $this->getFilteredBoostedCards($request, $targetCurrencyCode, $targetCurrencySymbol);

    // Initial grid page
    [$cards, $hasMore, $nextPage] = $this->queryMarketplaceCards($request, $targetCurrencyCode, $targetCurrencySymbol, 1);

  
    return view('marketplace', [
        'boostedCards'          => $boostedCards,
        'boostedCount'          => $boostedCount,
        'types'                 => $types,
        'viewerName'            => $viewerName,
        'subs'                  => $subs,
        'targetCurrencyCode'    => $targetCurrencyCode,
        'targetCurrencySymbol'  => $targetCurrencySymbol,
        'initialCards'          => $cards,
        'hasMore'               => $hasMore,
        'nextPage'              => $nextPage,
    ]);
}


private function queryMarketplaceCards(Request $request, string $targetCurrencyCode, string $targetCurrencySymbol, int $page = 1): array
{
    $fx = new CurrencyConverter();

    $perPage = max(1, (int) $request->integer('per_page', 24));

    $typeId  = $request->integer('type_id');

    // Multi-sub (keeps legacy sub_id)
    $subIds = $request->input('sub_ids', []);
    if (!is_array($subIds)) {
        $subIds = strlen((string)$subIds) ? explode(',', (string)$subIds) : [];
    }
    $subIds = array_values(array_unique(array_filter(array_map('intval', $subIds))));
    $legacySub = $request->integer('sub_id');
    if ($legacySub && !in_array($legacySub, $subIds, true)) $subIds[] = $legacySub;

    $usesAi  = $request->boolean('uses_ai', false);
    $hasTeam = $request->boolean('has_team', false);

    $priceMin = $request->filled('price_min') ? (float)$request->input('price_min') : null;
    $priceMax = $request->filled('price_max') ? (float)$request->input('price_max') : null;

    $sort = $request->string('sort', 'relevant')->toString();

    // ---- Subqueries for BASIC price and wishlist counts (fast!)
    $basicPriceSub = DB::table('product_pricings as pp')
        ->selectRaw('pp.product_id, pp.price as basic_price, co.currency as price_currency, co.currency_symbol as price_symbol')
        ->leftJoin('countries as co', 'co.id', '=', 'pp.country_id')
        ->where('pp.tier', 'basic');

    $wishlistCountSub = DB::table('wishlists')
        ->selectRaw('product_id, COUNT(*) as wl_count')
        ->groupBy('product_id');

    // ---- Base query: filter + join subs, but only select columns we need
    $base = Product::query()
        ->where('status', 'published')
        ->when($typeId, fn($q) => $q->where('product_type_id', $typeId))
        ->when(!empty($subIds), fn($q) => $q->whereIn('product_subcategory_id', $subIds))
        ->when($usesAi, fn($q) => $q->where('uses_ai', 1))
        ->when($hasTeam,fn($q) => $q->where('has_team', 1))
        ->leftJoinSub($basicPriceSub, 'bp', 'bp.product_id', '=', 'products.id')
        ->leftJoinSub($wishlistCountSub, 'wc', 'wc.product_id', '=', 'products.id')
        ->with([
            'user:id,first_name,last_name,unique_id',
            'pricings.country:id,currency,currency_symbol',
        ])
        ->withCount('reviews')
        ->withAvg('reviews as reviews_avg', 'rating_number')
        ->select([
            'products.*',
            DB::raw('COALESCE(wc.wl_count, 0) as wishlist_count'),
            DB::raw('bp.basic_price'),
            DB::raw('bp.price_currency'),
            DB::raw('bp.price_symbol'),
        ]);

    // ---- Sorting that can be done in SQL
    if ($sort === 'newest') {
        $base->orderByDesc('products.id');
    } else {
        // default "relevant" → newest as a reasonable fallback
        $base->orderByDesc('products.id');
    }

    // ---- Paginate in DB (only current page is retrieved)
    $page = max(1, (int)$page);
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $paginator = $base->paginate($perPage, ['*'], 'page', $page);

    $slice = collect($paginator->items());

    // ---- Map + price conversion ONLY for current page
    [$mappedColl, ] = $this->mapProductsToCards($slice, $targetCurrencyCode, $targetCurrencySymbol, true);

    // Attach wishlist_count from join (if mapper didn’t already set it)
    $byId = $slice->keyBy('id');
    $mappedColl = $mappedColl->map(function ($c) use ($byId) {
        $row = $byId[$c['id']] ?? null;
        $c['wishlist_count'] = (int) ($row->wishlist_count ?? ($c['wishlist_count'] ?? 0));
        return $c;
    });

    // Price filter on BASIC (numeric)
    if ($priceMin !== null) $mappedColl = $mappedColl->filter(fn($c) => $c['price_n'] !== null && $c['price_n'] >= $priceMin);
    if ($priceMax !== null) $mappedColl = $mappedColl->filter(fn($c) => $c['price_n'] !== null && $c['price_n'] <= $priceMax);

    // Sort by price if requested (PHP sort is OK for just one page)
    $mapped = match ($sort) {
        'price_asc'  => $mappedColl->sortBy(fn($c) => $c['price_n'] ?? INF)->values(),
        'price_desc' => $mappedColl->sortByDesc(fn($c) => $c['price_n'] ?? -INF)->values(),
        default      => $mappedColl->values(),
    };

    // Plain-text desc only for current page
    $descById = $slice->mapWithKeys(function ($p) {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$p->description)));
        return [$p->id => $plain];
    });

    // Final shape
    $prodById = $slice->keyBy('id');

    $viewItems = $mapped->map(function ($c) use ($descById, $prodById) {
        $pid = $c['id'];
        $p   = $prodById[$pid] ?? null;
        $desc= (string) ($descById[$pid] ?? ($c['desc'] ?? ''));
        return [
            'id'             => $pid,
            'name'           => $c['name'],
            'cover'          => $c['cover'],
            'seller'         => $c['seller'],
            'seller_id'      => $p?->user?->id,
            'avatar'         => $c['avatar'],
            'price'          => $c['price'] ?? 'N/A',   // BASIC tier display (mapper)
            'price_n'        => $c['price_n'] ?? null,  // BASIC tier numeric
            'rating'         => $c['rating'],
            'reviews'        => $c['reviews'],
            'url'            => route('product.details', ['id' => $pid]),
            'desc'           => $desc,
            'wishlist_count' => (int)($c['wishlist_count'] ?? 0),
        ];
    })->values();

    // Pagination flags (no extra guesswork)
    $hasMore = $paginator->currentPage() < $paginator->lastPage();
    $next    = $hasMore ? ($paginator->currentPage() + 1) : null;

    return [$viewItems, $hasMore, $next];
}


private function getFilteredBoostedCards(Request $request, string $targetCurrencyCode, string $targetCurrencySymbol): array
{
   $now = now();

$boostRows = Cache::remember('boosted_ids', 60, function () use ($now) {
    return ProductBoost::query()
        ->where('is_active', 1)
        ->where('start_at', '<=', $now)
        ->where('end_at', '>=', $now)
        ->orderByDesc('id')
        ->get(['id','product_id']);
});


    $boostedProductIds = [];
    foreach ($boostRows as $r) {
        $pid = (int)$r->product_id;
        if (!in_array($pid, $boostedProductIds, true)) $boostedProductIds[] = $pid;
    }
    if (empty($boostedProductIds)) return [collect(), 0];

    $typeId  = $request->integer('type_id');

    // Multi-sub (keeps legacy)
    $subIds = $request->input('sub_ids', []);
    if (!is_array($subIds)) {
        $subIds = strlen((string)$subIds) ? explode(',', (string)$subIds) : [];
    }
    $subIds = array_values(array_unique(array_filter(array_map('intval', $subIds))));
    $legacySub = $request->integer('sub_id');
    if ($legacySub && !in_array($legacySub, $subIds, true)) $subIds[] = $legacySub;

    $usesAi  = $request->boolean('uses_ai', false);
    $hasTeam = $request->boolean('has_team', false);

    $priceMin = $request->filled('price_min') ? (float)$request->input('price_min') : null;
    $priceMax = $request->filled('price_max') ? (float)$request->input('price_max') : null;

    // Subqueries
    $basicPriceSub = DB::table('product_pricings as pp')
        ->selectRaw('pp.product_id, pp.price as basic_price, co.currency as price_currency, co.currency_symbol as price_symbol')
        ->leftJoin('countries as co', 'co.id', '=', 'pp.country_id')
        ->where('pp.tier', 'basic');

    $wishlistCountSub = DB::table('wishlists')
        ->selectRaw('product_id, COUNT(*) as wl_count')
        ->groupBy('product_id');

    // Get boosted with all filters, keep original order
    $products = Product::query()
        ->whereIn('products.id', $boostedProductIds)
        ->where('status', 'published')
        ->when($typeId, fn($q) => $q->where('product_type_id', $typeId))
        ->when(!empty($subIds), fn($q) => $q->whereIn('product_subcategory_id', $subIds))
        ->when($usesAi, fn($q) => $q->where('uses_ai', 1))
        ->when($hasTeam,fn($q) => $q->where('has_team', 1))
        ->leftJoinSub($basicPriceSub, 'bp', 'bp.product_id', '=', 'products.id')
        ->leftJoinSub($wishlistCountSub, 'wc', 'wc.product_id', '=', 'products.id')
        ->with([
            'user:id,first_name,last_name,unique_id',
            'pricings.country:id,currency,currency_symbol',
        ])
        ->withCount('reviews')
        ->withAvg('reviews as reviews_avg', 'rating_number')
        ->select([
            'products.*',
            DB::raw('COALESCE(wc.wl_count, 0) as wishlist_count'),
            DB::raw('bp.basic_price'),
            DB::raw('bp.price_currency'),
            DB::raw('bp.price_symbol'),
        ])
        ->orderByRaw('FIELD(products.id,'.implode(',', $boostedProductIds).')')
        ->get();

    // Map + BASIC price for these items only
    [$mappedColl, ] = $this->mapProductsToCards($products, $targetCurrencyCode, $targetCurrencySymbol, true);

    // Attach wishlist_count from join
    $byId = $products->keyBy('id');
    $mappedColl = $mappedColl->map(function ($c) use ($byId) {
        $row = $byId[$c['id']] ?? null;
        $c['wishlist_count'] = (int) ($row->wishlist_count ?? ($c['wishlist_count'] ?? 0));
        return $c;
    });

    // Price range on BASIC
    if ($priceMin !== null) $mappedColl = $mappedColl->filter(fn($c) => $c['price_n'] !== null && $c['price_n'] >= $priceMin);
    if ($priceMax !== null) $mappedColl = $mappedColl->filter(fn($c) => $c['price_n'] !== null && $c['price_n'] <= $priceMax);

    // Final payload
    $cards = $mappedColl->map(function ($c) use ($byId) {
        $p = $byId[$c['id']] ?? null;
        return [
            'id'             => $c['id'],
            'name'           => $c['name'],
            'cover'          => $c['cover'],
            'seller'         => $c['seller'],
            'seller_id'      => $p?->user?->id,
            'avatar'         => $c['avatar'],
            'price'          => $c['price'] ?? 'N/A',
            'price_n'        => $c['price_n'] ?? null,
            'rating'         => $c['rating'],
            'reviews'        => $c['reviews'],
            'url'            => route('product.details', ['id' => $c['id']]),
            'desc'           => $c['desc'] ?? '',
            'wishlist_count' => (int)($c['wishlist_count'] ?? 0),
        ];
    })->values();

    return [$cards, $cards->count()];
}



    // =========================
    // Marketplace list (AJAX)
    // =========================
    public function marketplaceList(Request $request)
    {
        $viewer = auth()->user();
        if ($viewer) {
            $country = $viewer->country_id ? Country::find($viewer->country_id) : null;
            $targetCurrencyCode   = $country?->currency ?? 'USD';
            $targetCurrencySymbol = $country?->currency_symbol ?? '$';
        } else {
            $targetCurrencyCode   = 'USD';
            $targetCurrencySymbol = '$';
        }

        $page = max(1, (int)$request->integer('page', 1));

        [$cards, $hasMore, $nextPage] = $this->queryMarketplaceCards($request, $targetCurrencyCode, $targetCurrencySymbol, $page);

        $boostedPayload = null;
        if ($page === 1) {
            [$boostedCards] = $this->getFilteredBoostedCards($request, $targetCurrencyCode, $targetCurrencySymbol);
            $boostedPayload = $boostedCards;
        }

        return response()->json([
            'items'         => $cards,
            'has_more'      => $hasMore,
            'next'          => $nextPage,
            'boosted_items' => $boostedPayload,
        ]);
    }

    // =========================
    // Subcategories (AJAX)
    // =========================
    public function marketplaceSubcategories(Request $request)
    {
        $typeId = $request->integer('type_id');
        $q = ProductSubcategory::query()->where('is_active', 1);
        if ($typeId) $q->where('product_type_id', $typeId);
        $subs = $q->orderBy('name')->get(['id','name','product_type_id']);
        return response()->json($subs);
    }


private function mapProductsToCards($products, string $targetCurrencyCode, string $targetCurrencySymbol, bool $includeNumericPrice = false): array
{
    // Keep your existing card building exactly the same
    [$cards, $cnt] = $this->buildCardsForProducts($products, $targetCurrencyCode, $targetCurrencySymbol);

    // Ensure we always work with a Collection
    if (!($cards instanceof Collection)) {
        $cards = collect($cards);
    }

    // Collect product IDs present in the current card set
    $productIds = $cards->pluck('id')->filter()->values();

    // Efficient wishlist counts for these products (single query)
    $wishCounts = $productIds->isEmpty()
        ? collect()
        : DB::table('wishlists')
            ->select('product_id', DB::raw('COUNT(*) as total'))
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

    // Map product_id -> seller_id from the provided $products collection (if available)
    $sellerById = collect();
    if ($products instanceof Collection) {
        // In case relations not loaded, use optional() guards
        $sellerById = $products->mapWithKeys(function ($p) {
            return [$p->id => optional($p->user)->id];
        });
    }

    // Enrich cards with wishlist_count and seller_id; optionally drop price_n
    $cards = $cards->map(function (array $c) use ($wishCounts, $sellerById, $includeNumericPrice) {
        $pid = $c['id'] ?? null;

        // Keep existing value if your builder already set it; otherwise inject from DB (default 0)
        $c['wishlist_count'] = isset($c['wishlist_count'])
            ? (int) $c['wishlist_count']
            : (int) ($wishCounts[$pid] ?? 0);

        // Inject seller_id only if it's not already present
        if (!array_key_exists('seller_id', $c)) {
            $c['seller_id'] = $sellerById[$pid] ?? null;
        }

        // Preserve your current behavior: hide numeric price unless explicitly requested
        if (!$includeNumericPrice) {
            unset($c['price_n']);
        }

        return $c;
    });

    return [$cards, $cnt];
}


    // =========================
    // Analytics (Clicks / Views / Impressions)
    // =========================

    /**
     * POST /analytics/product-click
     * Body: { product_id:int, source?:string }
     * Saves to product_clicks and bumps product_insights.clicks for today.
     */
    public function analyticsProductClick(Request $request)
    {
        $pid = (int) $request->integer('product_id');
        if (!$pid) return response()->noContent();

        // Raw event row (if table exists)
        try {
            if (Schema::hasTable('product_clicks')) {
                DB::table('product_clicks')->insert([
                    'product_id' => $pid,
                    'user_id'    => optional($request->user())->id,
                    'source'     => (string)$request->input('source', 'welcome'),
                    'ip'         => $request->ip(),
                    'user_agent' => substr((string)$request->userAgent(), 0, 255),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // ignore write failure, still try to bump aggregate
        }

        // Aggregate
        $this->bumpInsight($pid, clicks: 1);

        return response()->noContent();
    }

    /**
     * POST /analytics/product-view
     * Body: { product_id:int, source?:string }
     * Saves to product_views and bumps product_insights.views.
     * Use this on the *product details page* (onload).
     */
    public function analyticsProductView(Request $request)
    {
        $pid = (int) $request->integer('product_id');
        if (!$pid) return response()->noContent();

        try {
            if (Schema::hasTable('product_views')) {
                DB::table('product_views')->insert([
                    'product_id' => $pid,
                    'user_id'    => optional($request->user())->id,
                    'source'     => (string)$request->input('source', 'details'),
                    'ip'         => $request->ip(),
                    'user_agent' => substr((string)$request->userAgent(), 0, 255),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // ignore write failure
        }

        $this->bumpInsight($pid, views: 1);

        return response()->noContent();
    }

 // POST /analytics/boost-view
public function analyticsBoostView(\Illuminate\Http\Request $request)
{
    $pid    = (int) $request->integer('product_id');
    $source = (string) $request->input('source', 'welcome'); // welcome|marketplace

    if (!$pid) return response()->noContent();

    // raw view row (best-effort)
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('product_views')) {
            \Illuminate\Support\Facades\DB::table('product_views')->insert([
                'product_id' => $pid,
                'user_id'    => optional($request->user())->id,
                'source'     => $source,     // "welcome" or "marketplace"
                'ip'         => $request->ip(),
                'user_agent' => substr((string)$request->userAgent(), 0, 255),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    } catch (\Throwable $e) {
        // don't block UX
    }

    // count this as a "view" in daily rollup
    $this->bumpInsight($pid, views: 1);

    return response()->noContent();
}
    /**
     * POST /analytics/list-view
     * Body: { items: [int,int,...], source?:string }
     * Batch impression counter for marketplace grid (and boosted list) via IntersectionObserver.
     */
public function analyticsListImpressions(\Illuminate\Http\Request $request)
{
    $ids    = array_values(array_unique(array_map('intval', (array)$request->input('items', []))));
    $source = (string) $request->input('source', 'marketplace');

    if (empty($ids)) return response()->noContent();

    // Bulk insert raw views (one row per card that came into view)
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('product_views')) {
            $now    = now();
            $userId = optional($request->user())->id;
            $rows   = [];
            foreach ($ids as $pid) {
                if ($pid > 0) {
                    $rows[] = [
                        'product_id' => $pid,
                        'user_id'    => $userId,
                        'source'     => $source, // "welcome" or "marketplace"
                        'ip'         => $request->ip(),
                        'user_agent' => substr((string)$request->userAgent(), 0, 255),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if ($rows) {
                \Illuminate\Support\Facades\DB::table('product_views')->insert($rows);
            }
        }
    } catch (\Throwable $e) {
        // ignore
    }

    // Bump daily "views" for each product
    foreach ($ids as $pid) {
        if ($pid > 0) $this->bumpInsight($pid, views: 1);
    }

    return response()->noContent();
}
    // -------------------------
    // Insights helper
    // -------------------------
    /**
     * Increment product_insights daily counters using MySQL UPSERT.
     * Any missing metric defaults to 0 for the operation.
     */
    private function bumpInsight(int $productId, int $views = 0, int $clicks = 0, int $impressions = 0): void
    {
        // If table doesn't exist yet, just exit quietly
        if (!Schema::hasTable('product_insights')) return;

        $date = now()->toDateString(); // YYYY-MM-DD
        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE to atomically increment
            DB::statement(
                'INSERT INTO product_insights (product_id, `date`, views, clicks, impressions, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    views = views + VALUES(views),
                    clicks = clicks + VALUES(clicks),
                    impressions = impressions + VALUES(impressions),
                    updated_at = NOW()',
                [$productId, $date, $views, $clicks, $impressions]
            );
        } catch (\Throwable $e) {
            // swallow—analytics should never block main UX
        }
    }

    public function searchSuggest(\Illuminate\Http\Request $request)
{
    // viewer currency (same logic you already use)
    $viewer = auth()->user();
    if ($viewer) {
        $country = $viewer->country_id ? \App\Models\Country::find($viewer->country_id) : null;
        $targetCurrencyCode   = $country?->currency ?? 'USD';
        $targetCurrencySymbol = $country?->currency_symbol ?? '$';
    } else {
        $targetCurrencyCode   = 'USD';
        $targetCurrencySymbol = '$';
    }

    $term = trim((string)$request->input('q', ''));
    $limit = max(1, (int)$request->integer('limit', 10));

    // ---- Active boosted product ids (unique, latest wins) ----
    $now = now();
    $boostRows = ProductBoost::query()
        ->where('is_active', 1)
        ->where('start_at', '<=', $now)
        ->where('end_at', '>=', $now)
        ->orderByDesc('id')
        ->get(['id','product_id']);

    $seen = [];
    $boostedProductIds = [];
    foreach ($boostRows as $r) {
        if (!isset($seen[$r->product_id])) {
            $seen[$r->product_id] = true;
            $boostedProductIds[]  = (int)$r->product_id;
        }
    }
    $boostedIdSet = collect($boostedProductIds)->flip(); // for O(1) lookup

    // ---- Boosted cards (for default/trending section) ----
    $boostedCards = collect();
    if (!empty($boostedProductIds)) {
        $boostedProducts = \App\Models\Product::query()
            ->whereIn('id', $boostedProductIds)
            ->where('status', 'published')
            ->with([
                'user:id,first_name,last_name,unique_id',
                'pricings.country:id,currency,currency_symbol',
                'type:id,name',
            ])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg', 'rating_number')
            ->orderByRaw('FIELD(id,'.implode(',', $boostedProductIds).')')
            ->take(12) // don't flood the dropdown
            ->get();

        [$boostedCards, ] = $this->buildCardsForProducts($boostedProducts, $targetCurrencyCode, $targetCurrencySymbol);
        // keep it light
        $boostedCards = $boostedCards->take(8)->values();
    }

    // ---- Query results (match by product, category or subcategory) ----
    $items = collect();
    if ($term !== '') {
        $q = \App\Models\Product::query()
            ->where('status', 'published')
            ->where(function($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                   ->orWhereHas('type', fn($t) => $t->where('name', 'like', "%{$term}%"))
                   ->orWhereHas('subcategory', fn($s) => $s->where('name', 'like', "%{$term}%"));
            })
            ->with([
                'user:id,first_name,last_name,unique_id',
                'pricings.country:id,currency,currency_symbol',
                'type:id,name',
                'subcategory:id,name',
            ])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg', 'rating_number')
            ->latest('id')
            ->take($limit)
            ->get();

        [$cards, ] = $this->buildCardsForProducts($q, $targetCurrencyCode, $targetCurrencySymbol);
        $items = $cards->values();
    }

    // mark boosted in payload (helps UI add a badge)
    $mark = function($c) use ($boostedIdSet) {
        $c['is_boosted'] = $boostedIdSet->has($c['id']);
        return $c;
    };

    return response()->json([
        'boosted' => $boostedCards->map($mark)->values(),
        'items'   => $items->map($mark)->values(),
    ]);
}

}

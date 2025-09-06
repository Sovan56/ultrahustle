<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBoost;
use App\Models\ProductPricing;
use App\Models\ProductType;
use App\Models\ProductSubcategory;
use App\Models\UserAdminAnotherDetail;
use App\Models\Country;
use App\Services\Currency\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    // =========================
    // Home / Welcome
    // =========================
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
        ]);
    }

    // =========================
    // Public Marketplace (page)
    // =========================
    public function marketplace(Request $request)
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
            'subs'                  => $subs,
            'targetCurrencyCode'    => $targetCurrencyCode,
            'targetCurrencySymbol'  => $targetCurrencySymbol,
            'initialCards'          => $cards,
            'hasMore'               => $hasMore,
            'nextPage'              => $nextPage,
        ]);
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

    // -------------------------
    // Mapping helpers
    // -------------------------
    private function queryMarketplaceCards(Request $request, string $targetCurrencyCode, string $targetCurrencySymbol, int $page = 1): array
    {
        $fx = new CurrencyConverter();
        $perPage   = max(1, (int)$request->integer('per_page', 24));
        $sliceSize = max($perPage * 20, 1000);

        $typeId  = $request->integer('type_id');
        $subId   = $request->integer('sub_id');
        $usesAi  = $request->boolean('uses_ai', false);
        $hasTeam = $request->boolean('has_team', false);

        $priceMin = $request->filled('price_min') ? (float)$request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float)$request->input('price_max') : null;

        $sort = $request->string('sort', 'relevant')->toString();

        $base = Product::query()
            ->where('status', 'published')
            ->when($typeId, fn($q) => $q->where('product_type_id', $typeId))
            ->when($subId,  fn($q) => $q->where('product_subcategory_id', $subId))
            ->when($usesAi, fn($q) => $q->where('uses_ai', 1))
            ->when($hasTeam,fn($q) => $q->where('has_team', 1))
            ->with([
                'user:id,first_name,last_name,unique_id',
                'pricings.country:id,currency,currency_symbol',
            ])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg', 'rating_number');

        $slice = $base->latest('id')->take($sliceSize)->get();

        [$mappedColl, ] = $this->mapProductsToCards($slice, $targetCurrencyCode, $targetCurrencySymbol, true);

        if ($priceMin !== null) $mappedColl = $mappedColl->filter(fn($c) => $c['price_n'] !== null && $c['price_n'] >= $priceMin);
        if ($priceMax !== null) $mappedColl = $mappedColl->filter(fn($c) => $c['price_n'] !== null && $c['price_n'] <= $priceMax);

        $mapped = match ($sort) {
            'price_asc'  => $mappedColl->sortBy(fn($c) => $c['price_n'] ?? INF)->values(),
            'price_desc' => $mappedColl->sortByDesc(fn($c) => $c['price_n'] ?? -INF)->values(),
            'newest'     => $mappedColl,
            default      => $mappedColl,
        };

        $total   = $mapped->count();
        $items   = $mapped->forPage($page, $perPage)->values();
        $hasMore = ($page * $perPage) < $total;
        $nextPage= $hasMore ? ($page + 1) : null;

        $viewItems = $items->map(fn($c) => [
            'id'      => $c['id'],
            'name'    => $c['name'],
            'cover'   => $c['cover'],
            'seller'  => $c['seller'],
            'avatar'  => $c['avatar'],
            'price'   => $c['price'] ?? 'N/A',
            'rating'  => $c['rating'],
            'reviews' => $c['reviews'],
            'url'     => route('product.details', ['id' => $c['id']]),
        ])->values();

        return [$viewItems, $hasMore, $nextPage];
    }

    private function getFilteredBoostedCards(Request $request, string $targetCurrencyCode, string $targetCurrencySymbol): array
    {
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

        if (empty($boostedProductIds)) return [collect(), 0];

        $typeId  = $request->integer('type_id');
        $subId   = $request->integer('sub_id');
        $usesAi  = $request->boolean('uses_ai', false);
        $hasTeam = $request->boolean('has_team', false);

        $base = Product::query()
            ->whereIn('id', $boostedProductIds)
            ->where('status', 'published')
            ->when($typeId, fn($q) => $q->where('product_type_id', $typeId))
            ->when($subId,  fn($q) => $q->where('product_subcategory_id', $subId))
            ->when($usesAi, fn($q) => $q->where('uses_ai', 1))
            ->when($hasTeam,fn($q) => $q->where('has_team', 1))
            ->with([
                'user:id,first_name,last_name,unique_id',
                'pricings.country:id,currency,currency_symbol',
            ])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg', 'rating_number')
            ->orderByRaw('FIELD(id,'.implode(',', $boostedProductIds).')');

        $products = $base->get();

        [$cards, $count] = $this->buildCardsForProducts($products, $targetCurrencyCode, $targetCurrencySymbol);

        return [$cards, $count];
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
                if ($from && $from !== $targetCurrencyCode) $amt = $fx->convert($amt, $from, $targetCurrencyCode);
                $priceNum  = round($amt, 2);
                $priceText = $targetCurrencySymbol . number_format($priceNum, 2);
            }

            $cover = $p->images[0] ?? null;
            if ($cover && !Str::startsWith($cover, ['http://','https://','/media/','/storage/'])) {
                $cover = route('media.pass', ['path' => ltrim($cover, '/')]);
            }
            if (!$cover) $cover = asset('images/slider/baby-slide1.jpg');

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

            return [
                'id'       => $p->id,
                'name'     => $p->name,
                'cover'    => $cover,
                'seller'   => $sellerName,
                'avatar'   => $avatar,
                'price'    => $priceText,
                'price_n'  => $priceNum,
                'rating'   => number_format((float)($p->reviews_avg ?? 0), 1),
                'reviews'  => (int)($p->reviews_count ?? 0),
                'url'      => route('product.details', ['id' => $p->id]),
            ];
        })->values();

        return [$cards, $cards->count()];
    }

    private function mapProductsToCards($products, string $targetCurrencyCode, string $targetCurrencySymbol, bool $includeNumericPrice = false): array
    {
        [$cards, $cnt] = $this->buildCardsForProducts($products, $targetCurrencyCode, $targetCurrencySymbol);
        if (!$includeNumericPrice) {
            $cards = $cards->map(function($c){ unset($c['price_n']); return $c; });
        }
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
    $boostRows = \App\Models\ProductBoost::query()
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

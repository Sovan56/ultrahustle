<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\Country;
use App\Models\User;
use App\Models\MyOrder;
use App\Models\ProductReview;
use App\Models\Wishlist;
use App\Services\Currency\CurrencyConverter;
use App\Services\ChatMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema; // ← added
use Illuminate\Support\Str;

class ProductPublicController extends Controller
{
    public function show(int $id)
    {
        $product = Product::with([
                'user:id,first_name,last_name,unique_id,last_seen_at,created_at,country_id',
                'user.anotherDetail:id,user_admin_id,profile_picture',
                'country:id,currency,currency_symbol',
                'type:id,name,slug',
                'subcategory:id,name',
                'pricings.country:id,currency,currency_symbol',
                'faqs:id,product_id,faq_heading,question,faq_answer',
            ])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg', 'rating_number')
            ->findOrFail($id);

        $viewer       = auth()->user() ?? User::find(session('user_id'));
        $isLogged     = (bool) $viewer;
        $viewerCtry   = $viewer?->country_id ? Country::find($viewer->country_id) : null;
        $targetCode   = $viewerCtry?->currency ?? ($viewer?->currency ?: 'USD');
        $targetSym    = $viewerCtry?->currency_symbol ?? '$';
        $viewerCtryId = $viewerCtry?->id;

        $fx    = new CurrencyConverter();
        $tiers = $this->buildTiers($product, $viewerCtryId, $targetCode, $targetSym, $fx);

        $images = $this->normalizeImageUrls((array)($product->images ?? []));
        if (empty($images)) {
            $images = ['https://placehold.co/750x400.png?text=No+Image'];
        }

        $seller       = $product->user;
        $sellerName   = trim(($seller->first_name ?? '') . ' ' . ($seller->last_name ?? '')) ?: ($seller->unique_id ?? 'Seller');
        $sellerAvatar = user_avatar_url($seller);
        $avgSec       = app(ChatMetricsService::class)->avgResponseSeconds($seller->id ?? 0) ?? ($seller->avg_response_seconds ?? null);
        $avgHuman     = $avgSec ? ChatMetricsService::human($avgSec) : '1 hour';
        $sellerOnline = optional($seller->last_seen_at)->gt(now()->subMinutes(5));

        $sellerOrdersCount = MyOrder::whereHas('product', function ($q) use ($seller) {
            $q->where('user_id', $seller->id ?? 0);
        })->count();
        $sellerSinceYear   = optional($seller->created_at)->format('Y');
        $sellerCountryCode = optional($seller->country_id ? Country::find($seller->country_id) : null)?->currency ?? null;

        $sellerCard = [
            'name'          => $sellerName,
            'avatar'        => $sellerAvatar,
            'online'        => (bool) $sellerOnline,
            'avg_response'  => $avgHuman,
            'orders_count'  => $sellerOrdersCount,
            'since_year'    => $sellerSinceYear,
            'country_code'  => $sellerCountryCode,
        ];

        $typeName          = trim(strtolower($product->type->name ?? ''));
        $isService         = ($typeName === 'services');
        $isDigitalOrCourse = in_array($typeName, ['digital product', 'course'], true);

        $alreadyPurchased = $viewer
            ? MyOrder::where('buyer_id', $viewer->id)
                ->where('product_id', $product->id)
                ->whereIn('status', ['paid','delivered','completed'])
                ->exists()
            : false;

        $alreadyReviewed = $viewer
            ? ProductReview::where('product_id', $product->id)->where('user_id', $viewer->id)->exists()
            : false;

        $reviews = ProductReview::with([
                'user:id,first_name,last_name,unique_id',
                'user.anotherDetail:id,user_admin_id,profile_picture'
            ])
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $alreadyWished = $viewer
            ? Wishlist::where('user_id', $viewer->id)->where('product_id', $product->id)->exists()
            : false;

        $categoryUrl    = route('marketplace', [], false) . '?type_id=' . ($product->type->id ?? '');
        $subcategoryUrl = $categoryUrl . '&sub_id=' . ($product->subcategory->id ?? '');

        $recommended    = $this->recommendedPayload($product, $viewerCtryId, $targetCode, $targetSym, $fx, 10);
        $moreFromSeller = $this->moreFromSellerPayload($product, $viewerCtryId, $targetCode, $targetSym, $fx, 10);

        return view('ProductDetails', [
            'product'              => $product,
            'images'               => $images,
            'tiers'                => $tiers,
            'targetCurrencySymbol' => $targetSym,

            'sellerName'           => $sellerName,
            'sellerAvatar'         => $sellerAvatar,
            'sellerOnline'         => $sellerOnline,
            'avgResponseHuman'     => $avgHuman,
            'sellerCard'           => $sellerCard,

            'rating'               => number_format((float)($product->reviews_avg ?? 0), 1),
            'reviewsCount'         => (int)($product->reviews_count ?? 0),

            'isService'            => $isService,
            'isDigitalOrCourse'    => $isDigitalOrCourse,
            'isLogged'             => $isLogged,
            'alreadyPurchased'     => $alreadyPurchased,
            'alreadyReviewed'      => $alreadyReviewed,
            'alreadyWished'        => $alreadyWished,

            'reviews'              => $reviews,
            'recommended'          => $recommended,
            'moreFromSeller'       => $moreFromSeller,

            'shareUrl'             => route('product.details', $product->id),
            'categoryUrl'          => $categoryUrl,
            'subcategoryUrl'       => $subcategoryUrl,
        ]);
    }

    public function recommended(int $id): JsonResponse
    {
        $product      = Product::with(['subcategory:id'])->findOrFail($id);
        $viewer       = auth()->user() ?? User::find(session('user_id'));
        $viewerCtry   = $viewer?->country_id ? Country::find($viewer->country_id) : null;
        $targetCode   = $viewerCtry?->currency ?? ($viewer?->currency ?: 'USD');
        $targetSym    = $viewerCtry?->currency_symbol ?? '$';
        $viewerCtryId = $viewerCtry?->id;
        $fx           = new CurrencyConverter();

        $data = $this->recommendedPayload($product, $viewerCtryId, $targetCode, $targetSym, $fx, 12);
        return response()->json(['ok' => true, 'items' => $data]);
    }

    public function moreFromSeller(int $id): JsonResponse
    {
        $product      = Product::with(['user:id'])->findOrFail($id);
        $viewer       = auth()->user() ?? User::find(session('user_id'));
        $viewerCtry   = $viewer?->country_id ? Country::find($viewer->country_id) : null;
        $targetCode   = $viewerCtry?->currency ?? ($viewer?->currency ?: 'USD');
        $targetSym    = $viewerCtry?->currency_symbol ?? '$';
        $viewerCtryId = $viewerCtry?->id;
        $fx           = new CurrencyConverter();

        $data = $this->moreFromSellerPayload($product, $viewerCtryId, $targetCode, $targetSym, $fx, 12);
        return response()->json(['ok' => true, 'items' => $data]);
    }

    /* ====================== Helpers ====================== */

    private function buildTiers(
        Product $product,
        ?int $viewerCountryId,
        string $targetCode,
        string $targetSym,
        CurrencyConverter $fx
    ): array {
        $order = ['basic', 'standard', 'premium'];
        $isValid = function (ProductPricing $pp): bool {
            $price = is_numeric($pp->price) ? (float)$pp->price : 0.0;
            $days  = is_numeric($pp->delivery_days) ? (int)$pp->delivery_days : 0;
            return $price > 0 && $days >= 0;
        };

        $tiers = [];
        foreach ($order as $tierKey) {
            $valid = $product->pricings->where('tier', $tierKey)->filter($isValid);
            if ($valid->isEmpty()) continue;

            $picked = $valid->sortBy(function (ProductPricing $pp) use ($product, $viewerCountryId) {
                return ($viewerCountryId && $pp->country_id === $viewerCountryId) ? 0
                    : ($pp->country_id === $product->country_id ? 1 : 2);
            })->first();

            $fromCode = $picked->country?->currency ?? $product->country?->currency ?? null;
            $amount   = (float) $picked->price;
            if ($fromCode && $fromCode !== $targetCode) {
                $amount = (float) $fx->convert($amount, $fromCode, $targetCode);
            }

            $tiers[] = [
                'key'           => $tierKey,
                'label'         => ucfirst($tierKey),
                'price_display' => $targetSym . number_format($amount, 2),
                'delivery_days' => (int) $picked->delivery_days,
                'details'       => $picked->details,
            ];
        }

        return $tiers;
    }

    private function normalizeImageUrls(array $paths): array
    {
        $urls = [];
        foreach ($paths as $path) {
            $p = (string)$path;
            if ($p === '') continue;

            if (Str::startsWith($p, ['http://', 'https://', '/media/', '/storage/'])) {
                if (Str::startsWith($p, '/storage/')) {
                    $urls[] = route('media.pass', ['path' => ltrim($p, '/')]);
                } else {
                    $urls[] = $p;
                }
                continue;
            }
            $urls[] = route('media.pass', ['path' => ltrim($p, '/')]);
        }
        return array_values(array_unique($urls));
    }

    private function minDisplayPrice(
        Product $p,
        ?int $viewerCountryId,
        string $targetCode,
        string $targetSym,
        CurrencyConverter $fx
    ): ?string {
        $valid = $p->pricings->filter(function (ProductPricing $pp) {
            $price = is_numeric($pp->price) ? (float)$pp->price : 0.0;
            $days  = is_numeric($pp->delivery_days) ? (int)$pp->delivery_days : 0;
            return $price > 0 && $days >= 0;
        });

        if ($valid->isEmpty()) return null;

        $min = null;
        foreach ($valid as $pp) {
            $from = $pp->country?->currency ?? $p->country?->currency ?? $targetCode;
            $amt  = (float) $pp->price;
            if ($from && $from !== $targetCode) {
                $amt = (float) $fx->convert($amt, $from, $targetCode);
            }
            $min = is_null($min) ? $amt : min($min, $amt);
        }
        return $min !== null ? ($targetSym . number_format($min, 2)) : null;
    }

    private function cardPayload(
        Product $p,
        ?int $viewerCountryId,
        string $targetCode,
        string $targetSym,
        CurrencyConverter $fx
    ): array {
        $imgList = $this->normalizeImageUrls((array)($p->images ?? []));
        $img     = $imgList[0] ?? 'https://placehold.co/480x320.png?text=No+Image';
        return [
            'id'            => (int) $p->id,
            'name'          => (string) $p->name,
            'image'         => $img,
            'price_display' => $this->minDisplayPrice($p, $viewerCountryId, $targetCode, $targetSym, $fx),
            'url'           => route('product.details', $p->id),
        ];
    }

    /**
     * Recommended products with robust FK detection for subcategory/type.
     */
    private function recommendedPayload(
        Product $current,
        ?int $viewerCountryId,
        string $targetCode,
        string $targetSym,
        CurrencyConverter $fx,
        int $limit = 10
    ): array {
        $q = Product::query()
            ->with(['pricings.country', 'country', 'type:id', 'subcategory:id'])
            ->where('id', '!=', $current->id);

        // Try to match by subcategory first
        $subId = $current->subcategory->id ?? null;
        if ($subId) {
            // Detect the correct FK column name on products table
            $subCol = collect(['subcategory_id', 'product_subcategory_id', 'sub_category_id'])
                ->first(fn($c) => Schema::hasColumn('products', $c));

            if ($subCol) {
                $q->where($subCol, $subId);
            } else {
                // Fallback: join via relation (no direct FK column assumption)
                $q->whereHas('subcategory', function ($qq) use ($subId) {
                    $qq->where('id', $subId);
                });
            }
        } elseif ($current->type?->id) {
            // Fallback to type match with robust FK detection
            $typeId = $current->type->id;
            $typeCol = collect(['product_type_id', 'type_id'])
                ->first(fn($c) => Schema::hasColumn('products', $c));

            if ($typeCol) {
                $q->where($typeCol, $typeId);
            } else {
                $q->whereHas('type', function ($qq) use ($typeId) {
                    $qq->where('id', $typeId);
                });
            }
        }

        $items = $q->latest('id')->limit($limit)->get();

        return $items->map(function (Product $p) use ($viewerCountryId, $targetCode, $targetSym, $fx) {
            return $this->cardPayload($p, $viewerCountryId, $targetCode, $targetSym, $fx);
        })->values()->all();
    }

    private function moreFromSellerPayload(
        Product $current,
        ?int $viewerCountryId,
        string $targetCode,
        string $targetSym,
        CurrencyConverter $fx,
        int $limit = 10
    ): array {
        $items = Product::with(['pricings.country', 'country'])
            ->where('user_id', $current->user->id ?? 0)
            ->where('id', '!=', $current->id)
            ->latest('id')
            ->limit($limit)
            ->get();

        return $items->map(function (Product $p) use ($viewerCountryId, $targetCode, $targetSym, $fx) {
            return $this->cardPayload($p, $viewerCountryId, $targetCode, $targetSym, $fx);
        })->values()->all();
    }
}

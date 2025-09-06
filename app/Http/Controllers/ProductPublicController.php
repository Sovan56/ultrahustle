<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\Country;
use App\Models\User;
use App\Models\MyOrder;
use App\Models\ProductReview;
use App\Services\Currency\CurrencyConverter;
use Illuminate\Support\Str;
use App\Services\ChatMetricsService;
use App\Models\Wishlist;
class ProductPublicController extends Controller
{
    public function show(int $id)
    {
        $product = Product::with([
            'user:id,first_name,last_name,unique_id,last_seen_at',
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

        // Viewer (support auth() AND session('user_id'))
        $viewer = auth()->user() ?? User::find(session('user_id'));
        $isLogged = (bool) $viewer;

        // Target currency (viewer’s country)
        if ($viewer?->country_id) {
            $viewerCountry         = Country::find($viewer->country_id);
            $targetCurrencyCode    = $viewerCountry?->currency ?? ($viewer->currency ?: 'USD');
            $targetCurrencySymbol  = $viewerCountry?->currency_symbol ?? '$';
            $viewerCountryId       = $viewerCountry?->id;
        } else {
            $targetCurrencyCode    = 'USD';
            $targetCurrencySymbol  = '$';
            $viewerCountryId       = null;
        }

        $fx = new CurrencyConverter();

        // Tiers (allow 0-day for instant digital)
        $order = ['basic', 'standard', 'premium'];
        $isValidPricing = function (ProductPricing $pp): bool {
            $price = is_numeric($pp->price) ? (float)$pp->price : 0.0;
            $days  = is_numeric($pp->delivery_days) ? (int)$pp->delivery_days : 0;
            return $price > 0 && $days >= 0;
        };

        $tiers = [];
        foreach ($order as $tierKey) {
            $valid = $product->pricings->where('tier', $tierKey)->filter($isValidPricing);
            if ($valid->isEmpty()) continue;

            $picked = $valid->sortBy(function (ProductPricing $pp) use ($product, $viewerCountryId) {
                return ($viewerCountryId && $pp->country_id === $viewerCountryId) ? 0
                    : ($pp->country_id === $product->country_id ? 1 : 2);
            })->first();

            $fromCurrency = $picked->country?->currency;
            $amount = (float) $picked->price;
            if ($fromCurrency && $fromCurrency !== $targetCurrencyCode) {
                $amount = $fx->convert($amount, $fromCurrency, $targetCurrencyCode);
            }

            $tiers[] = [
                'key'           => $tierKey,
                'label'         => ucfirst($tierKey),
                'price_display' => $targetCurrencySymbol . number_format($amount, 2),
                'delivery_days' => (int) $picked->delivery_days,
                'details'       => $picked->details,
            ];
        }

        // Images
        $images = $product->images ?? [];
        $images = array_map(function ($path) {
            if (Str::startsWith($path, ['http://','https://','/media/','/storage/'])) return $path;
            return route('media.pass', ['path' => ltrim($path, '/')]);
        }, $images);
        if (empty($images)) $images = ['https://placehold.co/750x400.png?text=No+Image'];

        // Seller
        $sellerName = trim(($product->user->first_name ?? '').' '.($product->user->last_name ?? ''));
        $avatar = user_avatar_url($product->user);

        // Type flags
        $typeName = trim(strtolower($product->type->name ?? ''));
        $isService = ($typeName === 'services');
        $isDigitalOrCourse = in_array($typeName, ['digital product','course'], true);

        // Presence + avg
        $avgSec   = app(ChatMetricsService::class)->avgResponseSeconds($product->user->id ?? 0)
            ?? ($product->user->avg_response_seconds ?? null);
        $avgHuman = $avgSec ? ChatMetricsService::human($avgSec) : '1 hour';
        $sellerOnline = optional($product->user->last_seen_at)->gt(now()->subMinutes(5));

        // Purchase/Review state
        // NOTE: after wallet flow, status may become "delivered", so allow multiple statuses
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
        ])->where('product_id', $product->id)
          ->orderByDesc('created_at')
          ->limit(100)
          ->get();



          $alreadyWished = $viewer
    ? Wishlist::where('user_id', $viewer->id)->where('product_id', $product->id)->exists()
    : false;

        return view('ProductDetails', [
            'product'              => $product,
            'images'               => $images,
            'sellerName'           => $sellerName,
            'sellerAvatar'         => $avatar,
            'tiers'                => $tiers,
            'targetCurrencySymbol' => $targetCurrencySymbol,
            'rating'               => number_format((float)($product->reviews_avg ?? 0), 1),
            'reviewsCount'         => (int)($product->reviews_count ?? 0),
            'isService'            => $isService,
            'isDigitalOrCourse'    => $isDigitalOrCourse,
            'avgResponseHuman'     => $avgHuman,
            'sellerOnline'         => $sellerOnline,
            'alreadyPurchased'     => $alreadyPurchased,
            'alreadyReviewed'      => $alreadyReviewed,
            'reviews'              => $reviews,
            'isLogged'             => $isLogged,
            'alreadyWished'        => $alreadyWished,
        ]);
    }
}

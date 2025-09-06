<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\Currency\CurrencyConverter;

class WishlistController extends Controller
{
    /**
     * Toggle wishlist for a product.
     * - Guests:
     *   - AJAX: 401 JSON with login_url to welcome?login=1&redirect=...
     *   - Non-AJAX: redirect to welcome with login flag
     * - Auth: JSON { ok:true, wished:bool }
     */
    public function toggle(Request $r)
    {
        $r->validate(['product_id' => 'required|integer|exists:products,id']);

        $user = Auth::user() ?? \App\Models\User::find(session('user_id'));
        if (!$user) {
            $loginUrl = route('home', [
                'login'    => 1,
                'redirect' => $this->intendedUrl($r),
            ]);

            if ($r->expectsJson() || $r->ajax() || $r->wantsJson()) {
                return response()->json([
                    'ok'        => false,
                    'auth'      => false,
                    'login_url' => $loginUrl,
                ], 401);
            }

            // Non-AJAX fallback
            return redirect()->to($loginUrl);
        }

        $productId = (int) $r->product_id;

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'wished' => false]);
        }

        Wishlist::create([
            'user_id'    => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json(['ok' => true, 'wished' => true]);
    }

    /** Wishlist PAGE (UserAdmin) */
    public function page(Request $r)
    {
        $user = Auth::user() ?? \App\Models\User::find(session('user_id'));
        abort_if(!$user, 403);

        $viewerCountry = $user->country_id ? Country::find($user->country_id) : null;
        $targetCode    = $viewerCountry?->currency ?? 'USD';
        $targetSymbol  = $this->currencySymbol($targetCode);
        $fx            = new CurrencyConverter();

        $rows = Wishlist::with(['product' => function ($q) {
                $q->with(['user','type','subcategory','country'])
                  ->withCount('reviews')
                  ->withAvg('reviews as reviews_avg', 'rating_number');
            }])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $items = $rows->map(function ($wl) use ($targetCode, $targetSymbol, $fx) {
            $p = $wl->product;
            if (!$p) return null;

            $img = $this->mediaUrlFor(($p->images[0] ?? '')) ?: 'https://placehold.co/300x180?text=No+Image';

            // compute "starting at" price in viewer currency
            $pps = ProductPricing::with('country')->where('product_id', $p->id)->get();
            $amts = [];
            foreach ($pps as $pp) {
                $price = is_numeric($pp->price) ? (float)$pp->price : 0.0;
                $days  = is_numeric($pp->delivery_days) ? (int)$pp->delivery_days : 0;
                if ($price <= 0 || $days <= 0) continue;
                $from = $pp->country?->currency ?? $p->country?->currency ?? 'USD';
                $amt  = ($from !== $targetCode) ? (float)$fx->convert($price, $from, $targetCode) : $price;
                $amts[] = $amt;
            }
            $priceFrom = !empty($amts) ? min($amts) : null;

            return [
                'product_id' => $p->id,
                'name'       => $p->name,
                'image'      => $img,
                'rating'     => number_format((float)($p->reviews_avg ?? 0), 1),
                'reviews'    => (int)($p->reviews_count ?? 0),
                'price_from' => $priceFrom,
                'symbol'     => $targetSymbol,
            ];
        })->filter()->values();

        return view('UserAdmin.Wishlist', ['items' => $items]);
    }

    /** JSON: wishlist items for modal / hydration (safe for guests) */
    public function items()
    {
        $user = Auth::user() ?? \App\Models\User::find(session('user_id'));
        if (!$user) {
            return response()->json(['ok' => false, 'auth' => false, 'items' => []]);
        }

        $viewerCountry = $user->country_id ? Country::find($user->country_id) : null;
        $targetCode    = $viewerCountry?->currency ?? 'USD';
        $targetSymbol  = $this->currencySymbol($targetCode);
        $fx            = new CurrencyConverter();

        $rows = Wishlist::with(['product' => function ($q) {
                $q->with(['country'])->withCount('reviews')->withAvg('reviews as reviews_avg', 'rating_number');
            }])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $items = [];
        foreach ($rows as $wl) {
            $p = $wl->product;
            if (!$p) continue;

            $img = $this->mediaUrlFor(($p->images[0] ?? '')) ?: 'https://placehold.co/300x180?text=No+Image';

            // compute "starting at" price in viewer currency
            $pps = ProductPricing::with('country')->where('product_id', $p->id)->get();
            $amts = [];
            foreach ($pps as $pp) {
                $price = is_numeric($pp->price) ? (float)$pp->price : 0.0;
                $days  = is_numeric($pp->delivery_days) ? (int)$pp->delivery_days : 0;
                if ($price <= 0 || $days <= 0) continue;
                $from = $pp->country?->currency ?? $p->country?->currency ?? 'USD';
                $amt  = ($from !== $targetCode) ? (float)$fx->convert($price, $from, $targetCode) : $price;
                $amts[] = $amt;
            }
            $priceFrom = !empty($amts) ? min($amts) : null;

            $items[] = [
                'product_id' => $p->id,
                'name'       => $p->name,
                'image'      => $img,
                'url'        => route('product.details', $p->id),
                'rating'     => number_format((float)($p->reviews_avg ?? 0), 1),
                'reviews'    => (int)($p->reviews_count ?? 0),
                'price_from' => $priceFrom,
                'symbol'     => $targetSymbol,
            ];
        }

        return response()->json(['ok' => true, 'items' => $items]);
    }

    /** JSON: wishlist count for header badge (safe for guests) */
    public function count()
    {
        $user = Auth::user() ?? \App\Models\User::find(session('user_id'));
        if (!$user) return response()->json(['count' => 0]);
        $c = Wishlist::where('user_id', $user->id)->count();
        return response()->json(['count' => $c]);
    }

    /** OPTIONAL: ids for hydration (if you want it here instead of a route closure) */
    public function ids()
    {
        $ids = Wishlist::where('user_id', Auth::id() ?? session('user_id'))
            ->pluck('product_id');

        return response()->json(['ids' => $ids]);
    }

    /* =================== helpers =================== */

    protected function currencySymbol(string $code): string
    {
        $byCountry = Country::where('currency', $code)->first();
        if ($byCountry && $byCountry->currency_symbol) return $byCountry->currency_symbol;
        $map = ['USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','JPY'=>'¥','AUD'=>'A$','CAD'=>'C$','SGD'=>'S$','AED'=>'د.إ'];
        return $map[$code] ?? $code;
    }

    protected function mediaUrlFor(?string $path): ?string
    {
        $path = (string) $path;
        if ($path === '') return null;
        if (Str::startsWith($path, ['http://','https://','/media/','/storage/'])) {
            if (Str::startsWith($path, '/storage/')) {
                return route('media.pass', ['path' => ltrim($path, '/')]);
            }
            return $path;
        }
        return route('media.pass', ['path' => ltrim($path, '/')]);
    }

    /**
     * Build an "intended" URL for the redirect param.
     * Prefer Referer (page where the heart was clicked), else previous URL, else home.
     */
    protected function intendedUrl(Request $r): string
    {
        $ref = (string) $r->headers->get('referer');
        if ($ref) return $ref;
        $prev = url()->previous();
        return $prev ?: route('home');
    }
}

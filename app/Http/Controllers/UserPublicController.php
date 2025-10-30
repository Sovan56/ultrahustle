<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\Currency\CurrencyConverter;

class UserPublicController extends Controller
{
    /**
     * Show a public user profile by users.id
     *
     * Everything on the page is dynamic.
     * - NO usage of product_orders table.
     * - Currency converted to viewer currency (logged-in user) else USD/$.
     * - Review avatars from user_admin_another_details.profile_picture (creator & client views).
     * - Teams: "View Team" goes to user.admin.myteam.portfolio if logged in, else login.
     */
    public function userdetailsShow(Request $request, int $id)
    {
        // User + relations
        $user = User::with(['anotherDetail', 'country'])->find($id);
        abort_if(!$user, 404, 'User not found');

        $detail  = $user->anotherDetail;
        $country = $user->country;

        // ---- Resolve viewer currency (logged-in user's country) else USD/$
        [$viewerCurrencyCode, $viewerCurrencySymbol] = $this->resolveViewerCurrency();

        // ---- Avatar for profile header
        $display = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->unique_id ?? 'User #'.$user->id);
        $avatar  = $this->buildAvatarFromDetail($detail, $display);

        // ---- Social links (normalize)
        $rawSocials = $detail->social_media_link ?? [];
        if (!is_array($rawSocials)) {
            $decoded = json_decode($rawSocials, true);
            $rawSocials = is_array($decoded) ? $decoded : [];
        }
        $socials = [];
        foreach ($rawSocials as $key => $value) {
            if (!is_string($value)) continue;
            $url = trim($value);
            if ($url === '') continue;
            if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
            if (filter_var($url, FILTER_VALIDATE_URL)) $socials[$key] = $url;
        }

        // ---- Listings (Products/Services/Courses/Webinars/Teams)
        $productsRaw = DB::table('products')
            ->select([
                'products.id',
                'products.name',
                'products.product_type_id',
                'products.product_subcategory_id',
                'products.images',
                'products.country_id as product_country_id',
                'product_types.name as type_name',
            ])
            ->join('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->where('products.user_id', (string) $user->id) // products.user_id is varchar
            ->orderBy('products.created_at', 'desc')
            ->get();

        $fx = new CurrencyConverter();

        $products = $productsRaw->map(function ($p) use ($fx, $viewerCurrencyCode, $viewerCurrencySymbol) {
            // Cover (first image from JSON)
            $img = null;
            if (!empty($p->images)) {
                $arr = json_decode($p->images, true);
                if (is_array($arr) && count($arr)) {
                    $imgPath = ltrim($arr[0], '/');
                    $img = route('media.pass', ['path' => $imgPath]);
                }
            }

            // BASIC tier price (prefer product's country, else lowest)
            $priceRow = DB::table('product_pricings as pp')
                ->leftJoin('countries as co', 'co.id', '=', 'pp.country_id')
                ->where('pp.product_id', $p->id)
                ->where('pp.tier', 'basic')
                ->orderByRaw('(pp.country_id = ?) DESC', [$p->product_country_id ?? 0])
                ->orderBy('pp.price', 'asc')
                ->select('pp.price', 'co.currency as price_currency', 'co.currency_symbol as price_symbol')
                ->first();

            $price_n = null;
            $price_text = '—';
            if ($priceRow && isset($priceRow->price)) {
                $amt  = (float) $priceRow->price;
                $from = $priceRow->price_currency ?: null;
                if ($from && strtoupper($from) !== strtoupper($viewerCurrencyCode)) {
                    try { $amt = $fx->convert($amt, strtoupper($from), strtoupper($viewerCurrencyCode)); } catch (\Throwable $e) {}
                }
                $price_n = $amt;
                $price_text = $viewerCurrencySymbol . number_format($amt, (floor($amt) == $amt ? 0 : 2));
            }

            // Map product_types -> tabs
            $cat = match ($p->type_name) {
                'Digital Product' => 'Products',
                'Course'          => 'Courses',
                'Services'        => 'Services',
                'Webinar'         => 'Webinars',
                'Teams'           => 'Teams',
                default           => 'Products',
            };

            // No "sold" column → use views as a lightweight proxy
            $soldProxy = (int) DB::table('product_views')->where('product_id', $p->id)->count();

            return (object)[
                'id'         => $p->id,
                'title'      => $p->name,
                'cat'        => $cat,
                'img'        => $img,
                'price_n'    => $price_n,
                'price_text' => $price_text,
                'rating'     => null,
                'sold'       => $soldProxy,
            ];
        });

        // ---- Stats (Creator)
        $listingsCount = $products->count();
        $completedProjects = DB::table('service_milestones')
            ->join('service_orders', 'service_orders.id', '=', 'service_milestones.service_order_id')
            ->where('service_orders.seller_id', $user->id)
            ->where('service_milestones.status', 'released')
            ->count();

        $productIds   = $products->pluck('id')->all();
        $avgRating    = null;
        $ratingsCount = 0;
        if (!empty($productIds)) {
            $ratingsQuery = DB::table('product_reviews')->whereIn('product_id', $productIds);
            $avgRating    = (float) $ratingsQuery->avg('rating_number');
            $ratingsCount = (int)   $ratingsQuery->count();
        }
        $xpLevel = max(1, (int) ceil(($listingsCount + $completedProjects + $ratingsCount) / 5));

        // ---- Stats (Client)
        $ordersPlaced     = DB::table('service_orders')->where('buyer_id', $user->id)->count();
        $avgResponseHuman = $this->humanizeSeconds($user->avg_response_seconds);
        $reviewsGiven     = DB::table('product_reviews')->where('user_id', $user->id)->count();
        $verifiedBuyer    = !is_null($user->email_verified_at);

        // ---- Reviews
        // Creator view: reviews on THIS user's products
        $creatorReviews = collect();
        if (!empty($productIds)) {
            $creatorReviews = DB::table('product_reviews as pr')
                ->select(
                    'pr.*',
                    'p.name as product_name',
                    'u.first_name', 'u.last_name', 'u.unique_id',
                    'ad.profile_picture as reviewer_pic'
                )
                ->leftJoin('products as p', 'p.id', '=', 'pr.product_id')
                ->leftJoin('users as u', 'u.id', '=', 'pr.user_id') // reviewer user
                ->leftJoin('user_admin_another_details as ad', 'ad.user_admin_id', '=', 'u.unique_id')
                ->whereIn('pr.product_id', $productIds)
                ->orderBy('pr.created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function($r){
                    $reviewerName = trim(($r->first_name ?? '').' '.($r->last_name ?? '')) ?: ('User #'.$r->user_id);
                    $r->reviewer_name   = $reviewerName;
                    $r->reviewer_avatar = $this->avatarFromPathOrFallback($r->reviewer_pic, $reviewerName);
                    return $r;
                });
        }

        // Client view: reviews written BY this user (use current user's own avatar from another_details)
        $clientReviews = DB::table('product_reviews as pr')
            ->select('pr.*', 'p.name as product_name')
            ->leftJoin('products as p', 'p.id', '=', 'pr.product_id')
            ->where('pr.user_id', $user->id)
            ->orderBy('pr.created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function($r) use ($detail, $display) {
                $r->reviewer_name   = $display;
                $r->reviewer_avatar = $this->avatarFromPathOrFallback($detail->profile_picture ?? null, $display);
                return $r;
            });

        // ---- Teams (owner or accepted member)
        $ownedTeams = DB::table('user_admin_teams')
            ->where('team_owner_id', (string) $user->unique_id)
            ->get();

        $memberTeams = DB::table('team_members')
            ->where('status','accepted')
            ->where(function($q) use ($user) {
                $q->where('member_email', $user->email)
                  ->orWhere('member_id', (string) $user->unique_id);
            })
            ->get();

        $teamIds = $ownedTeams->pluck('id')->merge($memberTeams->pluck('team_id'))->unique()->values();

        $teams = collect();
        if ($teamIds->isNotEmpty()) {
            $teams = DB::table('user_admin_teams')
                ->whereIn('id', $teamIds)
                ->get()
                ->map(function ($t) {
                    $members = (int) DB::table('team_members')->where('team_id', $t->id)->count();
                    // Build link only for logged-in viewers; else point to login
                    $url = auth()->check()
                        ? route('user.admin.myteam.portfolio', ['team' => $t->id])
                        : (route('login') ?? '#');

                    return (object)[
                        'id'      => $t->id,
                        'name'    => $t->team_name,
                        'members' => $members,
                        'role'    => 'Contributor', // change to real pivot role if you store it
                        'url'     => $url,
                    ];
                });
        }

        // ---- “Hero/meta” placeholders (not in schema)
        $profileMeta = [
            'preferred_stack' => null,
            'languages'       => null,
            'work_style'      => null,
            'keywords'        => null,
            'availability'    => true,
            'badges'          => [],
            'tags'            => [],
            'location_text'   => $detail?->location ?: ($country?->name ?? null),
        ];

        return view('userdetails', [
            'user'                 => $user,
            'detail'               => $detail,
            'country'              => $country,
            'socials'              => $socials,
            'avatarUrl'            => $avatar,

            // viewer currency
            'viewerCurrencyCode'   => $viewerCurrencyCode,
            'viewerCurrencySymbol' => $viewerCurrencySymbol,

            // meta
            'profileMeta'          => $profileMeta,

            // stats (creator)
            'listingsCount'        => $listingsCount,
            'completedProjects'    => $completedProjects,
            'avgRating'            => $avgRating,
            'ratingsCount'         => $ratingsCount,
            'xpLevel'              => $xpLevel,

            // stats (client)
            'ordersPlaced'         => $ordersPlaced,
            'avgResponseHuman'     => $avgResponseHuman,
            'reviewsGiven'         => $reviewsGiven,
            'verifiedBuyer'        => $verifiedBuyer,

            // listings & reviews & teams
            'products'             => $products,
            'creatorReviews'       => $creatorReviews,
            'clientReviews'        => $clientReviews,
            'teams'                => $teams,
        ]);
    }

    private function humanizeSeconds($seconds)
    {
        if (empty($seconds)) return '—';
        $seconds = (int) $seconds;
        if ($seconds < 60) return $seconds.' sec';
        $mins = intdiv($seconds, 60);
        if ($mins < 60) return $mins.' min';
        $hrs = intdiv($mins, 60);
        $rem = $mins % 60;
        return $hrs.' hrs'.($rem ? ' '.$rem.' min' : '');
    }

    /**
     * Resolve viewer currency based on the LOGGED-IN user's country.
     * Fallback: USD/$.
     *
     * @return array [code, symbol]
     */
    private function resolveViewerCurrency(): array
    {
        $code = 'USD';
        $symbol = '$';
        try {
            $viewer = auth()->user();
            if ($viewer && method_exists($viewer, 'country')) {
                $viewer->loadMissing('country');
                if ($viewer->country) {
                    $code   = $viewer->country->currency        ?: $code;
                    $symbol = $viewer->country->currency_symbol ?: $symbol;
                }
            }
        } catch (\Throwable $e) {}
        return [strtoupper($code), $symbol];
    }

    /**
     * Build avatar URL from another_details.profile_picture (absolute URL or storage path),
     * else ui-avatars fallback using the $displayName.
     */
    private function buildAvatarFromDetail($anotherDetail, string $displayName): string
    {
        $pic = $anotherDetail->profile_picture ?? null;
        if (is_string($pic) && $pic !== '') {
            if (preg_match('~^https?://~i', $pic)) {
                return $pic;
            }
            $path = ltrim($pic, '/');
            return route('media.pass', ['path' => $path]);
        }
        return 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&background=0D8ABC&color=fff';
    }

    /**
     * Build avatar from raw path or fallback with display name.
     */
    private function avatarFromPathOrFallback(?string $path, string $displayName): string
    {
        if ($path && preg_match('~^https?://~i', $path)) {
            return $path;
        }
        if ($path) {
            $normalized = ltrim($path, '/');
            return route('media.pass', ['path' => $normalized]);
        }
        return 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&background=0D8ABC&color=fff';
    }
}

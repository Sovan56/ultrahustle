<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

use App\Models\ProductType;
use App\Models\ProductSubcategory;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\ProductFaq;
use App\Models\ProductBoost;
use App\Models\BoostRate;
use App\Models\ProductOrder;
use App\Models\ProductOrderStage;
use App\Services\Currency\CurrencyConverter;
use App\Models\MyOrder;
use App\Models\BoostPlan;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    /* ===========================================================
     | Pages
     * ========================================================== */
    public function page()
    {
        return view('UserAdmin.marketplace');
    }

    // NEW: Dedicated Orders page (Step 2)
    public function ordersPage()
    {
        return view('UserAdmin.orders');
    }

    /* FE helper: user currency/country */
    public function userMeta()
    {
        $u = $this->currentUser();
        abort_if(!$u, 403);

        $country = null;
        if (!empty($u->country_id)) {
            $country = Country::find($u->country_id);
        }
        if (!$country && !empty($u->country_code)) {
            $country = Country::where('code', strtoupper($u->country_code))->first();
        }

        $ccy = strtoupper((string)($u->currency ?? ''));
        if ($ccy === '') $ccy = strtoupper((string)($country->currency ?? 'USD'));
        if ($ccy === '') $ccy = 'USD';

        return response()->json([
            'country_id'   => $country?->id,
            'country_code' => $country?->code,
            'currency'     => $ccy,
        ]);
    }

    /* ===========================================================
     | Helpers
     * ========================================================== */
    private function currentUserId(): ?int
    {
        $id = session('user_id');
        return $id ? (int)$id : null;
    }

    private function currentUser(): ?\App\Models\User
    {
        $id = $this->currentUserId();
        return $id ? \App\Models\User::find($id) : null;
    }

    private function ownerIds(): array
    {
        $ids = [];
        $id  = $this->currentUserId();
        if ($id) $ids[] = $id;

        if ($id) {
            $unique = DB::table('users')->where('id', $id)->value('unique_id');
            if (!is_null($unique) && $unique !== '') {
                $ids[] = ctype_digit((string)$unique) ? (int)$unique : (string)$unique;
            }
        }
        return array_values(array_unique($ids, SORT_REGULAR));
    }

    private function normalizeArray($value): array
    {
        if (is_null($value)) return [];
        if (is_string($value)) {
            $d = json_decode($value, true);
            return is_array($d) ? array_values(array_filter($d)) : [];
        }
        if (is_array($value)) return array_values(array_filter($value));
        return [];
    }

    private function mediaUrl(?string $rel): string
    {
        $rel = ltrim((string)$rel, '/');
        return url('/media/' . $rel);
    }

    private function isDigitalType(?ProductType $t): bool
    {
        $slug = strtolower((string)($t->slug ?? ''));
        $name = strtolower((string)($t->name ?? ''));
        return str_contains($slug, 'digital') || str_contains($name, 'digital');
    }

    private function isServiceType(?ProductType $t): bool
    {
        $slug = strtolower((string)($t->slug ?? ''));
        $name = strtolower((string)($t->name ?? ''));
        return str_contains($slug, 'service') || str_contains($name, 'service');
    }

    private function isCoursesType(?ProductType $t): bool
    {
        $slug = strtolower((string)($t->slug ?? ''));
        $name = strtolower((string)($t->name ?? ''));
        return str_contains($slug, 'course') || str_contains($name, 'course');
    }

    private function assertSubcategoryForType(?int $subId, int $typeId): void
    {
        if (!$subId) return;
        $ok = ProductSubcategory::where('id', $subId)->where('product_type_id', $typeId)->exists();
        if (!$ok) abort(response()->json(['message' => 'Invalid subcategory for chosen type'], 422));
    }

    private function resolveUserCountryAndCurrency(): array
    {
        $u = $this->currentUser();
        abort_if(!$u, 403);

        $country = null;
        if (!empty($u->country_id)) {
            $country = Country::find($u->country_id);
        }
        if (!$country && !empty($u->country_code)) {
            $country = Country::where('code', strtoupper($u->country_code))->first();
        }
        abort_if(!$country, 422, 'Please set your country in profile to use boost.');

        $ccy = strtoupper((string)($u->currency ?? ''));
        if ($ccy === '') $ccy = strtoupper((string)($country->currency ?? 'USD'));
        if ($ccy === '') $ccy = 'USD';

        return ['user' => $u, 'country' => $country, 'currency_code' => $ccy];
    }

    /* ===========================================================
     | Lookups
     * ========================================================== */
    public function getProductTypes()
    {
        return ProductType::where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    public function getSubcategories(Request $req)
    {
        $req->validate(['type_id' => ['required', 'integer', 'exists:product_types,id']]);

        return ProductSubcategory::where('product_type_id', $req->type_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCountries()
    {
        return Country::orderBy('code')->get(['id', 'code', 'name', 'currency']);
    }

    /* ===========================================================
     | Products
     * ========================================================== */
    public function index(Request $req)
    {
        $ownerIds = $this->ownerIds();

        $search        = trim((string)$req->get('search', ''));
        $typeId        = $req->filled('type_id') ? (int)$req->get('type_id') : null;
        $subcategoryId = $req->filled('subcategory_id') ? (int)$req->get('subcategory_id') : null;

        $q = Product::whereIn('user_id', $ownerIds)
            ->with([
                'pricings',
                'orders' => fn($q) => $q->select(['id', 'product_id', 'status', 'amount']),
            ]);

        if ($typeId) {
            $q->where('product_type_id', $typeId);
        }
        if ($subcategoryId) {
            $q->where('product_subcategory_id', $subcategoryId);
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $q->latest()->get();

        $ccyByCountry = Country::pluck('currency', 'id');

        $data = $products->map(function (Product $p) use ($ccyByCountry) {
            $completedOrders = $p->orders->where('status', 'completed');
            $sales   = $completedOrders->count();
            $revenue = (float) $completedOrders->sum('amount');

            $tiers = $p->pricings->keyBy('tier');
            $base  = $tiers->get('basic') ?? $tiers->get('standard') ?? $tiers->get('premium');

            $code = 'USD';
            if ($base && $base->country_id && isset($ccyByCountry[$base->country_id])) {
                $code = strtoupper((string)$ccyByCountry[$base->country_id] ?: 'USD');
            }

            $priceValue = $base ? number_format((float)$base->price, 2) : '0.00';

            $gallery = is_array($p->images) ? $p->images : (json_decode($p->images, true) ?: []);
            $thumbUrl = !empty($gallery) ? $this->mediaUrl($gallery[0]) : asset('assets/img/users/user-4.png');

            // Provide badge class expected by the Blade
            $statusBadge = match ($p->status) {
                'published' => 'badge-success',
                'unlisted'  => 'badge-secondary',
                'draft'     => 'badge-warning',
                default     => 'badge-light'
            };

            return [
                'id'            => $p->id,
                'name'          => $p->name,
                'thumbnail_url' => $thumbUrl,
                'sales'         => $sales,
                'revenue'       => $code . ' ' . number_format($revenue, 2),
                'price'         => $code . ' ' . $priceValue,
                'status'        => $p->status,
                'status_badge'  => $statusBadge,
            ];
        })->values();

        return response()->json($data);
    }

    public function store(Request $req)
    {
        $ownerIds = $this->ownerIds();
        $uid      = $this->currentUserId() ?? ($ownerIds[0] ?? null);

        // ✅ Step 1: align with Blade field names (answer)
        $validated = $req->validate([
            'product_type_id'         => ['required', 'exists:product_types,id'],
            'product_subcategory_id'  => ['required', 'integer', 'exists:product_subcategories,id'],
            'name'                    => ['required', 'string', 'max:255'],
            'uses_ai'                 => ['nullable'],
            'has_team'                => ['nullable'],
            'description'             => ['nullable', 'string'],

            'images.*'                => ['nullable', 'image', 'max:12288', 'mimes:jpg,jpeg,png,webp,gif'],
            'files.*'                 => ['nullable', 'file', 'max:51200'],

            'urls.*'                  => ['nullable', 'url'],

            'pricings'                => ['nullable', 'array'],
            'pricings.*.tier'         => ['nullable', 'string', Rule::in(['basic', 'standard', 'premium'])],
            'pricings.*.price'        => ['nullable', 'numeric', 'min:0'],
            'pricings.*.delivery_days'=> ['nullable', 'integer', 'min:0'],
            'pricings.*.details'      => ['nullable', 'string'],

            'faqs'                    => ['nullable', 'array'],
            'faqs.*.title'            => ['nullable', 'string', 'max:255'],
            'faqs.*.questions'        => ['nullable', 'array'],
            'faqs.*.questions.*.question'   => ['nullable', 'string', 'max:255'],
            'faqs.*.questions.*.answer'     => ['nullable', 'string'], // ← changed
        ]);

        $type = ProductType::find($validated['product_type_id']);
        $this->assertSubcategoryForType($validated['product_subcategory_id'] ?? null, (int)$validated['product_type_id']);
        $isDigital = $this->isDigitalType($type);
        $isCourses = $this->isCoursesType($type);

        $imagesInput = $req->file('images');
        $filesInput  = $req->file('files');
        $urlsInput   = $this->normalizeArray($req->input('urls', []));
        $imagesCount = is_array($imagesInput)
            ? collect($imagesInput)->filter(fn($f) => $f && $f->isValid())->count()
            : ($imagesInput && $imagesInput->isValid() ? 1 : 0);
        $filesCount  = is_array($filesInput)
            ? collect($filesInput)->filter(fn($f) => $f && $f->isValid())->count()
            : ($filesInput && $filesInput->isValid() ? 1 : 0);
        $urlsCount   = count(array_filter($urlsInput, fn($u) => filter_var($u, FILTER_VALIDATE_URL)));

        if ($imagesCount < 1)  return response()->json(['message' => 'Please upload at least one image.'], 422);
        if ($isDigital && $filesCount < 1) return response()->json(['message' => 'Please upload at least one file for Digital Product.'], 422);
        if ($isCourses && $urlsCount < 1) return response()->json(['message' => 'Please add at least one URL for Courses.'], 422);

        ['country' => $country] = $this->resolveUserCountryAndCurrency();

        return DB::transaction(function () use ($validated, $imagesInput, $filesInput, $urlsInput, $uid, $country) {
            $p = Product::create([
                'user_id'                => (string)$uid,
                'product_type_id'        => $validated['product_type_id'],
                'product_subcategory_id' => $validated['product_subcategory_id'] ?? null,
                'country_id'             => $country->id,
                'name'                   => $validated['name'],
                'uses_ai'                => (bool)($validated['uses_ai'] ?? false),
                'has_team'               => (bool)($validated['has_team'] ?? false),
                'description'            => $validated['description'] ?? null,
                'images'                 => [],
                'files'                  => [],
                'urls'                   => [],
                'status'                 => 'published',
            ]);

            $gallery = [];
            if ($imagesInput) {
                $arr = is_array($imagesInput) ? $imagesInput : [$imagesInput];
                foreach ($arr as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $gallery[] = $file->store("products/{$p->id}/images", 'public');
                }
            }

            $files = [];
            if ($filesInput) {
                $arr = is_array($filesInput) ? $filesInput : [$filesInput];
                foreach ($arr as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $files[] = $file->store("products/{$p->id}/files", 'public');
                }
            }

            $urls = [];
            foreach ($urlsInput as $url) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }

            $p->images = $gallery;
            $p->files  = $files;
            $p->urls   = $urls;
            $p->save();

            $this->upsertPricings($p, $validated['pricings'] ?? []);
            $this->replaceFaqs($p, $validated['faqs'] ?? []); // ← will read "answer"

            return response()->json(['message' => 'Product created successfully']);
        });
    }

    public function show($id)
    {
        $ownerIds = $this->ownerIds();

        $p = Product::with(['pricings', 'faqs', 'type', 'subcategory'])
            ->whereIn('user_id', $ownerIds)
            ->findOrFail($id);

        $imgUrls  = array_map(fn($rel) => $this->mediaUrl($rel), $this->normalizeArray($p->images));
        $fileUrls = array_map(fn($rel) => $this->mediaUrl($rel), $this->normalizeArray($p->files));
        $urls     = $this->normalizeArray($p->urls);

        $map = $p->pricings()->get()->keyBy('tier')->map(function (ProductPricing $pr) {
            return [
                'country_id'    => $pr->country_id,
                'price'         => $pr->price,
                'delivery_days' => $pr->delivery_days,
                'details'       => $pr->details,
            ];
        })->toArray();

        $faqsRaw = $p->faqs()->orderBy('id')->get(['faq_heading', 'question', 'faq_answer']);
        $faqsGrouped = [];
        foreach ($faqsRaw as $f) {
            $h = $f->faq_heading ?? '';
            if (!isset($faqsGrouped[$h])) $faqsGrouped[$h] = [];
            // ✅ Return "answer" to match Blade
            $faqsGrouped[$h][] = ['question' => $f->question, 'answer' => $f->faq_answer];
        }
        $faqs = [];
        foreach ($faqsGrouped as $h => $qs) {
            $faqs[] = ['title' => $h, 'questions' => $qs];
        }

        return response()->json([
            'id'                     => $p->id,
            'product_type_id'        => $p->product_type_id,
            'product_subcategory_id' => $p->product_subcategory_id,
            'name'                   => $p->name,
            'uses_ai'                => (bool)$p->uses_ai,
            'has_team'               => (bool)$p->has_team,
            'description'            => $p->description,
            'images_urls'            => $imgUrls,
            'files_urls'             => $fileUrls,
            'urls'                   => $urls,
            'pricings_by_tier'       => $map,
            'faqs'                   => $faqs, // ← with "answer" keys
            'is_service'             => $this->isServiceType($p->type) ? 1 : 0,
        ]);
    }

    public function update($id, Request $req)
    {
        $ownerIds = $this->ownerIds();
        $p = Product::whereIn('user_id', $ownerIds)->findOrFail($id);

        // ✅ Step 1: align with Blade field names (answer)
        $validated = $req->validate([
            'product_type_id'         => ['required', 'exists:product_types,id'],
            'product_subcategory_id'  => ['required', 'integer', 'exists:product_subcategories,id'],
            'name'                    => ['required', 'string', 'max:255'],
            'uses_ai'                 => ['nullable'],
            'has_team'                => ['nullable'],
            'description'             => ['nullable', 'string'],

            'images.*'                => ['nullable', 'image', 'max:12288', 'mimes:jpg,jpeg,png,webp,gif'],
            'files.*'                 => ['nullable', 'file', 'max:51200'],

            'remove_images'           => ['nullable', 'array'],
            'remove_images.*'         => ['string'],
            'remove_files'            => ['nullable', 'array'],
            'remove_files.*'          => ['string'],

            'urls.*'                  => ['nullable', 'url'],
            'remove_urls'             => ['nullable', 'array'],
            'remove_urls.*'           => ['string'],

            'pricings'                => ['nullable', 'array'],
            'pricings.*.tier'         => ['nullable', 'string', Rule::in(['basic', 'standard', 'premium'])],
            'pricings.*.price'        => ['nullable', 'numeric', 'min:0'],
            'pricings.*.delivery_days'=> ['nullable', 'integer', 'min:0'],
            'pricings.*.details'      => ['nullable', 'string'],

            'faqs'                    => ['nullable', 'array'],
            'faqs.*.title'            => ['nullable', 'string', 'max:255'],
            'faqs.*.questions'        => ['nullable', 'array'],
            'faqs.*.questions.*.question'   => ['nullable', 'string', 'max:255'],
            'faqs.*.questions.*.answer'     => ['nullable', 'string'], // ← changed
        ]);

        $type = ProductType::find($validated['product_type_id']);
        $this->assertSubcategoryForType($validated['product_subcategory_id'] ?? null, (int)$validated['product_type_id']);
        $isDigital = $this->isDigitalType($type);
        $isCourses = $this->isCoursesType($type);

        return DB::transaction(function () use ($req, $validated, $p, $isDigital, $isCourses) {

            // IMAGES
            $existingImgs = $this->normalizeArray($p->images);
            $removeImgs   = $this->normalizeArray($req->input('remove_images', []));
            $removeImgs   = array_values(array_intersect($removeImgs, $existingImgs));
            foreach ($removeImgs as $rel) if (Storage::disk('public')->exists($rel)) Storage::disk('public')->delete($rel);
            $keptImgs     = array_values(array_diff($existingImgs, $removeImgs));

            $imagesInput = $req->file('images');
            if ($imagesInput) {
                $arr = is_array($imagesInput) ? $imagesInput : [$imagesInput];
                foreach ($arr as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $keptImgs[] = $file->store("products/{$p->id}/images", 'public');
                }
            }

            // FILES
            $existingFiles = $this->normalizeArray($p->files);
            $removeFiles   = $this->normalizeArray($req->input('remove_files', []));
            $removeFiles   = array_values(array_intersect($removeFiles, $existingFiles));
            foreach ($removeFiles as $rel) if (Storage::disk('public')->exists($rel)) Storage::disk('public')->delete($rel);
            $keptFiles     = array_values(array_diff($existingFiles, $removeFiles));

            $filesInput = $req->file('files');
            if ($filesInput) {
                $arr = is_array($filesInput) ? $filesInput : [$filesInput];
                foreach ($arr as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $keptFiles[] = $file->store("products/{$p->id}/files", 'public');
                }
            }

            // URLS
            $existingUrls = $this->normalizeArray($p->urls);
            $removeUrls   = $this->normalizeArray($req->input('remove_urls', []));
            $removeUrls   = array_values(array_intersect($removeUrls, $existingUrls));
            $keptUrls     = array_values(array_diff($existingUrls, $removeUrls));

            $urlsInput = $this->normalizeArray($req->input('urls', []));
            $newUrls   = [];
            foreach ($urlsInput as $url) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $newUrls[] = $url;
                }
            }
            $keptUrls = array_merge($keptUrls, $newUrls);

            if (count($keptImgs) < 1)  return response()->json(['message' => 'Please upload at least one image.'], 422);
            if ($isDigital && count($keptFiles) < 1) return response()->json(['message' => 'Please upload at least one file for Digital Product.'], 422);
            if ($isCourses && count($keptUrls) < 1) return response()->json(['message' => 'Please add at least one URL for Courses.'], 422);

            $p->update([
                'product_type_id'        => $validated['product_type_id'],
                'product_subcategory_id' => $validated['product_subcategory_id'] ?? null,
                'name'                   => $validated['name'],
                'uses_ai'                => (bool)($validated['uses_ai'] ?? false),
                'has_team'               => (bool)($validated['has_team'] ?? false),
                'description'            => $validated['description'] ?? null,
                'images'                 => array_values($keptImgs),
                'files'                  => array_values($keptFiles),
                'urls'                   => array_values($keptUrls),
            ]);

            $this->upsertPricings($p, $validated['pricings'] ?? []);
            $this->replaceFaqs($p, $validated['faqs'] ?? []); // ← will read "answer"

            return response()->json(['message' => 'Product updated successfully']);
        });
    }

    public function destroy($id)
    {
        $ownerIds = $this->ownerIds();
        $p = Product::whereIn('user_id', $ownerIds)->findOrFail($id);

        DB::transaction(function () use ($p) {
            foreach ($this->normalizeArray($p->images) as $rel) Storage::disk('public')->delete($rel);
            foreach ($this->normalizeArray($p->files)  as $rel) Storage::disk('public')->delete($rel);
            Storage::disk('public')->deleteDirectory("products/{$p->id}");
            $p->faqs()->delete();
            $p->pricings()->delete();
            $p->boosts()->delete();
            $p->delete();
        });

        return response()->json(['message' => 'Product deleted']);
    }

    public function duplicate($id)
    {
        $ownerIds = $this->ownerIds();
        $p = Product::whereIn('user_id', $ownerIds)->with(['pricings', 'faqs'])->findOrFail($id);

        return DB::transaction(function () use ($p) {
            $copy = $p->replicate(['images', 'files', 'urls', 'is_boosted', 'status']);
            $copy->status = 'unlisted';
            $copy->images = [];
            $copy->files  = [];
            $copy->urls   = [];
            $copy->save();

            $newImages = [];
            foreach ($this->normalizeArray($p->images) as $rel) {
                if (!Storage::disk('public')->exists($rel)) continue;
                $ext = pathinfo($rel, PATHINFO_EXTENSION);
                $dst = "products/{$copy->id}/images/img_" . Str::random(8) . ($ext ? ".$ext" : '');
                Storage::disk('public')->copy($rel, $dst);
                $newImages[] = $dst;
            }
            $newFiles = [];
            foreach ($this->normalizeArray($p->files) as $rel) {
                if (!Storage::disk('public')->exists($rel)) continue;
                $ext = pathinfo($rel, PATHINFO_EXTENSION);
                $dst = "products/{$copy->id}/files/file_" . Str::random(8) . ($ext ? ".$ext" : '');
                Storage::disk('public')->copy($rel, $dst);
                $newFiles[] = $dst;
            }
            $newUrls = $this->normalizeArray($p->urls);
            $copy->images = $newImages;
            $copy->files  = $newFiles;
            $copy->urls   = $newUrls;
            $copy->save();

            foreach ($p->pricings as $pr) {
                $nr = $pr->replicate();
                $nr->product_id = $copy->id;
                $nr->save();
            }
            foreach ($p->faqs as $fq) {
                $nf = $fq->replicate();
                $nf->product_id = $copy->id;
                $nf->save();
            }

            return response()->json(['message' => 'Product duplicated (unlisted)']);
        });
    }

    public function togglePublish($id)
    {
        $ownerIds = $this->ownerIds();
        $p = Product::whereIn('user_id', $ownerIds)->findOrFail($id);
        $p->status = $p->status === 'published' ? 'unlisted' : 'published';
        $p->save();

        return response()->json([
            'message' => $p->status === 'published' ? 'Published' : 'Unpublished',
            'status'  => $p->status
        ]);
    }

    private function getUserWallet(\App\Models\User $u): array
    {
        $ccy = null;
        if (Schema::hasColumn('users', 'wallet_currency')) {
            $ccy = strtoupper((string)($u->wallet_currency ?? ''));
        }
        if (!$ccy) {
            $ccy = strtoupper((string)($u->currency ?? 'USD'));
        }
        if ($ccy === '') $ccy = 'USD';

        $bal = 0.0;
        if (Schema::hasColumn('users', 'wallet_balance'))       $bal = (float)($u->wallet_balance ?? 0);
        elseif (Schema::hasColumn('users', 'wallet'))           $bal = (float)($u->wallet ?? 0);
        elseif (Schema::hasColumn('users', 'balance'))          $bal = (float)($u->balance ?? 0);
        elseif (Schema::hasColumn('users', 'funds'))            $bal = (float)($u->funds ?? 0);

        return ['currency' => $ccy, 'balance' => max(0, $bal)];
    }

    private function setUserWallet(\App\Models\User $u, float $newBalance): void
    {
        $newBalance = round($newBalance, 2);
        $updates = [];
        if (Schema::hasColumn('users', 'wallet_balance'))       $updates['wallet_balance'] = $newBalance;
        elseif (Schema::hasColumn('users', 'wallet'))           $updates['wallet']         = $newBalance;
        elseif (Schema::hasColumn('users', 'balance'))          $updates['balance']        = $newBalance;
        elseif (Schema::hasColumn('users', 'funds'))            $updates['funds']          = $newBalance;

        if ($updates) {
            DB::table('users')->where('id', $u->id)->update($updates);
        }
    }

    /* ===========================================================
     | Orders (APIs stay the same)
     * ========================================================== */
    public function orders(Request $req)
    {
        $ownerIds = $this->ownerIds();

        $q = ProductOrder::query()
            ->with(['product.type', 'product.subcategory'])
            ->whereHas('product', fn($p) => $p->whereIn('user_id', $ownerIds));

        if ($req->filled('type_id')) {
            $q->whereHas('product', fn($p) => $p->where('product_type_id', $req->type_id));
        }
        if ($req->filled('subcategory_id')) {
            $q->whereHas('product', fn($p) => $p->where('product_subcategory_id', $req->subcategory_id));
        }
        if ($req->filled('search')) {
            $s = trim($req->search);
            $q->whereHas('product', fn($p) => $p->where('name', 'like', "%{$s}%"));
        }

        $orders = $q->latest()->get();

        $data = $orders->map(function (ProductOrder $o) {
            return [
                'id'            => $o->id,
                'product_id'    => $o->product_id,
                'product_name'  => $o->product?->name,
                'type'          => $o->product?->type?->name,
                'subcategory'   => $o->product?->subcategory?->name,
                'buyer_name'    => $o->buyer_name,
                'amount'        => $this->formatOrderAmount($o),
                'status'        => $o->status,
                'is_completed'  => $o->status === 'completed',
                'is_canceled'   => $o->status === 'canceled',
                'created_at'    => optional($o->created_at)->format('Y-m-d H:i'),
            ];
        })->values();

        return response()->json($data);
    }

    private function formatOrderAmount(ProductOrder $o): string
    {
        $code = null;
        if (isset($o->currency_code) && $o->currency_code) {
            $code = strtoupper($o->currency_code);
        } elseif (isset($o->currency) && is_string($o->currency) && strlen($o->currency) <= 3) {
            $code = strtoupper($o->currency);
        }
        $amt = number_format((float)$o->amount, 2);
        return $code ? ($code . ' ' . $amt) : $amt;
    }

    public function ordersSummary()
    {
        $ownerIds = $this->ownerIds();
        $base = ProductOrder::whereHas('product', fn($p) => $p->whereIn('user_id', $ownerIds));

        return response()->json([
            'new'         => (clone $base)->where('status', 'new')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed'   => (clone $base)->where('status', 'completed')->count(),
            'canceled'    => (clone $base)->where('status', 'canceled')->count(),
        ]);
    }

public function getOrder($id)
{
    // Load first (no ownership filter) so we can return clean JSON errors
    $o = ProductOrder::with(['product.type'])->find($id);
    if (!$o) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // Ownership: product must belong to current user (kept from your logic)
    $ownerIds = $this->ownerIds();
    $productOwner = optional($o->product)->user_id;

    $ownerIdsStr = array_map('strval', $ownerIds);
    $isOwner = in_array($productOwner, $ownerIds, true) || in_array((string)$productOwner, $ownerIdsStr, true);

    if (!$isOwner) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    // ALWAYS load stages from product_order_stages ordered by position
    $stages = $o->stages()->get(['title', 'notes', 'status', 'position']);

    return response()->json([
        'id'           => $o->id,
        'status'       => $o->status,
        'is_completed' => $o->status === 'completed' ? 1 : 0,
        'is_canceled'  => $o->status === 'canceled' ? 1 : 0,
        'is_service'   => $this->isServiceType(optional($o->product)->type) ? 1 : 0,
        'stages'       => $stages,
    ]);
}




    public function startOrder($id)
    {
        $this->getOrder($id);
        return response()->json(['message' => 'OK']);
    }

    public function saveOrderStages($id, \Illuminate\Http\Request $req)
{
    // Find order & ownership check (like getOrder)
    $o = ProductOrder::with(['product.type'])->find($id);
    if (!$o) {
        return response()->json(['message' => 'Order not found'], 404);
    }
    $ownerIds = $this->ownerIds();
    $productOwner = optional($o->product)->user_id;
    $ownerIdsStr = array_map('strval', $ownerIds);
    $isOwner = in_array($productOwner, $ownerIds, true) || in_array((string)$productOwner, $ownerIdsStr, true);
    if (!$isOwner) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $data = $req->validate([
        'stages'                 => ['required', 'array', 'min:1'],
        'stages.*.title'         => ['required', 'string', 'max:255'],
        'stages.*.notes'         => ['nullable', 'string'],
        'stages.*.status'        => ['required', \Illuminate\Validation\Rule::in(['pending', 'in_progress', 'done'])],
        'set_in_progress_if_new' => ['nullable'],
        'mark_complete_hint'     => ['nullable'],
        'delivery_files.*'       => ['nullable', 'file', 'max:20480'],
    ]);

    \DB::transaction(function () use ($o, $data, $req) {
        // Replace stages (ensure 'position' is set from index)
        $o->stages()->delete();

        foreach ($data['stages'] as $i => $st) {
            ProductOrderStage::create([
                'order_id' => $o->id,
                'position' => (int) $i,
                'title'    => $st['title'],
                'notes'    => $st['notes'] ?? '',
                'status'   => $st['status'],
            ]);
        }

        if ($req->boolean('set_in_progress_if_new') && $o->status === 'new') {
            if ($o->stages()->count() > 0) {
                $o->status = 'in_progress';
            }
        }

        if ($req->boolean('mark_complete_hint')) {
            $left = $o->stages()->where('status', '!=', 'done')->count();
            if ($left === 0) {
                $o->status = 'completed';
            }
        }

        if ($this->isServiceType(optional($o->product)->type) && $req->hasFile('delivery_files')) {
            foreach ($req->file('delivery_files') as $file) {
                if ($file && $file->isValid()) {
                    $file->store("orders/{$o->id}/deliveries", 'public');
                }
            }
        }

        $o->save();
    });

    return response()->json(['message' => 'Stages saved']);
}
    public function cancelOrder($id)
    {
        $ownerIds = $this->ownerIds();
        $o = ProductOrder::whereHas('product', fn($p) => $p->whereIn('user_id', $ownerIds))->findOrFail($id);

        if ($o->status !== 'new') {
            return response()->json(['message' => 'Only NEW orders can be canceled'], 422);
        }

        $o->status = 'canceled';
        $o->save();

        return response()->json(['message' => 'Order canceled']);
    }

    /* ===========================================================
     | Save helpers
     * ========================================================== */
    private function upsertPricings(Product $p, array $pricings): void
    {
        $p->pricings()->delete();

        foreach (['basic', 'standard', 'premium'] as $tier) {
            $row = collect($pricings)->first(fn($r) => ($r['tier'] ?? null) === $tier);
            if (!$row) continue;

            ProductPricing::create([
                'product_id'    => $p->id,
                'tier'          => $tier,
                'country_id'    => $p->country_id,
                'price'         => $row['price'] ?? 0,
                'delivery_days' => $row['delivery_days'] ?? 0,
                'details'       => $row['details'] ?? '',
            ]);
        }
    }

    private function replaceFaqs(Product $p, array $faqs): void
    {
        // Accept both keys for backward-compat (answer or faq_answer), but store to faq_answer
        $p->faqs()->delete();
        foreach ($faqs as $head) {
            $title = trim($head['title'] ?? '');
            foreach ($head['questions'] ?? [] as $qa) {
                $q = trim($qa['question'] ?? '');
                $a = trim($qa['answer'] ?? ($qa['faq_answer'] ?? '')); // ← robust
                if ($q === '' || $a === '') continue;
                ProductFaq::create([
                    'product_id'   => $p->id,
                    'faq_heading'  => $title,
                    'question'     => $q,
                    'faq_answer'   => $a,
                ]);
            }
        }
    }

    /* ===========================================================
     | Boosts (unchanged)
     * ========================================================== */
    public function boostPage()
    {
        return view('UserAdmin.boosts');
    }

    public function boostPlans(Request $request)
    {
        $user = $this->currentUser();
        abort_if(!$user, 403);

        $userCcy = $this->getUserCurrencyCode($user);
        /** @var CurrencyConverter $fx */
        $fx = app(CurrencyConverter::class);

        $plans = BoostPlan::query()
            ->where('is_active', true)
            ->orderBy('price_usd')
            ->get(['id', 'name', 'days', 'price_usd', 'description']);

        $data = $plans->map(function ($p) use ($fx, $userCcy) {
            $local = $fx->convert((float)$p->price_usd, 'USD', $userCcy);
            return [
                'id'           => $p->id,
                'name'         => $p->name,
                'days'         => (int)$p->days,
                'price_local'  => round($local, 2),
                'currency'     => $userCcy,
                'description'  => (string)$p->description,
            ];
        });

        return response()->json($data->values());
    }

    public function boostQuote(Request $request)
    {
        $user = $this->currentUser();
        abort_if(!$user, 403);

        $planId = (int)$request->get('plan_id');
        abort_if(!$planId, 422, 'Select a plan');

        $plan = BoostPlan::where('is_active', true)->findOrFail($planId);

        $userCcy = $this->getUserCurrencyCode($user);
        /** @var CurrencyConverter $fx */
        $fx = app(CurrencyConverter::class);

        $totalLocal = $fx->convert((float)$plan->price_usd, 'USD', $userCcy);
        $dailyLocal = $plan->days > 0 ? round($totalLocal / $plan->days, 2) : $totalLocal;

        return response()->json([
            'currency'     => $userCcy,
            'days'         => (int)$plan->days,
            'total_local'  => round($totalLocal, 2),
            'daily_local'  => round($dailyLocal, 2),
        ]);
    }

    public function toggleBoost(Request $request, int $id)
    {
        $user = $this->currentUser();
        abort_if(!$user, 403);

        $product = Product::where('user_id', $user->id)->findOrFail($id);

        $planId = (int) $request->get('plan_id');
        abort_if(!$planId, 422, 'Choose a pricing plan');

        $plan = BoostPlan::where('is_active', true)->findOrFail($planId);

        $days     = (int) $plan->days;
        $totalUsd = (float) $plan->price_usd;
        abort_if($days < 1, 422, 'Invalid plan duration');

        $wallet  = $this->getUserWallet($user);
        $wCcy    = $wallet['currency'] ?? 'USD';
        $balance = (float)($wallet['balance'] ?? 0);

        /** @var \App\Services\Currency\CurrencyConverter $fx */
        $fx = app(\App\Services\Currency\CurrencyConverter::class);
        $requiredLocal = $wCcy === 'USD' ? round($totalUsd, 2) : $fx->convert($totalUsd, 'USD', $wCcy);

        if ($balance + 1e-6 < $requiredLocal) {
            return response()->json([
                'code'            => 'INSUFFICIENT_FUNDS',
                'message'         => 'Insufficient balance',
                'wallet_currency' => $wCcy,
                'balance'         => round($balance, 2),
                'required'        => round($requiredLocal, 2),
                'topup_url'       => route('user.admin.wallet'),
            ], 402);
        }

        ['country' => $country] = $this->resolveUserCountryAndCurrency();

        DB::transaction(function () use ($user, $product, $days, $totalUsd, $balance, $requiredLocal, $country) {
            $this->setUserWallet($user, max(0, $balance - $requiredLocal));

            $now = now();

            \App\Models\ProductBoost::create([
                'product_id' => $product->id,
                'user_id'    => $user->id,
                'country_id' => $country->id,
                'amount'     => round($totalUsd, 2), // stored canonical USD
                'days'       => $days,
                'start_at'   => $now,
                'end_at'     => (clone $now)->addDays($days)->endOfDay(),
                'is_active'  => 1,
            ]);
        });

        return response()->json(['message' => 'Boost started']);
    }

    public function activeBoosts()
    {
        $user = $this->currentUser();
        abort_if(!$user, 403);

        $now = now();
        $rows = \App\Models\ProductBoost::with('product:id,name,images')
            ->where('user_id', $user->id)
            ->where('is_active', 1)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->orderByDesc('start_at')
            ->get();

        $countryCodes = \App\Models\Country::whereIn('id', $rows->pluck('country_id')->filter()->unique())
            ->pluck('code', 'id');

        $list = $rows->map(function (\App\Models\ProductBoost $b) use ($countryCodes) {
            $imgs = is_array($b->product->images) ? $b->product->images : (json_decode($b->product->images, true) ?: []);
            $thumb = !empty($imgs) ? $this->mediaUrl($imgs[0]) : null;

            return [
                'product_id'    => $b->product_id,
                'product_name'  => $b->product?->name ?? '—',
                'product_thumb' => $thumb,
                'country'       => $countryCodes[$b->country_id] ?? '-',
                'days'          => (int) $b->days,
                'start_at'      => optional($b->start_at)->format('Y-m-d H:i'),
                'end_at'        => optional($b->end_at)->format('Y-m-d H:i'),
                'amount_usd'    => number_format((float)$b->amount, 2),
            ];
        })->values();

        return response()->json($list);
    }

    private function getUserCurrencyCode($u): string
    {
        $country = null;
        if (!empty($u->country_id)) $country = Country::find($u->country_id);
        elseif (!empty($u->country_code)) $country = Country::where('code', strtoupper($u->country_code))->first();

        $ccy = strtoupper((string)($u->currency ?? ''));
        if ($ccy === '') $ccy = strtoupper((string)($country->currency ?? 'USD'));
        if ($ccy === '') $ccy = 'USD';
        return $ccy;
    }





     public function myOrdersPage()
    {
        return view('UserAdmin.MyOrder');
    }

    /** JSON list */
    public function myOrders(Request $request)
    {
        $buyerId = Auth::id() ?: (int) session('user_id');
        abort_unless($buyerId, 403);

        $q = DB::table('my_orders as mo')
            ->leftJoin('products as p', 'p.id', '=', 'mo.product_id')
            ->leftJoin('product_types as pt', 'pt.id', '=', 'mo.product_type_id')
            ->where('mo.buyer_id', $buyerId)
            ->select([
                'mo.id',
                'mo.status',
                'mo.currency',
                'mo.total_amount',
                'mo.delivery_files',      // fallback only
                'mo.course_urls',
                'mo.created_at',
                'p.name as product_name',
                'p.files as product_files', // <-- prefer these
                DB::raw('LOWER(COALESCE(pt.slug, pt.name)) as type_slug'),
            ])
            ->orderByDesc('mo.id');

        if ($request->filled('type')) {
            $type  = Str::of($request->get('type'))->lower()->toString();
            $match = $type === 'service' ? 'services' : $type;
            $q->whereRaw('LOWER(COALESCE(pt.slug, pt.name)) = ?', [$match]);
        }
        if ($request->filled('status')) {
            $status = Str::of($request->get('status'))->lower()->toString();
            $q->whereRaw('LOWER(mo.status) = ?', [$status]);
        }

        $perPage   = (int) $request->integer('per_page') ?: 10;
        $page      = (int) $request->integer('page') ?: 1;
        $paginator = $q->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            // 1) Prefer products.files (JSON of relative paths)  2) Fallback to my_orders.delivery_files
            $productFiles = json_decode($row->product_files ?? '[]', true) ?: [];
            $orderFiles   = json_decode($row->delivery_files ?? '[]', true) ?: [];
            $sourceFiles  = !empty($productFiles) ? array_values($productFiles) : array_values($orderFiles);

            // Build secure download links using order id + index
            $files = [];
            foreach ($sourceFiles as $i => $path) {
                $files[] = [
                    'url'  => route('downloads.order', ['order' => $row->id, 'index' => $i]),
                    'name' => is_string($path) ? basename($path) : ('File '.($i+1)),
                ];
            }

            return [
                'id'           => (int)  $row->id,
                'status'       => (string)$row->status,
                'currency'     => (string)$row->currency,
                'total_amount' => (float) $row->total_amount,
                'product'      => [
                    'name' => (string) ($row->product_name ?? 'Product'),
                    'type' => (string) ($row->type_slug ?? null),
                ],
                'delivery_files' => $files, // now based on products.files first
                'course_urls'    => array_values(json_decode($row->course_urls ?? '[]', true) ?: []),
                'created_at'     => $row->created_at,
            ];
        });

        return response()->json([
            'data'         => $items,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ]);
    }

    /** Single order JSON */
    public function myOrderShow(int $id)
    {
        $buyerId = Auth::id() ?: (int) session('user_id');
        abort_unless($buyerId, 403);

        $row = DB::table('my_orders as mo')
            ->leftJoin('products as p', 'p.id', '=', 'mo.product_id')
            ->leftJoin('product_types as pt', 'pt.id', '=', 'mo.product_type_id')
            ->where('mo.id', $id)
            ->where('mo.buyer_id', $buyerId)
            ->select([
                'mo.id','mo.status','mo.currency','mo.total_amount',
                'mo.delivery_files','mo.course_urls','mo.created_at',
                'p.name as product_name',
                'p.files as product_files', // <-- prefer
                DB::raw('LOWER(COALESCE(pt.slug, pt.name)) as type_slug'),
            ])
            ->firstOrFail();

        $productFiles = json_decode($row->product_files ?? '[]', true) ?: [];
        $orderFiles   = json_decode($row->delivery_files ?? '[]', true) ?: [];
        $sourceFiles  = !empty($productFiles) ? array_values($productFiles) : array_values($orderFiles);

        $files = [];
        foreach ($sourceFiles as $i => $path) {
            $files[] = [
                'url'  => route('downloads.order', ['order' => $row->id, 'index' => $i]),
                'name' => is_string($path) ? basename($path) : ('File '.($i+1)),
            ];
        }

        return response()->json([
            'id'           => (int)  $row->id,
            'status'       => (string)$row->status,
            'currency'     => (string)$row->currency,
            'total_amount' => (float) $row->total_amount,
            'product'      => [
                'name' => (string) ($row->product_name ?? 'Product'),
                'type' => (string) ($row->type_slug ?? null),
            ],
            'delivery_files' => $files,
            'course_urls'    => array_values(json_decode($row->course_urls ?? '[]', true) ?: []),
            'created_at'     => $row->created_at,
        ]);
    }

    /** Buyer approves delivery */
    public function approveOrder(int $id, Request $request)
    {
        $buyerId = Auth::id() ?: (int) session('user_id');
        abort_unless($buyerId, 403);

        $updated = DB::table('my_orders')
            ->where('id', $id)
            ->where('buyer_id', $buyerId)
            ->update([
                'status'     => 'completed',
                'updated_at' => now(),
            ]);

        abort_unless($updated, 404);

        return response()->json(['ok' => true, 'message' => 'Order approved.']);
    }


}

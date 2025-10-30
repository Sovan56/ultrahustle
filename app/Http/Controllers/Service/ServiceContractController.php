<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreMilestoneSubmissionRequest;
// We keep the import, but we'll validate inline to avoid a pre-validation redirect loop
use App\Http\Requests\Service\StoreServiceOrderRequest;
use App\Http\Requests\Service\UpdateServiceOrderRequest;
use App\Mail\Service\ContractReupdatedMail;
use App\Mail\Service\ContractSentMail;
use App\Mail\Service\MilestoneSubmittedMail;
use App\Models\Product;
use App\Models\ServiceMilestone;
use App\Models\ServiceMilestoneSubmission;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Conversation;
use App\Models\ProductType;
use App\Models\PlatformSetting;
use App\Models\ProductSubcategory;

class ServiceContractController extends Controller
{
    public function index(Request $request)
{
    $me = Auth::id();
    abort_unless($me, 403);

    $orders = ServiceOrder::withCount([
            'milestones',
            'milestones as milestones_released_count' => function ($q) {
                $q->where('status', 'released'); // treat "released" as completed
            },
        ])
        ->with([
            'buyer:id,first_name,last_name,email,country_id',
            'buyer.country:id,currency,currency_symbol',
            'seller:id,first_name,last_name,email,country_id',
            'seller.country:id,currency,currency_symbol',
        ])
        ->forUser($me)
        ->latest('id')
        ->paginate(20);

    return view('service.contracts.index', compact('orders'));
}

    public function create(Request $request)
    {
        $sellerId = Auth::id();
        abort_unless($sellerId, 403);

        // 1) Determine buyer automatically
        $buyerId = (int) $request->query('buyer', 0);
        $conversationId = (int) $request->query('conversation_id', 0);

        if (!$buyerId && $conversationId > 0) {
            $conv = Conversation::find($conversationId);
            abort_unless($conv && $conv->hasUser($sellerId), 404);
            // use model helper that exists in your codebase
            $otherId = $conv->otherUserId($sellerId);
            abort_if(!$otherId || (int)$otherId === (int)$sellerId, 422, 'Could not infer buyer.');
            $buyerId = (int) $otherId;
        }

        abort_if(!$buyerId, 422, 'Buyer could not be inferred.');

        // 2) Only "Services" subcategories
        $servicesType = ProductType::where('slug', 'services')
            ->orWhere('name', 'Services')
            ->first();

        abort_unless($servicesType, 500, 'Service type not found');

        $serviceSubcategories = ProductSubcategory::where('product_type_id', $servicesType->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        // 3) If a subcategory is preselected, preload seller’s products
        $selectedSubcat = (int) $request->query('subcategory_id', 0);
        $products = collect();
        if ($selectedSubcat > 0) {
            $products = Product::where('user_id', $sellerId)
                ->where('product_subcategory_id', $selectedSubcat) // correct column in DB for products
                ->orderBy('name')
                ->get(['id', 'name', 'product_subcategory_id']);
        }

        return view('service.contracts.create_step1', [
            'buyerId'              => $buyerId,
            'conversationId'       => $conversationId ?: null,
            'serviceSubcategories' => $serviceSubcategories,
            'selectedSubcat'       => $selectedSubcat ?: null,
            'products'             => $products,
        ]);
    }

    /**
     * JSON list of the logged-in seller’s products for a given subcategory (service only).
     */
    public function myProductsBySubcategory(Request $request)
    {
        $sellerId = Auth::id();
        abort_unless($sellerId, 403);

        $request->validate([
            'subcategory_id' => ['required', 'integer', 'exists:product_subcategories,id'],
        ]);
        $subcategoryId = (int) $request->query('subcategory_id');

        $items = Product::where('user_id', $sellerId)
            ->where('product_subcategory_id', $subcategoryId) // correct column
            ->orderBy('name')
            ->get(['id', 'name', 'product_subcategory_id']);

        return response()->json(['ok' => true, 'data' => $items]);
    }

    /**
     * Store Step-1 (create draft order). We validate inline here to avoid a FormRequest
     * pre-validation mismatch bouncing back to the same page.
     */
    public function store(Request $request)
    {
        $sellerId = Auth::id();
        abort_unless($sellerId, 403);

        // Accept either 'subcategory_id' (preferred) or legacy 'product_subcategory_id'
        $subcategory = $request->input('subcategory_id', $request->input('product_subcategory_id'));

        // ✅ Validate exactly the fields your form sends (kept your keys)
        $validated = $request->validate([
            'buyer_id'        => ['required', 'integer', 'exists:users,id'],
            'product_id'      => ['required', 'integer', 'exists:products,id'],
            'terms'           => ['required', 'string', 'max:10000'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            // subcategory validated explicitly below (so legacy/new names both work)
        ], [], [
            'buyer_id'   => 'buyer',
            'product_id' => 'product',
            'terms'      => 'terms',
        ]);

        // Normalize values
        $buyerId        = (int) $validated['buyer_id'];
        $productId      = (int) $validated['product_id'];
        $conversationId = (int) ($validated['conversation_id'] ?? 0);
        $terms          = (string) $validated['terms'];
        $subcategoryId  = (int) $subcategory;

        // ✅ Validate subcategory explicitly (works for both field names)
        abort_if($subcategoryId <= 0, 422, 'Please choose a service subcategory.');
        abort_unless(ProductSubcategory::whereKey($subcategoryId)->exists(), 422, 'Invalid subcategory.');

        // ❌ Guard: cannot contract with self
        if ($buyerId === $sellerId) {
            return back()->withErrors(['buyer_id' => 'You cannot create a contract with yourself'])->withInput();
        }

        // ✅ Guard: product must belong to this seller AND match the chosen subcategory
        $validProduct = Product::where('id', $productId)
            ->where('user_id', $sellerId)
            ->where('product_subcategory_id', $subcategoryId)
            ->exists();
        abort_unless($validProduct, 422, 'Invalid product selection.');

        // 🧾 Determine SELLER currency/symbol from seller->country
        $seller = \App\Models\User::with('country:id,currency,currency_symbol')->findOrFail($sellerId);
        $sellerCurrencyCode   = strtoupper($seller->country->currency ?? 'USD');
        $sellerCurrencySymbol = $seller->country->currency_symbol ?? '$';

        // 🧾 Create draft order in SELLER currency
        $order = ServiceOrder::create([
            'buyer_id'         => $buyerId,
            'seller_id'        => $sellerId,
            'product_id'       => $productId,
            // FIX #1: service_orders has `subcategory_id` (not product_subcategory_id)
            'subcategory_id'   => $subcategoryId,
            'terms'            => $terms,
            'meta'             => [
                'origin_product_id' => $productId,
                'from'              => 'chat_or_product_page',
                'conversation_id'   => $conversationId ?: null,
            ],
            'currency_code'    => $sellerCurrencyCode,
            'currency_symbol'  => $sellerCurrencySymbol,
            'status'           => 'draft',
            'subtotal'         => 0,
            // FIX #2: these columns are NOT NULL in your DB, initialize to 0
            'platform_fee_percent' => 0,
            'platform_fee_amount'  => 0,
            'gst_percent'          => 0,
            'gst_amount'           => 0,
            'total_payable'        => 0,
            'hold_amount'          => 0,
            'released_amount'      => 0,
        ]);

        // ✅ Move to Step 2 (milestones builder)
        return redirect()
            ->route('service.contracts.build', $order->id)
            ->with('success', 'Contract draft created. Add milestones next.');
    }

    public function build(ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && $order->seller_id === $me, 403);

        if (!in_array($order->status, ['draft', 'reupdated', 'sent'], true)) {
            return redirect()->route('service.contracts.show', $order->id);
        }

        $order->load('milestones');

        return view('service.contracts.create_step2', compact('order'));
    }

    public function storeMilestones(UpdateServiceOrderRequest $request, ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && $order->seller_id === $me, 403);

        if (! $order->isEditableBySeller($me)) {
            return back()->withErrors(['milestones' => 'Order is not editable now.']);
        }

        $rows = $request->validated()['milestones'];

        DB::transaction(function () use ($order, $rows) {
            // Re-compose milestones
            $order->milestones()->delete();

            $subtotal = 0.0;
            foreach ($rows as $r) {
                $price = (float) ($r['price'] ?? 0);
                $subtotal += $price;

                $order->milestones()->create([
                    'title'       => (string) ($r['title'] ?? ''),
                    'description' => $r['description'] ?? null,
                    'price'       => $price,
                    'start_date'  => $r['start_date'] ?? null,
                    'end_date'    => $r['end_date']   ?? null,
                    'status'      => 'draft',
                ]);
            }

            // ---- Seller-side fee settings (fallback to generic keys) ----
            $platformPercent = (float) (
                DB::table('platform_settings')->where('key', 'seller_platform_fee_percent')->value('value')
                ?? DB::table('platform_settings')->where('key', 'platform_fee_percent')->value('value')
                ?? 0
            );
            $gstPercent = (float) (
                DB::table('platform_settings')->where('key', 'gst_percent')->value('value')
                ?? 0
            );
            if ($platformPercent < 0) $platformPercent = 0;
            if ($gstPercent      < 0) $gstPercent      = 0;

            // All in seller currency (order currency)
            $platformAmount = round($subtotal * ($platformPercent / 100), 2);
            $gstAmount      = round(($subtotal + $platformAmount) * ($gstPercent / 100), 2);
            $totalPayable   = round($subtotal + $platformAmount + $gstAmount, 2);

            $order->forceFill([
                'subtotal'               => $subtotal,
                'platform_fee_percent'   => $platformPercent,
                'platform_fee_amount'    => $platformAmount,
                'gst_percent'            => $gstPercent,
                'gst_amount'             => $gstAmount,
                'total_payable'          => $totalPayable,
                'status'                 => $order->status === 'reupdated' ? 'reupdated' : 'sent',
            ])->save();
        });

        // email buyer
        $order->refresh();
        try {
            Mail::to($order->buyer->email)->send(new ContractSentMail($order));
        } catch (\Throwable $e) {
        }

        return redirect()
            ->route('service.contracts.show', $order->id)
            ->with('success', 'Milestones saved and contract sent to buyer.');
    }

    public function resend(ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && $order->seller_id === $me, 403);

        if (!in_array($order->status, ['sent', 'reupdated'], true)) {
            return back()->withErrors(['order' => 'You can only resend a sent contract.']);
        }

        $order->update(['status' => 'reupdated']);
        try {
            Mail::to($order->buyer->email)->send(new ContractReupdatedMail($order));
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'Contract re-sent to buyer.');
    }

    public function show(ServiceOrder $order)
{
    $me = Auth::id();
    abort_unless($me && ($order->buyer_id === $me || $order->seller_id === $me), 403);

    $order->load([
        'milestones',
        'buyer:id,first_name,last_name,email,wallet,country_id',
        'buyer.country:id,name,currency,currency_symbol',
        'seller:id,first_name,last_name,email,wallet,country_id',
        'seller.country:id,name,currency,currency_symbol',
    ]);

    // ---- Fee snapshot or live settings for the seller-currency summary ----
    $hasSnapshot = ($order->platform_fee_percent !== null) || ($order->gst_percent !== null);

    if ($hasSnapshot) {
        $feeQuote = [
            'platform_fee_percent' => (float) ($order->platform_fee_percent ?? 0),
            'gst_percent'          => (float) ($order->gst_percent ?? 0),
            'platform_fee_amount'  => (float) ($order->platform_fee_amount ?? 0),
            'gst_amount'           => (float) ($order->gst_amount ?? 0),
            'subtotal'             => (float) ($order->subtotal ?? 0),
            'total'                => (float) ($order->total_payable ?? (
                (float) ($order->subtotal ?? 0)
              + (float) ($order->platform_fee_amount ?? 0)
              + (float) ($order->gst_amount ?? 0)
            )),
        ];
    } else {
        // Not approved yet: compute from current platform_settings
        try {
            $ps = \App\Models\PlatformSetting::query()->first();
            $buyerFeePct = (float) ($ps->buyer_platform_fee_percent ?? 0); // buyer-side fee for preview
            $gstPct      = (float) ($ps->gst_percent ?? 0);
        } catch (\Throwable $e) {
            $settings    = \Illuminate\Support\Facades\DB::table('platform_settings')->pluck('value', 'key');
            $buyerFeePct = (float) ($settings['buyer_platform_fee_percent'] ?? $settings['platform_fee_percent'] ?? 0);
            $gstPct      = (float) ($settings['gst_percent'] ?? 0);
        }

        if ($buyerFeePct < 0) $buyerFeePct = 0;
        if ($gstPct < 0)      $gstPct      = 0;

        $subtotal     = (float) ($order->subtotal ?? 0);
        $platformFee  = round($subtotal * $buyerFeePct / 100, 2);
        // GST on (subtotal + platform fee)
        $gstAmount    = round(($subtotal + $platformFee) * $gstPct / 100, 2);
        $total        = round($subtotal + $platformFee + $gstAmount, 2);

        $feeQuote = [
            'platform_fee_percent' => $buyerFeePct,
            'gst_percent'          => $gstPct,
            'platform_fee_amount'  => $platformFee,
            'gst_amount'           => $gstAmount,
            'subtotal'             => $subtotal,
            'total'                => $total,
        ];
    }

    // ---- Buyer-currency preview & buyer funds conversion ----
    $buyerPreview = null;
    $buyerConverted = null;
    try {
        $buyerCode   = strtoupper(($order->buyer->country->currency ?? '') ?: 'USD');
        $buyerSymbol = $order->buyer->country->currency_symbol ?? '$';
        $sellerCode  = strtoupper($order->currency_code ?: 'USD');

        $fx = new \App\Services\Currency\CurrencyConverter();
        $buyerSubtotal = round($fx->convert((float)$order->subtotal, $sellerCode, $buyerCode), 2);

        $settings = \Illuminate\Support\Facades\DB::table('platform_settings')->pluck('value', 'key');
        $buyerPct = (float) ($settings['buyer_platform_fee_percent'] ?? $settings['platform_fee_percent'] ?? 0);
        $gstPct   = (float) ($settings['gst_percent'] ?? 0);
        if ($buyerPct < 0) $buyerPct = 0;
        if ($gstPct   < 0) $gstPct   = 0;

        $buyerFee   = round($buyerSubtotal * ($buyerPct / 100), 2);
        $buyerGST   = round(($buyerSubtotal + $buyerFee) * ($gstPct / 100), 2);
        $buyerTotal = round($buyerSubtotal + $buyerFee + $buyerGST, 2);

        // funds conversion (hold / released) for buyer
        $buyerHold     = round($fx->convert((float)($order->hold_amount ?? 0), $sellerCode, $buyerCode), 2);
        $buyerReleased = round($fx->convert((float)($order->released_amount ?? 0), $sellerCode, $buyerCode), 2);

        // milestone price conversion map for buyer view
        $milestonePrices = [];
        foreach ($order->milestones as $m) {
            $milestonePrices[$m->id] = round($fx->convert((float)($m->price ?? 0), $sellerCode, $buyerCode), 2);
        }

        $buyerPreview = [
            'symbol'               => $buyerSymbol,
            'subtotal'             => $buyerSubtotal,
            'platform_fee_percent' => $buyerPct,
            'platform_fee_amount'  => $buyerFee,
            'gst_percent'          => $gstPct,
            'gst_amount'           => $buyerGST,
            'total'                => $buyerTotal,
            'hold'                 => $buyerHold,
            'released'             => $buyerReleased,
        ];

        $buyerConverted = [
            'symbol'     => $buyerSymbol,
            'milestones' => $milestonePrices,
            'hold'       => $buyerHold,
            'released'   => $buyerReleased,
        ];
    } catch (\Throwable $e) {
        $buyerPreview = null;
        $buyerConverted = null;
    }

    // ---- Seller payout info (show seller platform % and net in seller currency) ----
    try {
        $set = \Illuminate\Support\Facades\DB::table('platform_settings')->pluck('value','key');
        $sellerPlatformPctTotal = (float) ($set['seller_platform_fee_percent'] ?? 0);
    } catch (\Throwable $e) {
        $sellerPlatformPctTotal = 0.0;
    }
    if ($sellerPlatformPctTotal < 0) $sellerPlatformPctTotal = 0;

    $milestoneCount    = max(1, (int) $order->milestones->count());
    $perMilestonePct   = $sellerPlatformPctTotal / $milestoneCount;
    $sellerSubtotal    = (float) ($order->subtotal ?? 0);
    $holdGross         = (float) ($order->hold_amount ?? 0);

    // what seller receives if ALL milestones are released (net after platform fee rule)
    $netIfFullyReleased = round($sellerSubtotal - ($sellerSubtotal * ($perMilestonePct / 100)), 2);
    // net on the CURRENT hold (remaining part), if released now
    $netOnRemainingHold = round($holdGross - ($holdGross * ($perMilestonePct / 100)), 2);

    $sellerPayoutInfo = [
        'per_milestone_percent' => $perMilestonePct,
        'net_if_full'           => $netIfFullyReleased,
        'net_on_hold'           => $netOnRemainingHold,
    ];

    return view('service.contracts.show', [
        'order'            => $order,
        'feeQuote'         => $feeQuote,
        'buyerPreview'     => $buyerPreview,
        'buyerConverted'   => $buyerConverted,
        'sellerPayoutInfo' => $sellerPayoutInfo,
    ]);
}


    public function buyerCancel(ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && $order->buyer_id === $me, 403);

        if (!in_array($order->status, ['sent', 'reupdated', 'draft'], true)) {
            return back()->withErrors(['order' => 'Cannot cancel at this stage.']);
        }

        $order->update(['status' => 'canceled_by_buyer']);

        // notify seller
        try {
            Mail::to($order->seller->email)->send(new ContractReupdatedMail($order));
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'Contract canceled.');
    }

    /** Seller submission form */
   public function submitForm(ServiceMilestone $milestone)
{
    $me = Auth::id();
    abort_unless($me && $milestone->order && $milestone->order->seller_id === $me, 403);

    $order = $milestone->order;

    // Must be paid/in progress
    if (!in_array($order->status, ['approved_paid', 'in_progress'], true)) {
        return redirect()->route('service.contracts.show', $order->id)
            ->withErrors(['milestone' => 'Order must be approved & paid first.']);
    }

    // 🔒 NEW: sequential gate — previous milestone must be released (if any)
    $prev = $order->milestones()
        ->where('id', '<', $milestone->id)
        ->orderByDesc('id')
        ->first();

    if ($prev && $prev->status !== 'released') {
        return redirect()->route('service.contracts.show', $order->id)
            ->withErrors(['milestone' => 'You can submit this milestone only after the previous one is released.']);
    }

    return view('service.milestones.submit', compact('milestone'));
}


    /** Seller submits milestone deliverable */
   public function submit(StoreMilestoneSubmissionRequest $request, ServiceMilestone $milestone)
{
    $me = Auth::id();
    abort_unless($me && $milestone->order && $milestone->order->seller_id === $me, 403);

    $order = $milestone->order;
    if (!in_array($order->status, ['approved_paid', 'in_progress'], true)) {
        return back()->withErrors(['milestone' => 'Order must be approved & paid first.']);
    }

    // 🔒 NEW: sequential gate — previous milestone must be released (if any)
    $prev = $order->milestones()
        ->where('id', '<', $milestone->id)
        ->orderByDesc('id')
        ->first();

    if ($prev && $prev->status !== 'released') {
        return back()->withErrors(['milestone' => 'You can submit this milestone only after the previous one is released.']);
    }

    $path = null; $name = null; $mime = null; $size = null;
    if ($request->hasFile('file')) {
        $f = $request->file('file');
        $path = $f->store("service/{$order->id}/milestones/{$milestone->id}", 'public');
        $name = $f->getClientOriginalName();
        $mime = $f->getClientMimeType();
        $size = $f->getSize();
    }

    DB::transaction(function () use ($request, $milestone, $me, $path, $name, $mime, $size) {
        ServiceMilestoneSubmission::create([
            'service_milestone_id' => $milestone->id,
            'seller_id'            => $me,
            'note'                 => $request->input('note'),
            'file_path'            => $path,
            'file_name'            => $name,
            'file_mime'            => $mime,
            'file_size'            => $size,
            'url'                  => $request->input('url'),
        ]);

        $milestone->update(['status' => 'submitted']);
        if ($milestone->order->status === 'approved_paid') {
            $milestone->order->update(['status' => 'in_progress']);
        }
    });

    // notify buyer
    try {
        Mail::to($order->buyer->email)->send(new MilestoneSubmittedMail($milestone));
    } catch (\Throwable $e) {}

    return redirect()->route('service.contracts.show', $order->id)
        ->with('success', 'Milestone submitted to buyer.');
}


    /** Buyer asks for changes (simple state) */
    public function requestCancel(Request $request, ServiceMilestone $milestone)
{
    $me = Auth::id();
    abort_unless($me && $milestone->order && $milestone->order->buyer_id === $me, 403);

    // Delete previous submissions (files + rows)
    $subs = $milestone->submissions()->get();
    foreach ($subs as $sub) {
        try {
            if ($sub->file_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($sub->file_path);
            }
        } catch (\Throwable $e) {}
    }
    $milestone->submissions()->delete();

    // Flag for resubmission
    $milestone->update(['status' => 'resubmitted']);

    // Notify seller to re-submit
    try {
        \Mail::to($milestone->seller?->email ?? $milestone->order->seller->email)
            ->queue(new \App\Mail\Service\MilestoneSubmittedMail($milestone));
    } catch (\Throwable $e) {}

    return back()->with('success','Requested changes from seller. Previous files removed. Please wait for resubmission.');
}


    /** Seller service orders (work queue) */
public function sellerOrdersPage(Request $request)
{
    $me = Auth::id();
    abort_unless($me, 403);

    // ── Filters from query string
    $activeStatus       = strtolower($request->query('status', 'all')); // all|new|in_progress|completed|canceled
    $selectedSubcatId   = (int) $request->query('subcategory_id', 0);
    $productSearchQuery = trim((string) $request->query('q', ''));

    // ── Service subcategories for the filter dropdown
    $servicesType = \App\Models\ProductType::where('slug', 'services')
        ->orWhere('name', 'Services')
        ->first();
    $serviceSubcategories = \App\Models\ProductSubcategory::query()
        ->when($servicesType, fn ($q) => $q->where('product_type_id', $servicesType->id))
        ->orderBy('name')
        ->get(['id','name']);

    // ── Base query for orders list
    $ordersQ = \App\Models\ServiceOrder::query()
        ->where('seller_id', $me)
        ->withCount(['milestones'])
        ->with([
            'buyer:id,first_name,last_name,email',
            'product:id,name,product_subcategory_id',
        ])
        // default scope for "seller orders" page
        ->whereIn('status', ['approved_paid','in_progress','completed','canceled','canceled_by_buyer','canceled_by_seller']);

    // Status filter
    if ($activeStatus !== 'all') {
        if ($activeStatus === 'new') {
            $ordersQ->whereIn('status', ['approved_paid']);
        } elseif ($activeStatus === 'in_progress') {
            $ordersQ->where('status', 'in_progress');
        } elseif ($activeStatus === 'completed') {
            $ordersQ->where('status', 'completed');
        } elseif ($activeStatus === 'canceled') {
            $ordersQ->whereIn('status', ['canceled','canceled_by_buyer','canceled_by_seller']);
        }
    }

    // Subcategory filter (services only)
    if ($selectedSubcatId > 0) {
        $ordersQ->whereHas('product', function ($q) use ($selectedSubcatId) {
            $q->where('product_subcategory_id', $selectedSubcatId);
        });
    }

    // Product name search
    if ($productSearchQuery !== '') {
        $ordersQ->whereHas('product', function ($q) use ($productSearchQuery) {
            $q->where('name', 'like', '%'.$productSearchQuery.'%');
        });
    }

    $orders = $ordersQ->latest('id')->paginate(25)->withQueryString();

    // ── Tab counts (not affected by dropdown/search so they match the "overall" buckets)
    $countBase = \App\Models\ServiceOrder::query()
        ->where('seller_id', $me);
    $counts = [
        'new'         => (clone $countBase)->whereIn('status', ['approved_paid'])->count(),
        'in_progress' => (clone $countBase)->where('status', 'in_progress')->count(),
        'completed'   => (clone $countBase)->where('status', 'completed')->count(),
        'canceled'    => (clone $countBase)->whereIn('status', ['canceled','canceled_by_buyer','canceled_by_seller'])->count(),
    ];

    // View name kept as singular per your last file: resources/views/UserAdmin/service_order.blade.php
    return view('UserAdmin.service_orders', [
        'orders'               => $orders,
        'counts'               => $counts,
        'serviceSubcategories' => $serviceSubcategories,
        'selectedSubcatId'     => $selectedSubcatId,
        'productSearchQuery'   => $productSearchQuery,
        'activeStatus'         => $activeStatus,
    ]);
}


    /** NEW: Delete a contract (only seller; only while not confirmed) */
    public function destroy(ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && $order->seller_id === $me, 403);

        if (!in_array($order->status, ['draft', 'sent', 'reupdated'], true)) {
            return back()->withErrors(['order' => 'This contract can no longer be deleted.']);
        }

        DB::transaction(function () use ($order) {
            // delete submissions -> milestones -> order
            foreach ($order->milestones as $m) {
                $m->submissions()->delete();
            }
            $order->milestones()->delete();
            $order->delete();
        });

        return redirect()->route('service.contracts.index')->with('success', 'Contract deleted.');
    }

    public function cancelOngoingBySeller(ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && $order->seller_id === $me, 403);

        if (!in_array($order->status, ['approved_paid','in_progress'], true)) {
            return back()->withErrors(['order' => 'You can only cancel an ongoing project.']);
        }

        // mark canceled by seller + keep history in meta
        $meta = (array) ($order->meta ?? []);
        $meta['canceled_at'] = now()->toDateTimeString();
        $meta['canceled_by'] = 'seller';

        $order->forceFill([
            'status' => 'canceled_by_seller',
            'meta'   => $meta,
        ])->save();

        // Notify buyer (raw mail keeps this self-contained)
        try {
            \Mail::raw(
                "The seller canceled Contract #{$order->id}. If you disagree, you can report to admin from the contract page.",
                function ($m) use ($order) {
                    $m->to($order->buyer->email)
                      ->subject("Contract #{$order->id} canceled by seller");
                }
            );
        } catch (\Throwable $e) {
            // swallow mail errors silently
        }

        return back()->with('success', 'Project canceled. The buyer can now report to admin if needed.');
    }

    public function cancelOngoingByBuyer(ServiceOrder $order)
    {
        $me = Auth::id();
        abort_unless($me && $order->buyer_id === $me, 403);

        if (!in_array($order->status, ['approved_paid','in_progress'], true)) {
            return back()->withErrors(['order' => 'You can only cancel an ongoing project.']);
        }

        // mark canceled by buyer + keep history in meta
        $meta = (array) ($order->meta ?? []);
        $meta['canceled_at'] = now()->toDateTimeString();
        $meta['canceled_by'] = 'buyer';

        $order->forceFill([
            'status' => 'canceled_by_buyer',
            'meta'   => $meta,
        ])->save();

        // Notify seller (raw mail keeps this self-contained)
        try {
            \Mail::raw(
                "The buyer canceled Contract #{$order->id}. If you disagree, you can report to admin from the contract page.",
                function ($m) use ($order) {
                    $m->to($order->seller->email)
                      ->subject("Contract #{$order->id} canceled by buyer");
                }
            );
        } catch (\Throwable $e) {
            // swallow mail errors silently
        }

        return back()->with('success', 'Project canceled. The seller can now report to admin if needed.');
    }

}

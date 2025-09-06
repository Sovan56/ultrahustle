<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ProductReviewController;
// Controllers
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\MyTeamController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\UserAdminController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminBoostRateController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductPublicController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AdminKycController;
use App\Http\Controllers\Api\S3MultipartController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WishlistController;

// PING
Route::get('/ping', function () {
    if (auth()->check()) {
        $now  = now();
        $last = session('last_seen_touch_at');

        if (!$last || $now->diffInSeconds($last) >= 60) {
            auth()->user()->forceFill(['last_seen_at' => $now])->saveQuietly();
            session(['last_seen_touch_at' => $now]);
        }
    }
    return 'pong';
})->name('ping');



Route::get('/media/{path}', function (string $path) {
    // basic safety
    $path = str_replace('..', '', $path);
    $path = ltrim($path, '/');

    // Normalize common prefixes so public disk can find the file
    if (str_starts_with($path, 'storage/')) {
        $path = substr($path, 8); // drop "storage/"
    }
    if (str_starts_with($path, 'public/')) {
        $path = substr($path, 7); // drop "public/"
    }

    if (!Storage::disk('public')->exists($path)) abort(404);
    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('media.pass');

/**
 * Static pages / errors
 */
Route::view('/session-expired', 'errors.session-expired')->name('session.expired');
Route::view('/page-not-found', 'errors.404')->name('custom.404');

/**
 * Public pages
 */
Route::get('/', [HomeController::class, 'welcome'])->name('home');
// Marketplace (public)
Route::get('/marketplace', [HomeController::class, 'marketplace'])->name('marketplace');
Route::get('/marketplace/list', [HomeController::class, 'marketplaceList'])->name('marketplace.list');
Route::get('/marketplace/subcategories', [HomeController::class, 'marketplaceSubcategories'])->name('marketplace.subs'); // AJAX

// Search suggestions (public, JSON)
Route::get('/search/suggest', [HomeController::class, 'searchSuggest'])->name('search.suggest');


/**
 * Public product detail page
 */
Route::get('/product/{id}', [ProductPublicController::class, 'show'])
    ->whereNumber('id')
    ->name('product.details');

Route::get('/forum', fn() => view('forum'))->name('forum');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->name('newsletter.subscribe');

Route::post('/a/click', [AnalyticsController::class, 'click'])->name('analytics.click');

// Login with 2FA recovery code
Route::post('/login/recovery', [UserAdminController::class, 'loginWithRecovery'])
    ->name('auth.login.recovery');

/**
 * ===========================
 * AUTHENTICATED USER (checkUser)
 * ===========================
 */
Route::middleware(['checkUser', 'touch.lastseen'])->group(function () {

    // ---- Dashboard / landing ----
    Route::get('/UserAdmin', [UserAdminController::class, 'dashboard'])->name('user.admin.index');

    // ---- Profile (About / Settings) ----
    Route::get('/UserAdmin/profile', [ProfileController::class, 'show'])->name('user.admin.profile');
    Route::post('/user-admin/profile', [ProfileController::class, 'update'])->name('user.admin.profile.update');
    // Update country (AJAX)
    Route::post('/user-admin/profile/country', [ProfileController::class, 'updateCountryAjax'])->name('user.admin.profile.country.update');

    // ---- Countries (AJAX search for select) ----
    Route::get('/user-admin/countries', [CountryController::class, 'index'])->name('user.admin.countries');

    // ---- Security: password, email OTP, 2FA ----
    Route::post('/user-admin/security/password', [SecurityController::class, 'changePassword'])->name('user.admin.security.password');
    Route::post('/user-admin/security/email/request-otp', [SecurityController::class, 'requestEmailOtp'])->name('user.admin.security.email.request');
    Route::post('/user-admin/security/email/verify', [SecurityController::class, 'verifyEmailOtp'])->name('user.admin.security.email.verify');

    // 2FA (TOTP)
    Route::post('/user-admin/security/2fa/setup', [SecurityController::class, 'twofaSetup'])->name('user.admin.security.2fa.setup');
    Route::post('/user-admin/security/2fa/enable', [SecurityController::class, 'twofaEnable'])->name('user.admin.security.2fa.enable');
    Route::post('/user-admin/security/2fa/disable', [SecurityController::class, 'twofaDisable'])->name('user.admin.security.2fa.disable');

    // Recovery codes
    Route::post('/user-admin/security/2fa/recovery/regenerate', [SecurityController::class, 'recoveryRegenerate'])->name('user.admin.security.2fa.recovery.regen');
    Route::get('/user-admin/security/2fa/recovery/download', [SecurityController::class, 'recoveryDownload'])->name('user.admin.security.2fa.recovery.download');

    // ---- KYC ----
    Route::post('/user-admin/kyc/save', [KycController::class, 'save'])->name('user.admin.kyc.save');

    // =========================
    // My Team (pages + JSON APIs)
    // =========================
    Route::get('/UserAdmin/myteam', [MyTeamController::class, 'page'])->name('user.admin.myteam');

    // Pages
    Route::get('/UserAdmin/myteam/create',           [MyTeamController::class, 'pageCreate'])->name('user.admin.myteam.create');
    Route::get('/UserAdmin/myteam/{team}/edit',      [MyTeamController::class, 'pageEdit'])->name('user.admin.myteam.edit');
    Route::get('/UserAdmin/myteam/{team}/portfolio', [MyTeamController::class, 'pagePortfolio'])->name('user.admin.myteam.portfolio');

    // Teams JSON
    Route::get('/user-admin/teams',                           [MyTeamController::class, 'teams'])->name('teams.index');
    Route::post('/user-admin/teams',                          [MyTeamController::class, 'createTeam'])->name('teams.store');
    Route::post('/user-admin/teams/{team}',                   [MyTeamController::class, 'updateTeam'])->name('teams.update'); // POST for FormData
    Route::delete('/user-admin/teams/{team}',                 [MyTeamController::class, 'deleteTeam'])->name('teams.delete');

    // Members JSON
    Route::get('/user-admin/teams/{team}/members',                    [MyTeamController::class, 'members'])->name('teams.members');
    Route::post('/user-admin/teams/{team}/members',                   [MyTeamController::class, 'addMembers'])->name('teams.members.add');
    Route::delete('/user-admin/teams/{team}/members/{member}',        [MyTeamController::class, 'removeMember'])->name('teams.members.remove');
    Route::post('/user-admin/teams/{team}/members/{member}/resend',   [MyTeamController::class, 'resendInvite'])->name('teams.members.resend');

    // Projects JSON
    Route::get('/user-admin/teams/{team}/projects',                   [MyTeamController::class, 'projects'])->name('teams.projects.index');
    Route::post('/user-admin/teams/{team}/projects',                  [MyTeamController::class, 'projectStore'])->name('teams.projects.store');
    Route::post('/user-admin/teams/{team}/projects/{project}',        [MyTeamController::class, 'projectUpdate'])->name('teams.projects.update'); // POST for FormData
    Route::delete('/user-admin/teams/{team}/projects/{project}',      [MyTeamController::class, 'projectDelete'])->name('teams.projects.delete');

    // Project Images
    Route::post('/user-admin/teams/{team}/projects/{project}/images',           [MyTeamController::class, 'projectImagesAdd'])->name('teams.projects.images.add');
    Route::delete('/user-admin/teams/{team}/projects/{project}/images/{image}', [MyTeamController::class, 'projectImagesDelete'])->name('teams.projects.images.delete');

    // =========================
    // Marketplace (User area)
    // =========================
    Route::get('/UserAdmin/marketplace', [MarketplaceController::class, 'page'])->name('user.admin.marketplace');

    // Lookups
    Route::get('/user-admin/marketplace/types',         [MarketplaceController::class, 'getProductTypes']);
    Route::get('/user-admin/marketplace/subcategories', [MarketplaceController::class, 'getSubcategories']);
    Route::get('/user-admin/marketplace/user-meta',     [MarketplaceController::class, 'userMeta'])->name('user.admin.marketplace.user_meta');
    // Countries list for selects
    Route::get('/user-admin/marketplace/countries',     [MarketplaceController::class, 'getCountries']);
    // Back-compat alias: some code might still call "currencies"
    Route::get('/user-admin/marketplace/currencies',    [MarketplaceController::class, 'getCountries']);

    // Products
    Route::get('/user-admin/marketplace/products',                 [MarketplaceController::class, 'index']);
    Route::post('/user-admin/marketplace/products',                [MarketplaceController::class, 'store']);
    Route::get('/user-admin/marketplace/products/{id}',            [MarketplaceController::class, 'show'])->whereNumber('id');
    Route::post('/user-admin/marketplace/products/{id}',           [MarketplaceController::class, 'update'])->whereNumber('id'); // POST for FormData
    Route::delete('/user-admin/marketplace/products/{id}',         [MarketplaceController::class, 'destroy'])->whereNumber('id');
    Route::post('/user-admin/marketplace/products/{id}/duplicate', [MarketplaceController::class, 'duplicate'])->whereNumber('id');

    // Publish toggle (single item)
    Route::post('/user-admin/marketplace/products/{id}/publish-toggle', [MarketplaceController::class, 'togglePublish'])->whereNumber('id');

    // Orders
    Route::get('/user-admin/marketplace/orders',               [MarketplaceController::class, 'orders']);
    Route::get('/user-admin/marketplace/orders-summary',       [MarketplaceController::class, 'ordersSummary']);
    Route::get('/user-admin/marketplace/orders/{id}',          [MarketplaceController::class, 'getOrder']);
    Route::post('/user-admin/marketplace/orders/{id}/start',   [MarketplaceController::class, 'startOrder']);
    Route::post('/user-admin/marketplace/orders/{id}/stages',  [MarketplaceController::class, 'saveOrderStages']);
    Route::post('/user-admin/marketplace/orders/{id}/cancel',  [MarketplaceController::class, 'cancelOrder']);

    // Razorpay (user)
    Route::post('/payments/razorpay/order', [MarketplaceController::class, 'createRazorpayOrder'])->name('razorpay.order');

    // Checkout (user)
    Route::get('/checkout/{product}',                 [PaymentController::class, 'showCheckout'])->name('checkout.show')->whereNumber('product');
    Route::post('/checkout/create-order',             [PaymentController::class, 'createOrder'])->name('checkout.createOrder');
    Route::post('/checkout/verify',                   [PaymentController::class, 'verify'])->name('checkout.verify');
    Route::post('/checkout/simulate-success/{order}', [PaymentController::class, 'simulateSuccess'])->name('checkout.simulate')->whereNumber('order');

    // ===== Wallet =====
    Route::get('/UserAdmin/wallet', [UserAdminController::class, 'wallet'])->name('user.admin.wallet');
    Route::get('/user-admin/wallet/transactions', [UserAdminController::class, 'walletTransactionsJson'])->name('user.admin.wallet.transactions.json');
    Route::post('/user-admin/wallet/add-funds/order', [UserAdminController::class, 'createAddFundsOrder'])->name('user.admin.wallet.add_funds.order');
    Route::post('/user-admin/wallet/add-funds/callback', [UserAdminController::class, 'handleAddFundsCallback'])->name('user.admin.wallet.add_funds.callback');
    Route::post('/user-admin/wallet/withdraw', [UserAdminController::class, 'requestWithdraw'])->name('user.admin.wallet.withdraw');

    // Boost page + APIs
    Route::get('/user-admin/marketplace/boosts',            [MarketplaceController::class, 'boostPage']);
    Route::get('/user-admin/marketplace/boosts/active',     [MarketplaceController::class, 'activeBoosts']);
    Route::get('/user-admin/marketplace/boost-plans',       [MarketplaceController::class, 'boostPlans']);
    Route::get('/user-admin/marketplace/boost-quote',       [MarketplaceController::class, 'boostQuote']);
    Route::post('/user-admin/marketplace/products/{id}/boost', [MarketplaceController::class, 'toggleBoost'])->whereNumber('id');

    Route::get('/user-admin/analytics/boosted/daily', [AnalyticsController::class, 'daily'])->name('user.admin.analytics.boosted.daily');
    Route::get('/user-admin/analytics/boosted/top',   [AnalyticsController::class, 'top'])->name('user.admin.analytics.boosted.top');

    // Orders page (separate page with sidebar entry)
    Route::get('/UserAdmin/orders', [MarketplaceController::class, 'ordersPage'])->name('user.admin.orders.page');

    // My orders
    // Route::get('/UserAdmin/my-orders', [MarketplaceController::class, 'myOrdersPage'])->name('user.myorders.page');
    Route::get('/user/my-orders', [MarketplaceController::class, 'myOrders'])->name('user.myorders.list');
    // Route::get('/user/my-orders/{id}', [MarketplaceController::class, 'myOrderShow'])->name('user.myorders.show')->whereNumber('id');
    // Route::post('/user/my-orders/{id}/approve', [MarketplaceController::class, 'approveOrder'])->name('user.myorders.approve')->whereNumber('id');

    // Quote (line items) for wallet checkout
    Route::post('/checkout/quote', [PaymentController::class, 'walletQuote'])->name('checkout.quote');
    // Pay from wallet (deduct total and fulfill)
    Route::post('/checkout/wallet', [PaymentController::class, 'walletCheckout'])->name('checkout.wallet');
    // Buy success page
    Route::get('/orders/success/{order}', [PaymentController::class, 'success'])->name('orders.success')->whereNumber('order');

    // My Orders (User)
    Route::get('/UserAdmin/my-orders', fn() => view('UserAdmin.MyOrder'))->name('user.myorders.page');
    Route::get('/user/my-orders/data', [PaymentController::class, 'myOrdersData'])->name('user.myorders.data');

    // Attachments (chat)
    Route::get('/UserAdmin/chat/attachments/{message}/{index}', [ChatAttachmentController::class, 'redirect'])
        ->whereNumber('message')->whereNumber('index')
        ->name('chat.attachments.show');

    // Digital downloads
    Route::get('/downloads/order/{order}/{index}', [DownloadController::class, 'digital'])
        ->whereNumber('order')->whereNumber('index')
        ->name('downloads.order');

    // ===== Messages pages =====
    Route::get('/UserAdmin/messages', [ChatController::class, 'page'])->name('user.messages');

    // ===== Chat APIs (aligned with blades & controller) =====
    // Conversations list (left sidebar)
    Route::get('/chat/conversations', [ChatController::class, 'conversations'])->name('chat.conversations');

    // Open (or create) a conversation by partner (used by product detail mini-chat)
    Route::post('/chat/open', [ChatController::class, 'open'])->name('chat.open');

    // Seed (send) from product page mini-chat
    Route::post('/chat/seed', [ChatController::class, 'seed'])->name('chat.seed');

    // Full conversation payload (partner + last 100 messages)
    Route::get('/chat/{conversation}', [ChatController::class, 'conversation'])
        ->whereNumber('conversation')
        ->name('chat.conversation');

    // Send message to a conversation
    Route::post('/chat/{conversation}/send', [ChatController::class, 'send'])
        ->whereNumber('conversation')->name('chat.send');

    // Typing indicator
    Route::post('/chat/{conversation}/typing', [ChatController::class, 'typing'])
        ->whereNumber('conversation')->name('chat.typing');

    // Read receipts
    Route::post('/chat/{conversation}/delivered', [ChatController::class, 'markDelivered'])
        ->whereNumber('conversation')->name('chat.delivered');
    Route::post('/chat/{conversation}/seen', [ChatController::class, 'markSeen'])
        ->whereNumber('conversation')->name('chat.seen');

    // Mini-chat history by partner (pair); used by product page to preload
    Route::get('/chat/history', [ChatController::class, 'history'])->name('chat.history');

    // (Optional legacy) quick send kept for back-compat
    Route::post('/chat/quick-send', [ChatController::class, 'quickSend'])->name('chat.quickSend');


    Route::post('/product/{product}/review', [ProductReviewController::class, 'store'])
        ->whereNumber('product')
        ->name('product.review.store');


    Route::get('/UserAdmin/wishlist',          [WishlistController::class, 'page'])->name('wishlist.page');
    Route::get('/wishlist/items',    [WishlistController::class, 'items'])->name('wishlist.items');
    Route::post('/wishlist/toggle',  [WishlistController::class, 'toggle'])->name('wishlist.toggle');










// routes/web.php (inside your checkUser group)

Route::get('/user-admin/payout-accounts', [UserAdminController::class, 'payoutAccounts'])->name('user.payout.accounts');
Route::post('/user-admin/payout-accounts', [UserAdminController::class, 'savePayoutAccount'])->name('user.payout.accounts.save');
Route::delete('/user-admin/payout-accounts/{acc}', [UserAdminController::class, 'deletePayoutAccount'])->name('user.payout.accounts.delete');
Route::post('/user-admin/payout-accounts/{acc}/default', [UserAdminController::class, 'makeDefaultPayoutAccount'])->name('user.payout.accounts.default');

// PayPal Add Funds
Route::post('/user-admin/wallet/paypal/order',   [UserAdminController::class, 'createPaypalOrder'])->name('user.admin.wallet.paypal.order');
Route::post('/user-admin/wallet/paypal/capture', [UserAdminController::class, 'capturePaypalOrder'])->name('user.admin.wallet.paypal.capture');

// Optional throttling
Route::post('/user-admin/wallet/withdraw', [UserAdminController::class, 'requestWithdraw'])
  ->middleware('throttle:6,1')
  ->name('user.admin.wallet.withdraw');

Route::post('/user-admin/wallet/add-funds/order', [UserAdminController::class, 'createAddFundsOrder'])
  ->middleware('throttle:10,1')
  ->name('user.admin.wallet.add_funds.order');


Route::post('/user-admin/wallet/paypal/order',   [UserAdminController::class, 'paypalCreateOrder'])->name('user.admin.wallet.paypal.order');
Route::post('/user-admin/wallet/paypal/capture', [UserAdminController::class, 'paypalCapture'])->name('user.admin.wallet.paypal.capture');

Route::get('/user-admin/wallet/paypal/return',   [UserAdminController::class, 'paypalReturn'])->name('user.admin.wallet.paypal.return');
Route::get('/user-admin/wallet/paypal/cancel',   [UserAdminController::class, 'paypalCancel'])->name('user.admin.wallet.paypal.cancel');



















});

/**
 * ===========================
 * ADMIN (auth guarded)
 * ===========================
 */
Route::get('/admin/login',  [AdminController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

Route::middleware('admin.auth')->group(function () {
    Route::get('/admin', fn() => redirect()->route('admin.members.page'))->name('admin.home');

    // Members
    Route::get('/admin/members',            [AdminController::class, 'membersPage'])->name('admin.members.page');
    Route::get('/admin/members/list',       [AdminController::class, 'membersList'])->name('admin.members.list');          // JSON
    Route::get('/admin/members/export',     [AdminController::class, 'membersExport'])->name('admin.members.export');      // CSV|PDF

    // Teams (admin view)
    Route::get('/admin/teams',                        [AdminController::class, 'teamsPage'])->name('admin.teams.page');
    Route::get('/admin/teams/list',                   [AdminController::class, 'teamsList'])->name('admin.teams.list');    // JSON
    Route::get('/admin/teams/{team}/members',         [AdminController::class, 'teamMembers'])->name('admin.team.members'); // JSON
    Route::get('/admin/teams/{team}/portfolio',       [AdminController::class, 'teamPortfolio'])->name('admin.team.portfolio'); // JSON
    Route::get('/admin/teams/{team}/export',          [AdminController::class, 'teamExport'])->name('admin.team.export');  // CSV|PDF
    Route::get('/admin/teams/{team}/portfolio/view',  [AdminController::class, 'teamPortfolioFull'])->name('admin.team.portfolio.view');

    // Legal CMS
    Route::get('/admin/legal',         [AdminController::class, 'legalPage'])->name('admin.legal.page');
    Route::get('/admin/legal/fetch',   [AdminController::class, 'legalFetch'])->name('admin.legal.fetch'); // JSON
    Route::post('/admin/legal/save',   [AdminController::class, 'legalSave'])->name('admin.legal.save');   // upsert
    Route::delete('/admin/legal/{id}', [AdminController::class, 'legalDelete'])->name('admin.legal.delete');

    // Boost Pricing Plans (Admin)
    Route::get('/admin/boost-plans',          [\App\Http\Controllers\AdminBoostPlanController::class, 'page'])->name('admin.boost_plans.page');
    Route::get('/admin/boost-plans/list',     [\App\Http\Controllers\AdminBoostPlanController::class, 'list'])->name('admin.boost_plans.list');
    Route::post('/admin/boost-plans/save',    [\App\Http\Controllers\AdminBoostPlanController::class, 'save'])->name('admin.boost_plans.save');
    Route::delete('/admin/boost-plans/{id}',  [\App\Http\Controllers\AdminBoostPlanController::class, 'delete'])->name('admin.boost_plans.delete');

    // KYC Review
    Route::get('/admin/kyc-requests', [AdminKycController::class, 'index'])->name('admin.kyc.page');
    Route::get('/admin/kyc-requests/list', [AdminKycController::class, 'list'])->name('admin.kyc.list'); // DataTables JSON
    Route::get('/admin/kyc-requests/{kyc}', [AdminKycController::class, 'show'])->name('admin.kyc.show');
    Route::post('/admin/kyc-requests/{kyc}/status', [AdminKycController::class, 'updateStatus'])->name('admin.kyc.status');

    Route::get('/admin/settings/fees', [\App\Http\Controllers\AdminPlatformSettingsController::class, 'show'])
        ->name('admin.settings.fees');
    Route::post('/admin/settings/fees', [\App\Http\Controllers\AdminPlatformSettingsController::class, 'save'])
        ->name('admin.settings.fees.save');

    Route::post('/s3/create',   [S3MultipartController::class, 'create']);
    Route::post('/s3/sign',     [S3MultipartController::class, 'signPart']);
    Route::post('/s3/complete', [S3MultipartController::class, 'complete']);

// ===========================
// Admin: Product Taxonomy
// ===========================
Route::get('/admin/taxonomy', [\App\Http\Controllers\AdminProductTaxonomyController::class, 'page'])
    ->name('admin.taxonomy.page');

Route::get('/admin/product-types/list',   [\App\Http\Controllers\AdminProductTaxonomyController::class, 'typesList'])->name('admin.types.list');
Route::post('/admin/product-types/save',  [\App\Http\Controllers\AdminProductTaxonomyController::class, 'typesSave'])->name('admin.types.save');
Route::delete('/admin/product-types/{id}',[\App\Http\Controllers\AdminProductTaxonomyController::class, 'typesDelete'])->name('admin.types.delete');

Route::get('/admin/product-subcategories/list',   [\App\Http\Controllers\AdminProductTaxonomyController::class, 'subcategoriesList'])->name('admin.subs.list');
Route::post('/admin/product-subcategories/save',  [\App\Http\Controllers\AdminProductTaxonomyController::class, 'subcategoriesSave'])->name('admin.subs.save');
Route::delete('/admin/product-subcategories/{id}',[\App\Http\Controllers\AdminProductTaxonomyController::class, 'subcategoriesDelete'])->name('admin.subs.delete');


});

/**
 * ===========================
 * PUBLIC webhooks & invites
 * ===========================
 */
Route::post('/payments/razorpay/webhook', [MarketplaceController::class, 'razorpayWebhook'])->name('razorpay.webhook');

// Public invite accept (guests ok; controller will redirect to login if needed)
Route::get('/invites/accept/{token}', [MyTeamController::class, 'acceptInvite'])->name('invites.accept');

/**
 * ===========================
 * AUTH (User)
 * ===========================
 */
Route::post('/register', [UserAdminController::class, 'register'])->name('auth.register');
Route::get('/login',     [UserAdminController::class, 'showLogin'])->name('login');
Route::post('/login',    [UserAdminController::class, 'login'])->name('auth.login');
Route::post('/logout',   [UserAdminController::class, 'logout'])->name('auth.logout');

/**
 * Password reset (custom OTP flow in UserAdminController)
 */
Route::post('/password/forgot',  [UserAdminController::class, 'forgot'])->name('password.forgot.send');
Route::post('/password/verify',  [UserAdminController::class, 'verify'])->name('password.otp.verify');
Route::post('/password/resend',  [UserAdminController::class, 'resend'])->name('password.otp.resend');
Route::post('/password/reset',   [UserAdminController::class, 'reset'])->name('password.reset');

/**
 * Public legal pages
 */
Route::get('/terms',   [AdminController::class, 'termsPagePublic'])->name('legal.terms');
Route::get('/privacy', [AdminController::class, 'privacyPagePublic'])->name('legal.privacy');

Route::post('/analytics/product-click', [HomeController::class, 'analyticsProductClick'])->name('analytics.product.click');
Route::post('/analytics/boost-view',    [HomeController::class, 'analyticsBoostView'])->name('analytics.boost.view');
Route::post('/analytics/list-view',     [HomeController::class, 'analyticsListImpressions'])->name('analytics.list.view');
Route::post('/analytics/product-view',  [HomeController::class, 'analyticsProductView'])->name('analytics.product.view');





// web.php
Route::get('/wishlist/ids', fn() => response()->json([
  'ids' => \App\Models\Wishlist::where('user_id', auth()->id() ?? session('user_id'))->pluck('product_id')
]))->name('wishlist.ids');


Route::get('/wishlist/count',    [WishlistController::class, 'count'])->name('wishlist.count');

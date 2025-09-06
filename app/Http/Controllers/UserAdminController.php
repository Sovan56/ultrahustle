<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAdminLoginRequest;
use App\Mail\OtpCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Mail, RateLimiter, Schema};
use Illuminate\Support\Str;
use App\Models\BalanceTransaction;
use App\Services\Payments\RazorpayGateway;
use App\Models\Country;

use App\Models\UserPayoutAccount;
use Illuminate\Validation\Rule;
use App\Services\Payments\PaypalGateway;
use App\Services\Payouts\RazorpayXPayouts;
use App\Services\Payouts\PaypalPayouts;
use Illuminate\Support\Facades\Http;

class UserAdminController extends Controller
{
    private int $otpExpiresInMinutes = 10;
    private int $otpMaxAttempts = 5;
    private int $resendCooldownSec = 60;

    public function dashboard(\Illuminate\Http\Request $request)
{
    $user = \Auth::user() ?? \App\Models\User::find(session('user_id'));
    abort_if(!$user, 403);

    $user->load(['anotherDetail', 'kycSubmission']);

    // ---------- SETTINGS bucket (60%) ----------
    $settingsFields = [
        'first_name'                       => filled($user->first_name),
        'last_name'                        => filled($user->last_name),
        'email'                            => filled($user->email),
        'phone_number'                     => filled($user->phone_number),
        'country_id'                       => filled($user->country_id),
        'anotherDetail.location'           => filled(optional($user->anotherDetail)->location),
        'anotherDetail.profile_description'=> filled(optional($user->anotherDetail)->profile_description),
        'anotherDetail.profile_picture'    => filled(optional($user->anotherDetail)->profile_picture),
    ];
    $settingsTotal     = count($settingsFields);
    $settingsCompleted = collect($settingsFields)->filter()->count();
    $settingsPct       = $settingsTotal ? ($settingsCompleted / $settingsTotal) : 0;

    // ---------- SECURITY bucket (20%) ----------
    // Consider "2FA enabled" and "email verified" as security items
    $securityFields = [
        'twofa_enabled'      => (bool) $user->twofa_enabled,
        'email_verified_at'  => !is_null($user->email_verified_at),
    ];
    $securityTotal     = count($securityFields);
    $securityCompleted = collect($securityFields)->filter()->count();
    $securityPct       = $securityTotal ? ($securityCompleted / $securityTotal) : 0;

    // ---------- KYC bucket (20%) ----------
    $kyc = $user->kycSubmission;
    // Consider KYC "complete" if a submission exists with the 3 files present.
    $kycComplete = $kyc
        && filled($kyc->id_front_path)
        && filled($kyc->id_back_path)
        && filled($kyc->selfie_path);

    // ---------- Weighted overall ----------
    $overallPercent = (int) round(
        ($settingsPct * 60) + ($securityPct * 20) + (($kycComplete ? 1 : 0) * 20)
    );

    // ---------- Which tabs are missing? ----------
    $missingTabs = [];
    if ($settingsCompleted < $settingsTotal) $missingTabs[] = 'settings';
    if ($securityCompleted < $securityTotal) $missingTabs[] = 'security';
    if (!$kycComplete)                       $missingTabs[] = 'kyc';

    // Which tab to open first on "Complete Profile"
    $firstTab = $missingTabs[0] ?? 'about';

    return view('UserAdmin.index', [
        'profileMeter' => [
            'percent'       => $overallPercent,
            'missing_tabs'  => $missingTabs,
            'first_tab'     => $firstTab,
            'breakdown'     => [
                'settings' => ['done' => $settingsCompleted, 'total' => $settingsTotal],
                'security' => ['done' => $securityCompleted, 'total' => $securityTotal],
                'kyc'      => ['done' => $kycComplete ? 1 : 0, 'total' => 1],
            ],
        ],
        'user' => $user,
    ]);
}


    private function redirectAfterAuth(Request $request)
    {
        if (session()->has('pending_team_invite')) {
            $token = session('pending_team_invite');
            session()->forget('pending_team_invite');
            return redirect()->route('invites.accept', ['token' => $token]);
        }
        return redirect()->route('user.admin.index');
    }

    public function showLogin(Request $request)
    {
        if (session()->has('user_id')) {
            return redirect()->route('user.admin.index');
        }
        return redirect()->route('home')->with('openModal', 'login');
    }

    public function register(UserAdminLoginRequest $request)
    {
        $data = $request->validated();

        $firstName = trim($data['first_name']);
        $lastName  = trim($data['last_name']);
        $phone     = isset($data['phone_number']) ? trim($data['phone_number']) : null;
        $email     = strtolower(trim($data['email']));
        $password  = trim($data['password']); // plain; hashed by cast

        // Unique 8-digit ID
        do {
            $uniqueId = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('unique_id', $uniqueId)->exists());

        DB::transaction(function () use ($firstName, $lastName, $phone, $email, $password, $uniqueId, $request) {
            $payload = [
                'unique_id'    => $uniqueId,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'phone_number' => $phone,
                'email'        => $email,
                'password'     => $password, // plain; hashed by cast
            ];

            if (Schema::hasColumn('users', 'terms_accepted_at')) {
                $payload['terms_accepted_at'] = now();
            }
            if (Schema::hasColumn('users', 'signup_ip')) {
                $payload['signup_ip'] = $request->ip();
            }
            if (Schema::hasColumn('users', 'signup_user_agent')) {
                $payload['signup_user_agent'] = Str::limit((string) $request->userAgent(), 255, '');
            }

            User::create($payload);
        });

       return back()
    ->with('success', 'Registration successful. Subscribe to our newsletter?')
    ->with('openModal', 'newsletter')
    ->with('prefill_email', $email);

    }

public function loginWithRecovery(\Illuminate\Http\Request $request)
{
    // Validate
    $v = \Validator::make($request->all(), [
        'email'         => ['required','email'],
        'recovery_code' => ['required','string','min:6','max:64'],
    ], [
        'recovery_code.required' => 'Please enter a recovery code.',
    ]);

    if ($v->fails()) {
        \Log::warning('RECOVERY_LOGIN_VALIDATION_FAIL', ['errs' => $v->errors()->toArray()]);
        return back()->withErrors($v)->withInput()->with('openModal', 'loginRecovery');
    }

    $email = strtolower(trim($request->input('email')));
    $code  = strtoupper(preg_replace('/\s+/', '', $request->input('recovery_code')));

    /** @var \App\Models\User|null $user */
    $user = \App\Models\User::where('email', $email)->first();

    if (!$user) {
        \Log::info('RECOVERY_LOGIN_NOUSER', ['email' => $email]);
        return back()
            ->withErrors(['email' => 'No account found for that email.'])
            ->withInput()
            ->with('openModal', 'loginRecovery');
    }

    if (empty($user->twofa_enabled)) {
        \Log::info('RECOVERY_LOGIN_NO2FA', ['user_id' => $user->id]);
        return back()
            ->withErrors(['recovery_code' => 'Recovery codes are not enabled on this account.'])
            ->withInput()
            ->with('openModal', 'loginRecovery');
    }

    $codes = (array) ($user->twofa_recovery_codes ?? []);
    if (!count($codes)) {
        \Log::info('RECOVERY_LOGIN_NOCODES', ['user_id' => $user->id]);
        return back()
            ->withErrors(['recovery_code' => 'No recovery codes available.'])
            ->withInput()
            ->with('openModal', 'loginRecovery');
    }

    // Match against stored codes (supports plain or hashed)
    $matchedIndex = null;
    foreach ($codes as $i => $stored) {
        $stored = (string) $stored;
        $isHash = str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2');
        $ok     = $isHash ? \Hash::check($code, $stored) : (strcasecmp($stored, $code) === 0);
        if ($ok) { $matchedIndex = $i; break; }
    }

    if ($matchedIndex === null) {
        \Log::info('RECOVERY_LOGIN_BADCODE', ['user_id' => $user->id]);
        return back()
            ->withErrors(['recovery_code' => 'Invalid recovery code.'])
            ->withInput()
            ->with('openModal', 'loginRecovery');
    }

    // Consume the code
    unset($codes[$matchedIndex]);
    $user->twofa_recovery_codes = array_values($codes);
    $user->save();

    // Login
    \Auth::login($user, true);
    $request->session()->regenerate();
    session(['user_id' => $user->id]);

    \Log::info('RECOVERY_LOGIN_OK', ['user_id' => $user->id]);

    return redirect()->route('user.admin.profile')
        ->with('success', 'Logged in via recovery code. For security, please update your password.')
        ->with('tab', 'security');
}


    public function login(UserAdminLoginRequest $request)
    {
        $creds = [
            'email'    => strtolower($request->input('email')),
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($creds, true)) {
            $request->session()->regenerate();
            session(['user_id' => Auth::id()]);
            return $this->redirectAfterAuth($request);
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials'])
            ->withInput()
            ->with('openModal', 'login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    // ---------- Forgot password (OTP) ----------
    public function forgot(UserAdminLoginRequest $request)
    {
        $email = strtolower($request->validated()['email']);

        $key = 'send-otp:' . $email . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withErrors(['email' => "Please wait {$seconds}s before retrying."])
                ->withInput()
                ->with('openModal', 'forgotPassword');
        }
        RateLimiter::hit($key, $this->resendCooldownSec);

        DB::table('password_otps')->where('email', $email)->whereNull('used_at')->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_otps')->insert([
            'email'        => $email,
            'code'         => $code,
            'expires_at'   => Carbon::now()->addMinutes($this->otpExpiresInMinutes),
            'attempts'     => 0,
            'used_at'      => null,
            'last_sent_at' => now(),
            'ip'           => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        Mail::to($email)->send(new OtpCodeMail($code));

        return back()
            ->with('emailForReset', $email)
            ->with('openModal', 'verifyOtp');
    }

    public function resend(Request $request)
    {
        $email = strtolower($request->input('email', ''));
        if (!$email) {
            return back()->with('openModal', 'forgotPassword');
        }

        $rec = DB::table('password_otps')
            ->where('email', $email)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (!$rec) {
            return back()
                ->withErrors(['email' => 'No pending reset found.'])
                ->with('openModal', 'forgotPassword');
        }

        if (!empty($rec->last_sent_at) && now()->diffInSeconds(Carbon::parse($rec->last_sent_at)) < $this->resendCooldownSec) {
            $left = $this->resendCooldownSec - now()->diffInSeconds(Carbon::parse($rec->last_sent_at));
            return back()
                ->withErrors(['email' => "Please wait {$left}s before resending."])
                ->with('emailForReset', $email)
                ->with('openModal', 'verifyOtp');
        }

        DB::table('password_otps')->where('id', $rec->id)->update(['last_sent_at' => now()]);
        Mail::to($email)->send(new OtpCodeMail($rec->code));

        return back()
            ->with('emailForReset', $email)
            ->with('openModal', 'verifyOtp');
    }

    public function verify(UserAdminLoginRequest $request)
    {
        $data  = $request->validated();
        $email = strtolower($data['email']);
        $code  = $data['code'];

        $rec = DB::table('password_otps')
            ->where('email', $email)
            ->where('code', $code)
            ->latest('id')
            ->first();

        if (!$rec) {
            return back()
                ->withErrors(['code' => 'Invalid code.'])
                ->with('emailForReset', $email)
                ->with('openModal', 'verifyOtp');
        }
        if ($rec->used_at) {
            return back()
                ->withErrors(['code' => 'Code already used.'])
                ->with('emailForReset', $email)
                ->with('openModal', 'verifyOtp');
        }
        if (Carbon::parse($rec->expires_at)->isPast()) {
            return back()
                ->withErrors(['code' => 'Code expired.'])
                ->with('emailForReset', $email)
                ->with('openModal', 'verifyOtp');
        }
        if ($rec->attempts >= $this->otpMaxAttempts) {
            return back()
                ->withErrors(['code' => 'Too many attempts. Request a new code.'])
                ->with('openModal', 'forgotPassword');
        }

        DB::table('password_otps')->where('id', $rec->id)->update([
            'attempts'   => $rec->attempts + 1,
            'updated_at' => now(),
        ]);

        return back()
            ->with('emailForReset', $email)
            ->with('otpForReset', $code)
            ->with('openModal', 'resetPassword');
    }

    public function reset(UserAdminLoginRequest $request)
    {
        $data  = $request->validated();
        $email = strtolower($data['email']);
        $code  = $data['code'];

        $rec = DB::table('password_otps')
            ->where('email', $email)
            ->where('code', $code)
            ->latest('id')
            ->first();

        if (!$rec || $rec->used_at || Carbon::parse($rec->expires_at)->isPast()) {
            return back()
                ->withErrors(['password' => 'Invalid or expired code.'])
                ->with('openModal', 'verifyOtp');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()
                ->withErrors(['email' => 'User not found.'])
                ->with('openModal', 'forgotPassword');
        }

        // IMPORTANT: your User model uses 'password' => 'hashed' cast.
        // Assign PLAIN password here; cast will hash it.
        $user->password = trim($data['password']);
        $user->save();

        DB::table('password_otps')->where('id', $rec->id)->update(['used_at' => now()]);
        DB::table('password_otps')->where('email', $email)->whereNull('used_at')->delete();

        return back()
            ->with('success', 'Password reset successful. Please log in.')
            ->with('openModal', 'login');
    }


public function wallet(Request $request)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $user->load('anotherDetail');

    $country      = Country::find($user->country_id);
    $currencyCode = $country?->currency ?: ($user->currency ?: 'USD');
    $symbol       = $country?->currency_symbol ?: $currencyCode;

    $recent = BalanceTransaction::where('user_id', $user->id)
        ->orderByDesc('id')->limit(10)->get();

    return view('UserAdmin.wallet', [
        'user'         => $user,
        'symbol'       => $symbol,
        'currencyCode' => $currencyCode,
        'recent'       => $recent,
    ]);
}


public function walletTransactionsJson(Request $request)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $q = BalanceTransaction::where('user_id', $user->id);

    if ($type = $request->get('type'))   $q->where('type', $type);
    if ($status = $request->get('status')) $q->where('status', $status);
    if ($from = $request->get('from'))   $q->whereDate('created_at', '>=', $from);
    if ($to = $request->get('to'))       $q->whereDate('created_at', '<=', $to);

    $data = $q->orderByDesc('id')->paginate(25);

    return response()->json([
        'data'       => $data->items(),
        'total'      => $data->total(),
        'per_page'   => $data->perPage(),
        'current'    => $data->currentPage(),
        'last_page'  => $data->lastPage(),
    ]);
}

public function createAddFundsOrder(Request $request, RazorpayGateway $gateway)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $validated = $request->validate([
        'amount'   => ['required','numeric','min:1'], // major units
        'currency' => ['required','string','max:4'],  // 'INR' or 'USD'
    ]);

    $amountMinor = (int) round($validated['amount'] * 100);
    $order = $gateway->createOrder(
        receipt: 'AF-'.$user->id.'-'.now()->format('YmdHis'),
        amountMinor: $amountMinor,
        currency: $validated['currency']
    );

    return response()->json([
        'order'    => $order,
        'key'      => config('services.razorpay.key'),
        'user'     => ['name'=>$user->name, 'email'=>$user->email],
    ]);
}

public function handleAddFundsCallback(Request $request, RazorpayGateway $gateway)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $validated = $request->validate([
        'razorpay_order_id'   => 'required|string',
        'razorpay_payment_id' => 'required|string',
        'razorpay_signature'  => 'required|string',
        'amount'              => 'required|numeric',
        'currency_symbol'     => 'required|string|max:8',
    ]);

    $ok = $gateway->verifySignature(
        $validated['razorpay_order_id'],
        $validated['razorpay_payment_id'],
        $validated['razorpay_signature']
    );

    if (!$ok) {
        BalanceTransaction::create([
            'user_id'         => $user->id,
            'type'            => 'credit',
            'amount'          => $validated['amount'],
            'currency_symbol' => $validated['currency_symbol'],
            'gateway'         => 'razorpay',
            'gateway_ref'     => $validated['razorpay_payment_id'],
            'status'          => 'failed',
            'meta'            => ['order_id'=>$validated['razorpay_order_id']],
        ]);
        return response()->json(['success'=>false, 'message'=>'Signature verification failed'], 422);
    }

    DB::transaction(function () use ($user, $validated) {
        BalanceTransaction::create([
            'user_id'         => $user->id,
            'type'            => 'credit',
            'amount'          => $validated['amount'],
            'currency_symbol' => $validated['currency_symbol'],
            'gateway'         => 'razorpay',
            'gateway_ref'     => $validated['razorpay_payment_id'],
            'status'          => 'success',
            'meta'            => ['order_id'=>$validated['razorpay_order_id']],
        ]);

        // mirror to users.wallet
        $user->refresh();
        $user->wallet = bcadd((string)$user->wallet, (string)$validated['amount'], 2);
        $user->save();
    });

    return response()->json(['success'=>true]);
}

public function requestWithdraw(Request $request, RazorpayXPayouts $rzx, PaypalPayouts $ppx)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    // Hard guards
    $kycApproved = \DB::table('user_kyc_submissions')->where('user_id',$user->id)->where('status','approved')->exists();
    if (!$kycApproved) return response()->json(['success'=>false,'reason'=>'kyc','message'=>'Withdrawal requires KYC approval.'], 422);

    $twofaEnabled = (int)($user->twofa_enabled ?? 0) === 1 && !empty($user->twofa_secret);
    if (!$twofaEnabled) return response()->json(['success'=>false,'reason'=>'2fa','message'=>'Enable 2FA to withdraw.'], 422);

    // Validate
    $v = $request->validate([
        'amount'            => ['required','numeric','min:1'],
        'currency_symbol'   => ['required','string','max:8'],
        'payout_account_id' => ['nullable','integer'], // if null, use default
    ]);

    // Find payout account
    $payoutAcc = UserPayoutAccount::where('user_id',$user->id)
        ->when($v['payout_account_id'] ?? null, fn($q) => $q->where('id',$v['payout_account_id']))
        ->when(!($v['payout_account_id'] ?? null), fn($q) => $q->where('is_default', true))
        ->first();

    if (!$payoutAcc) return response()->json(['success'=>false,'message'=>'No payout account. Please add one first.'], 422);

    // Currency code (by user country; fallback USD)
    $country = \DB::table('countries')->where('id',$user->country_id)->first();
    $currencyCode = $country->currency ?? ($user->currency ?? 'USD');
    $symbol       = $v['currency_symbol'] ?? $this->currencySymbol($currencyCode);

    // Throttle basic abuse
    if (app()->bound('router')) {
        // Optionally use throttle middleware on route
    }

    // HOLD: create pending ledger (no wallet deduction yet)
    $reference = 'WD-'.$user->id.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(3));

    // Deny if sum(pending debits) + amount > wallet
    $pendingSum = (float) \App\Models\BalanceTransaction::where('user_id',$user->id)
        ->where('type','debit')->where('category','withdraw')->where('status','pending')->sum('amount');
    $available = (float)($user->wallet ?? 0) - $pendingSum;
    if ($v['amount'] > $available) {
        return response()->json(['success'=>false, 'message'=>'Insufficient available balance (considering pending withdrawals).'], 422);
    }

    $txn = \App\Models\BalanceTransaction::create([
        'user_id'         => $user->id,
        'type'            => 'debit',
        'category'        => 'withdraw',
        'amount'          => $v['amount'],
        'currency_symbol' => $symbol,
        'currency_code'   => $currencyCode,
        'gateway'         => $payoutAcc->type === 'paypal' ? 'paypal' : 'razorpayx',
        'payout_account_id'=> $payoutAcc->id,
        'status'          => 'pending', // HOLD
        'reference'       => $reference,
        'counterparty'    => $payoutAcc->type === 'paypal' ? $payoutAcc->paypal_email : ($payoutAcc->type==='upi' ? $payoutAcc->upi_vpa : $payoutAcc->maskedAccount()),
        'meta'            => ['payout_type'=>$payoutAcc->type],
    ]);

    // Trigger payout (synchronously for now)
    try {
        if ($payoutAcc->type === 'paypal') {
            // PayPal Payouts: send to PayPal email (not bank)
            $pr = $ppx->createPayout($payoutAcc->paypal_email, (string)$v['amount'], $currencyCode, $reference);
            $gatewayRef = $pr['batch_header']['payout_batch_id'] ?? null;
        } else {
            // RazorpayX bank/upi
            // RazorpayX requires INR; if user's currency != INR, convert using your converter before passing amountMinor.
            $fx = new \App\Services\Currency\CurrencyConverter();
            $amtForGateway = (strtoupper($currencyCode) === 'INR') ? (float)$v['amount'] : (float)$fx->convert($v['amount'], $currencyCode, 'INR');
            $minor = (int) round($amtForGateway * 100);
            $pr = $rzx->createPayout($payoutAcc, $minor, 'INR', $reference);
            $gatewayRef = $pr['id'] ?? null;
        }
    } catch (\Throwable $e) {
        // mark failed; no wallet deduction
        $txn->status = 'failed';
        $txn->meta = array_merge($txn->meta ?? [], ['error' => $e->getMessage()]);
        $txn->save();
        return response()->json(['success'=>false, 'message'=>'Payout failed: '.$e->getMessage()], 422);
    }

    // Finalize: deduct wallet & mark success (atomic)
    \DB::transaction(function() use ($user, $txn, $gatewayRef) {
        $u = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
        if ((float)$u->wallet < (float)$txn->amount) {
            // Defensive: balance changed during payout → mark failed and stop
            $txn->status = 'failed';
            $txn->meta = array_merge($txn->meta ?? [], ['error'=>'Balance changed during payout finalize']);
            $txn->save();
            throw new \RuntimeException('Insufficient balance at finalize');
        }
        $u->wallet = bcsub((string)$u->wallet, (string)$txn->amount, 2);
        $u->save();

        $txn->status = 'success';
        $txn->gateway_ref = $gatewayRef;
        $txn->save();
    });

    return response()->json(['success'=>true, 'message'=>'Withdrawal successful.']);
}



// + use statements


// LIST
public function payoutAccounts(Request $r)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $accs = UserPayoutAccount::where('user_id', $user->id)->orderByDesc('is_default')->get()
      ->map(function($a){
        return [
          'id' => $a->id,
          'type' => $a->type,
          'holder_name' => $a->holder_name,
          'masked_account' => $a->maskedAccount(),
          'ifsc' => $a->ifsc,
          'upi_vpa' => $a->upi_vpa,
          'paypal_email' => $a->paypal_email,
          'bank_name' => $a->bank_name,
          'branch' => $a->branch,
          'is_default' => (bool)$a->is_default,
        ];
      })->values();

    return response()->json(['data' => $accs]);
}

// CREATE/UPDATE
public function savePayoutAccount(Request $r)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $id = $r->input('id');
    $type = $r->input('type', 'bank');

    $rules = [
        'type'         => ['required', Rule::in(['bank','upi','paypal'])],
        'holder_name'  => ['required','string','max:120'],
        'is_default'   => ['nullable','boolean'],
    ];
    if ($type === 'bank') {
        $rules += [
            'account_number' => ['required','string','min:9','max:32','regex:/^[0-9A-Za-z-]+$/'],
            'confirm_account'=> ['required','same:account_number'],
            'ifsc'           => ['required','regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name'      => ['nullable','string','max:120'],
            'branch'         => ['nullable','string','max:120'],
        ];
    } elseif ($type === 'upi') {
        $rules += [ 'upi_vpa' => ['required','regex:/^[a-zA-Z0-9.\-_]{2,}@[a-zA-Z]{2,}$/'] ];
    } else { // paypal
        $rules += [ 'paypal_email' => ['required','email','max:191'] ];
    }

    $data = $r->validate($rules);

    $acc = $id
        ? UserPayoutAccount::where('user_id',$user->id)->where('id',$id)->firstOrFail()
        : new UserPayoutAccount(['user_id' => $user->id]);

    $acc->fill([
        'type'         => $data['type'],
        'holder_name'  => $data['holder_name'],
        'account_number'=> $data['account_number'] ?? null,
        'ifsc'         => $data['ifsc'] ?? null,
        'bank_name'    => $data['bank_name'] ?? null,
        'branch'       => $data['branch'] ?? null,
        'upi_vpa'      => $data['upi_vpa'] ?? null,
        'paypal_email' => $data['paypal_email'] ?? null,
    ]);
    $acc->save();

    if ($r->boolean('is_default')) {
        UserPayoutAccount::where('user_id',$user->id)->update(['is_default' => false]);
        $acc->is_default = true;
        $acc->save();
    }

    return response()->json(['ok'=>true, 'id'=>$acc->id]);
}

// DELETE
public function deletePayoutAccount(Request $r, int $acc)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $a = UserPayoutAccount::where('user_id',$user->id)->where('id',$acc)->firstOrFail();
    if ($a->is_default && UserPayoutAccount::where('user_id',$user->id)->count() > 1) {
        return response()->json(['ok'=>false,'message'=>'Set another account as default before deleting this one.'], 422);
    }
    $a->delete();
    return response()->json(['ok'=>true]);
}

// MAKE DEFAULT
public function makeDefaultPayoutAccount(Request $r, int $acc)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $a = UserPayoutAccount::where('user_id',$user->id)->where('id',$acc)->firstOrFail();
    UserPayoutAccount::where('user_id',$user->id)->update(['is_default' => false]);
    $a->is_default = true;
    $a->save();
    return response()->json(['ok'=>true]);
}



// Create PayPal order
public function createPaypalOrder(Request $r, \App\Services\Payments\PaypalGateway $pp)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $v = $r->validate([
        'amount'   => ['required','numeric','min:1'],
        'currency' => ['required','string','size:3'], // e.g., INR or USD
    ]);
    $reference = 'AF-PP-'.$user->id.'-'.now()->format('YmdHis');

    $order = $pp->createOrder($v['currency'], (string)$v['amount'], $reference);

    // Create a pending ledger row (hold) with our reference; finalize on capture
    \App\Models\BalanceTransaction::create([
        'user_id'         => $user->id,
        'type'            => 'credit',
        'category'        => 'add_funds',
        'amount'          => $v['amount'],
        'currency_symbol' => $this->currencySymbol($v['currency']),
        'currency_code'   => $v['currency'],
        'gateway'         => 'paypal',
        'reference'       => $reference,
        'status'          => 'pending',
        'meta'            => ['paypal_order' => $order['id'] ?? null],
    ]);

    return response()->json(['orderID' => $order['id'] ?? null]);
}

// Capture PayPal order
public function capturePaypalOrder(Request $r, \App\Services\Payments\PaypalGateway $pp)
{
    $user = auth()->user() ?? User::find(session('user_id'));
    abort_if(!$user, 403);

    $v = $r->validate(['orderID' => 'required|string']);

    $cap = $pp->captureOrder($v['orderID']);
    // Extract captured amount/currency and capture id
    $purchase = $cap['purchase_units'][0] ?? null;
    $captures = $purchase['payments']['captures'][0] ?? null;

    abort_if(!$captures, 422, 'Capture failed');
    $captureId = $captures['id'];
    $amountV   = (float)($captures['amount']['value'] ?? 0);
    $currency  = (string)($captures['amount']['currency_code'] ?? 'USD');

    // Idempotency guard
    if (\App\Models\BalanceTransaction::where('gateway_ref',$captureId)->exists()) {
        return response()->json(['success'=>true]); // already processed
    }

    \DB::transaction(function() use ($user, $captureId, $amountV, $currency, $v) {
        // Find pending row by paypal order id (in meta) or reference
        $row = \App\Models\BalanceTransaction::where('user_id',$user->id)
            ->where('category','add_funds')->where('status','pending')
            ->where('gateway','paypal')
            ->whereJsonContains('meta->paypal_order', $v['orderID'])
            ->lockForUpdate()
            ->first();

        if (!$row) {
            // create fresh if pending wasn't there (fallback)
            $row = new \App\Models\BalanceTransaction([
                'user_id' => $user->id,
                'type'    => 'credit',
                'category'=> 'add_funds',
                'status'  => 'pending',
                'gateway' => 'paypal',
            ]);
        }

        $row->amount          = $amountV;
        $row->currency_code   = $currency;
        $row->currency_symbol = $this->currencySymbol($currency);
        $row->gateway_ref     = $captureId;
        $row->status          = 'success';
        $row->meta = array_merge($row->meta ?? [], ['paypal_capture' => $v['orderID']]);
        $row->save();

        // Credit wallet
        $u = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
        $u->wallet = bcadd((string)$u->wallet, (string)$amountV, 2);
        $u->save();
    });

    return response()->json(['success'=>true]);
}

// Minimal helper — reads symbol from countries table, falls back to the code itself
protected function currencySymbol(string $code): string
{
    $code = strtoupper(trim($code));
    return \App\Models\Country::where('currency', $code)->value('currency_symbol') ?? $code;
}

public function paypalCreateOrder(Request $r, PayPalGateway $pp)
{
    $r->validate(['amount'=>'required|numeric|min:1','currency'=>'required|string|size:3']);

    $out = $pp->createOrder((float)$r->amount, strtoupper($r->currency), [
        'return_url' => route('user.admin.wallet.paypal.return'),
        'cancel_url' => route('user.admin.wallet.paypal.cancel'),
    ]);

    if (empty($out['orderID'])) {
        return response()->json(['message'=>'Failed to create PayPal order'], 422);
    }

    return response()->json([
        'orderID'    => $out['orderID'],
        'approveUrl' => $out['approveUrl'],
    ]);
}

public function paypalReturn(Request $r)
{
    $token = $r->query('token', ''); // PayPal sends ?token=<orderID>
    $html = '<!doctype html><html><body><script>
      (function(){
        var t='.json_encode($token).';
        try { window.opener && window.opener.postMessage({type:"PAYPAL_APPROVED", orderID: t}, "*"); } catch(e){}
        window.close();
      })();
    </script>Approved. You can close this window.</body></html>';
    return response($html);
}

public function paypalCancel()
{
    $html = '<!doctype html><html><body><script>
      (function(){
        try { window.opener && window.opener.postMessage({type:"PAYPAL_CANCELLED"}, "*"); } catch(e){}
        window.close();
      })();
    </script>Cancelled. You can close this window.</body></html>';
    return response($html);
}


public function paypalCapture(Request $r, PayPalGateway $pp)
{
    $r->validate(['orderID' => 'required|string']);
    $user = auth()->user() ?? \App\Models\User::find(session('user_id'));
    abort_if(!$user, 403);

    $cap = $pp->captureOrder($r->orderID);

    $status = $cap['status'] ?? null; // should be COMPLETED
    if ($status !== 'COMPLETED') {
        return response()->json(['message' => 'Capture not completed'], 422);
    }

    $purchase = $cap['purchase_units'][0] ?? [];
    $captures = $purchase['payments']['captures'][0] ?? [];
    $value    = (float)($captures['amount']['value'] ?? 0);
    $currency = (string)($captures['amount']['currency_code'] ?? 'USD');
    $txId     = (string)($captures['id'] ?? null);

    if ($value <= 0 || !$txId) {
        return response()->json(['message' => 'Invalid capture data'], 422);
    }

    // Resolve currency symbol (Country table or fallback map)
    $symbol = Country::where('currency', $currency)->value('currency_symbol');
    if (!$symbol) {
        $map = ['USD'=>'$', 'INR'=>'₹','EUR'=>'€','GBP'=>'£','JPY'=>'¥','AUD'=>'A$','CAD'=>'C$','SGD'=>'S$','AED'=>'د.إ'];
        $symbol = $map[$currency] ?? $currency;
    }

    DB::transaction(function () use ($user, $value, $symbol, $txId, $r) {
        // Idempotency: avoid double credit on retry
        $exists = BalanceTransaction::where('gateway', 'paypal')
            ->where('gateway_ref', $txId)->exists();
        if ($exists) return;

        BalanceTransaction::create([
            'user_id'         => $user->id,
            'type'            => 'credit',
            'amount'          => $value,
            'currency_symbol' => $symbol,
            'gateway'         => 'paypal',
            'gateway_ref'     => $txId,
            'status'          => 'success',
            'meta'            => ['order_id' => $r->orderID],
        ]);

        $user->refresh();
        $user->wallet = bcadd((string)$user->wallet, (string)$value, 2);
        $user->save();
    });

    return response()->json(['success' => true]);
}


}

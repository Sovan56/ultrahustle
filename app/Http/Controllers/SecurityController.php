<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestEmailOtpRequest;
use App\Http\Requests\VerifyEmailOtpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Mail, Crypt, Log, Validator};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Requests\TwoFaEnableRequest;

use App\Models\User;
use App\Models\EmailOtp;

class SecurityController extends Controller
{
    /* -------------------------------------------------
     | Small helpers
     * -------------------------------------------------*/
    private function mustHave2FAEnabledOrBack(User $user)
    {
        if (empty($user->twofa_enabled)) {
            return back()
                ->withErrors(['twofa' => 'For security, please enable 2FA first, then you can change email & password.'])
                ->with('tab', 'security');
        }
        return null;
    }

    private function generateRecoveryCodes(int $count = 8, bool $hash = true): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Grouped for readability: XXXX-XXXX
            $raw = strtoupper(Str::random(8));
            $raw = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
            $codes[] = $hash ? Hash::make($raw) : $raw;
        }
        return $codes;
    }

    /* -------------------------------------------------
     | Change password (2FA required)
     * -------------------------------------------------*/
    public function changePassword(Request $request)
    {
        $user = Auth::user() ?? User::find(session('user_id'));
        if (!$user) return redirect()->route('session.expired');

        if ($block = $this->mustHave2FAEnabledOrBack($user)) {
            return $block;
        }

        $v = Validator::make($request->all(), [
            'old_password'     => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'regex:/\d/', 'different:old_password'],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'new_password.regex' => 'New password must contain at least one number.',
        ]);

        if ($v->fails()) {
            Log::warning('PASSWORD_CHANGE_VALIDATION_FAIL', [
                'user_id' => $user->id,
                'errors'  => $v->errors()->toArray(),
            ]);
            return back()->withErrors($v)->withInput()->with('tab', 'security');
        }

        if (!Hash::check($request->input('old_password'), $user->password)) {
            Log::warning('PASSWORD_CHANGE_OLD_MISMATCH', ['user_id' => $user->id]);
            return back()
                ->withErrors(['old_password' => 'The current password is incorrect.'])
                ->withInput()
                ->with('tab', 'security');
        }

        // User model casts 'password' => 'hashed'
        $user->password = $request->input('new_password');
        $user->save();

        return back()->with('success', 'Password updated successfully.')->with('tab', 'security');
    }

    /* -------------------------------------------------
     | Email change via OTP (2FA required)
     * -------------------------------------------------*/
    public function requestEmailOtp(RequestEmailOtpRequest $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        if ($block = $this->mustHave2FAEnabledOrBack($user)) {
            return $block;
        }

        $code = (string) random_int(100000, 999999);

        EmailOtp::where('user_id', $user->id)
            ->where('new_email', $request->new_email)
            ->update(['used' => true]);

        EmailOtp::create([
            'user_id'    => $user->id,
            'new_email'  => $request->new_email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::raw("Your email change OTP is: {$code}. It expires in 10 minutes.", function ($m) use ($request) {
                $m->to($request->new_email)->subject('Verify your new email');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['new_email' => 'Failed to send OTP email.'])->with('tab', 'security');
        }

        return back()->with('success', 'OTP sent to the new email. Check your inbox.')->with('tab', 'security');
    }

    public function verifyEmailOtp(VerifyEmailOtpRequest $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        if ($block = $this->mustHave2FAEnabledOrBack($user)) {
            return $block;
        }

        $otp = EmailOtp::where('user_id', $user->id)
            ->where('new_email', $request->new_email)
            ->where('used', false)
            ->latest()->first();

        if (!$otp || $otp->isExpired() || $otp->code !== $request->code) {
            return back()->withErrors(['code' => 'Invalid or expired OTP.'])->with('tab', 'security');
        }

        $otp->used = true;
        $otp->save();

        $user->email = $request->new_email;
        if (Schema::hasColumn('users', 'email_verified_at')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return back()->with('success', 'Email updated. Please verify the new email if required.')->with('tab', 'security');
    }

    /* -------------------------------------------------
     | 2FA (TOTP) minimal implementation
     * -------------------------------------------------*/
    protected function verifyTotp(string $secretBase32, string $code, int $window = 1): bool
    {
        $secret = $this->base32Decode($secretBase32);
        $timeStep = 30;
        $t = floor(time() / $timeStep);

        for ($i = -$window; $i <= $window; $i++) {
            $calc = $this->hotp($secret, $t + $i);
            if (hash_equals($calc, $code)) return true;
        }
        return false;
    }

    protected function hotp(string $secret, int $counter): string
    {
        $binCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binCounter, $secret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = (ord($hash[$offset]) & 0x7F) << 24 |
            (ord($hash[$offset + 1]) & 0xFF) << 16 |
            (ord($hash[$offset + 2]) & 0xFF) << 8 |
            (ord($hash[$offset + 3]) & 0xFF);
        $code = $truncated % 1000000;
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    protected function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper($b32);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        for ($i = 0, $l = strlen($b32); $i < $l; $i++) {
            $val = strpos($alphabet, $b32[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $result;
    }

    public function twofaSetup(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];

        $issuer = rawurlencode(config('app.name', 'Laravel'));
        $label  = rawurlencode($user->email);
        $otpauth = "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&period=30&digits=6";

        return response()->json([
            'secret'  => $secret,
            'otpauth' => $otpauth,
        ]);
    }

private function makeRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // 10-char, grouped as XXXX-XXXXXX for readability
            $raw   = Str::upper(Str::random(10));
            $codes[] = substr($raw, 0, 4) . '-' . substr($raw, 4);
        }
        return $codes;
    }

  public function twofaEnable(TwoFaEnableRequest $request)
    {
        $user = Auth::user() ?? User::find(session('user_id'));
        abort_if(!$user, 403);

        $secret = $request->input('secret');
        if (!$secret) {
            return back()->withErrors(['code' => 'Missing secret. Generate the QR first.'])->with('tab', 'security');
        }

        if (!$this->verifyTotp($secret, $request->code, 1)) {
            return back()->withErrors(['code' => 'Invalid 2FA code.'])->with('tab', 'security');
        }

        // Persist secret + flag
        $user->twofa_secret  = Crypt::encryptString($secret);
        $user->twofa_enabled = true;

        // Generate ONCE
        $plain  = $this->makeRecoveryCodes(8);                 // plaintext (for one-time display/download)
        $hashed = array_map(fn ($c) => hash('sha256', $c), $plain); // store hashed in DB

        // Save to DB (cast to array/json on the model)
        $user->twofa_recovery_codes = $hashed;
        $user->save();

        // Save plaintext in session for immediate download; not stored permanently
        session()->put('recovery_plain', $plain);

        return back()
            ->with('success', '2FA enabled. Save your recovery codes now (download or print).')
            ->with('tab', 'security');
    }


    public function twofaDisable(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        $request->validate(['password' => ['required']]);
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password is incorrect.'])->with('tab', 'security');
        }

        $user->twofa_enabled = false;
        $user->twofa_secret = null;
        $user->twofa_recovery_codes = null;
        $user->save();

        return back()->with('success', '2FA disabled.')->with('tab', 'security');
    }

    /* -------------------------------------------------
     | Manage recovery codes
     * -------------------------------------------------*/
   // SecurityController.php

public function recoveryRegenerate(Request $request)
{
    $user = Auth::user();
    abort_if(!$user, 403);

    if (empty($user->twofa_enabled)) {
        return back()
            ->withErrors(['twofa' => 'Enable 2FA to manage recovery codes.'])
            ->with('tab', 'security');
    }

    // Build a new set of codes (plaintext for UI + hashed for DB)
    $plain = [];
    $hashed = [];

    for ($i = 0; $i < 8; $i++) {
        $code = strtoupper(Str::random(10)); // e.g. A1B2C3D4E5
        $plain[]  = $code;
        $hashed[] = Hash::make($code);
    }

    // Save hashed set to DB
    $user->twofa_recovery_codes = $hashed; // cast to array/json in model
    $user->save();

    // IMPORTANT: persist plaintext set across multiple requests
    session()->put('recovery_plain', $plain);
    // (optional) also surface a success flash
    session()->flash('success', 'Recovery codes regenerated. Download or copy them now.');

    return back()->with('tab', 'security');
}

    // SecurityController.php

public function recoveryDownload(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        if (empty($user->twofa_enabled)) {
            return back()
                ->withErrors(['twofa' => 'Enable 2FA to use recovery codes.'])
                ->with('tab', 'security');
        }

        // Try to use the most recent plaintext set in session
        $plain = session('recovery_plain', []);

        // If not present, regenerate a fresh set and overwrite DB hashed
        if (empty($plain) || !is_array($plain)) {
            $plain  = $this->makeRecoveryCodes(8);
            $hashed = array_map(fn ($c) => hash('sha256', $c), $plain);

            $user->twofa_recovery_codes = $hashed;
            $user->save();

            session()->put('recovery_plain', $plain);

            // Optional: notify the user we regenerated
            session()->flash('info', 'No recent plaintext codes found. A new set was generated and older codes were invalidated.');
        }

        $lines = [];
        $lines[] = 'Your recovery codes (each can be used once):';
        $lines[] = '------------------------------------------';
        foreach ($plain as $c) $lines[] = $c;
        $lines[] = '';
        $lines[] = 'Keep this file safe. Anyone with these codes can access your account.';

        $filename = 'recovery-codes-' . now()->format('Ymd-His') . '.txt';

        // Stream the file; DO NOT call ->send(), just return the response.
        return response()->streamDownload(function () use ($lines) {
            echo implode("\r\n", $lines);
        }, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

}

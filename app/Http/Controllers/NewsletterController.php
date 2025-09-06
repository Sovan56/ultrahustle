<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($data['email']));

        // detect ajax
        $isAjax =
            $request->ajax() ||
            $request->wantsJson() ||
            $request->expectsJson() ||
            str_contains(strtolower($request->header('accept', '')), 'application/json') ||
            $request->header('X-Requested-With') === 'XMLHttpRequest';

        // ✅ Check if already subscribed
        if (NewsletterSubscriber::where('email', $email)->exists()) {
            if ($isAjax) {
                return response()->json([
                    'result' => 'error',
                    'msg'    => 'This email is already subscribed.',
                ], 409);
            }

            return back()
                ->withInput()
                ->with('newsletter_error', 'This email is already subscribed.')
                ->with('openModal', 'newsletter');
        }

        try {
            // Save to DB
            NewsletterSubscriber::create(['email' => $email]);

            // Optional: Send confirmation email
            Mail::send('emails.newsletter', ['email' => $email], function ($m) use ($email) {
                $m->to($email)->subject('Thanks for subscribing!');
            });

            if ($isAjax) {
                return response()->json([
                    'result' => 'success',
                    'msg'    => 'Subscribed successfully.',
                ], 200);
            }

            return back()
                ->with('success', 'Subscribed successfully.')
                ->with('openModal', 'login');
        } catch (\Throwable $e) {
            if ($isAjax) {
                return response()->json([
                    'result' => 'error',
                    'msg'    => 'Something went wrong. Please try again.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('newsletter_error', 'Something went wrong. Please try again.')
                ->with('openModal', 'newsletter');
        }
    }
}

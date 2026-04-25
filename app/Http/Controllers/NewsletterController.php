<?php

namespace App\Http\Controllers;

use App\Models\EmailSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Handle newsletter subscription from the footer form.
     * Saves the email into email_subscribers table.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ], [
            'email.required' => 'يرجى إدخال بريدك الإلكتروني.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first('email'),
                ], 422);
            }
            return back()->with('error', $validator->errors()->first('email'));
        }

        $email = strtolower(trim($request->input('email')));

        // Check if already subscribed
        $existing = EmailSubscriber::where('email', $email)->first();

        if ($existing) {
            if ($existing->email_status === 'unsubscribed') {
                // Re-subscribe
                $existing->update([
                    'email_status' => 'valid',
                    'is_active' => true,
                ]);

                $msg = 'تم إعادة تفعيل اشتراكك بنجاح! شكراً لعودتك.';
            } else {
                $msg = 'بريدك الإلكتروني مسجل بالفعل في القائمة البريدية.';
            }
        } else {
            // New subscriber
            EmailSubscriber::create([
                'email' => $email,
                'email_status' => 'valid',
                'is_active' => true,
                'is_verified' => false,
                'validation_score' => 50,
            ]);

            $msg = 'تم اشتراكك بنجاح في القائمة البريدية! شكراً لك.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}
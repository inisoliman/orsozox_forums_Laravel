<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EmailSubscriber;

class UnsubscribeController extends Controller
{
    /**
     * Handle unsubscribe requests via magic link.
     */
    public function unsubscribe($hash)
    {
        // Simple hash matching (MD5 of email as configured in the Job)
        // For production, a signed route is much safer, but this works for basic legacy systems.
        // We will do a full table scan match (since email list is large, we should probably index the hash or use signed routes.
        // For performance on 200k, we shouldn't scan all md5s. We'll use the precise email as a signature payload, or
        // as implemented in the Job: md5($subscriber->email).

        // Let's find the subscriber by MD5(email). Since we can't search MD5 natively efficiently without raw,
        // we'll use a direct raw where.

        $subscriber = EmailSubscriber::whereRaw('MD5(email) = ?', [$hash])->first();

        if (!$subscriber) {
            return response('لم يتم العثور على البريد الإلكتروني أو الرابط غير صالح.', 404);
        }

        $subscriber->update([
            'email_status' => 'unsubscribed',
            'is_active' => false,
            'updated_at' => now(),
        ]);

        return response('<html><body style="font-family: Arial, sans-serif; text-align: center; margin-top: 50px;">
            <h2>تم إلغاء الاشتراك بنجاح</h2>
            <p>لن تتلقى أي رسائل بريدية ترويجية منا بعد الآن.</p>
        </body></html>');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\SeoHelper;

class PageController extends Controller
{
    /**
     * صفحة من نحن (About Us)
     */
    public function about()
    {
        $seoData = [
            'title' => SeoHelper::title('من نحن'),
            'description' => SeoHelper::description('تعرف على رؤية ورسالة المنتدى الدينية وفريق العمل القائم على مراجعة المحتوى.'),
            'url' => route('page.about'),
        ];

        return view('pages.about', compact('seoData'));
    }

    /**
     * صفحة سياسة التحرير (Editorial Policy)
     */
    public function editorial()
    {
        $seoData = [
            'title' => SeoHelper::title('سياسة التحرير والمراجعة'),
            'description' => SeoHelper::description('اطلع على سياسة النشر، معايير القبول، وموثوقية المصادر الدينية المتبعة في المنتدى.'),
            'url' => route('page.editorial'),
        ];

        return view('pages.editorial', compact('seoData'));
    }

    /**
     * صفحة اتصل بنا (Contact Us)
     */
    public function contact()
    {
        $seoData = [
            'title' => SeoHelper::title('اتصل بنا'),
            'description' => SeoHelper::description('تواصل مع إدارة المنتدى لأي استفسار، اقتراح، أو طلب تصحيح محتوى.'),
            'url' => route('page.contact'),
        ];

        return view('pages.contact', compact('seoData'));
    }

    /**
     * معالجة إرسال نموذج اتصل بنا
     */
    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        $data = $request->only('name', 'email', 'message');

        try {
            \Illuminate\Support\Facades\Mail::raw("رسالة جديدة من: {$data['name']}\nالبريد: {$data['email']}\n\nالرسالة:\n{$data['message']}", function ($message) use ($data) {
                $message->to('orsozox@gmail.com')
                    ->subject('رسالة جديدة من نموذج الاتصال - منتدى أرثوذكس')
                    ->replyTo($data['email'], $data['name']);
            });
            return back()->with('success', 'تم إرسال رسالتك بنجاح. شكراً لتواصلك معنا!');
        } catch (\Exception $e) {
            return back()->with('error', 'عذراً، حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة لاحقاً.')->withInput();
        }
    }

    /**
     * صفحة سياسة الخصوصية
     */
    public function privacy()
    {
        $seoData = [
            'title' => SeoHelper::title('سياسة الخصوصية'),
            'description' => SeoHelper::description('سياسة الخصوصية وشروط الاستخدام في المنتدى لضمان سرية بياناتك الشخصية.'),
            'url' => route('page.privacy'),
        ];

        return view('pages.privacy', compact('seoData'));
    }
}

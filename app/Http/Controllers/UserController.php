<?php

namespace App\Http\Controllers;

use App\Helpers\HtmlSanitizer;
use App\Models\Post;
use App\Models\User;
use App\Models\Thread;
use App\Models\VisitorMessage;
use App\Services\LocalAI\SpamShieldService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly SpamShieldService $spamShield)
    {
    }

    /**
     * عرض ملف العضو الشخصي
     */
    public function show(int $id)
    {
        $user = User::findOrFail($id);

        $threads = Thread::where('postuserid', $id)
            ->visible()
            ->orderBy('dateline', 'desc')
            ->with('forum')
            ->paginate(15);

        // رسائل الزوار المنشورة على صفحة العضو
        $messages = VisitorMessage::where('userid', $id)
            ->visible()
            ->orderBy('dateline', 'desc')
            ->with('author')
            ->paginate(20);

        // ردود العضو (المشاركات التي ليست أول مشاركة) — لتبويب "الردود"
        $replies = Post::where('userid', $id)
            ->where('visible', 1)
            ->whereNotIn('postid', function ($query) {
                $query->select('firstpostid')->from('thread')->whereNotNull('firstpostid');
            })
            ->orderBy('dateline', 'desc')
            ->with(['thread.forum'])
            ->paginate(15);

        // إجماليات للترويسة
        $threadsTotal = (int) $threads->total();
        $repliesTotal = (int) $replies->total();
        $messagesTotal = (int) $messages->total();

        // رسائل زوار قيد المراجعة — تظهر فقط للأدمن والمشرف
        $pendingMessages = collect();
        if (auth()->check() && (auth()->user()->is_admin || auth()->user()->is_moderator)) {
            $pendingMessages = VisitorMessage::where('userid', $id)
                ->moderation()
                ->orderBy('dateline', 'desc')
                ->with('author')
                ->get();
        }

        return view('user.show', compact('user', 'threads', 'replies', 'messages', 'pendingMessages', 'threadsTotal', 'repliesTotal', 'messagesTotal'));
    }

    /**
     * إرسال رسالة زائر على صفحة عضو
     */
    public function storeVisitorMessage(Request $request, int $id)
    {
        $owner = User::findOrFail($id);
        $visitor = $request->user();

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'content.required' => 'محتوى الرسالة مطلوب.',
            'content.min' => 'الرسالة قصيرة جداً — 3 أحرف على الأقل.',
            'content.max' => 'الرسالة طويلة جداً — 2000 حرف كحد أقصى.',
        ]);

        $content = HtmlSanitizer::clean($validated['content']);
        abort_if(trim(strip_tags($content)) === '', 422, 'محتوى الرسالة مطلوب.');

        // فحص السبام: الرسائل عالية الخطورة تبقى في قائمة المراجعة بدل النشر الفوري
        $spamScore = $this->spamShield->calculateSpamScore($owner->username, $content);
        $state = $spamScore > 80 ? 'moderation' : 'visible';

        $message = VisitorMessage::create([
            'userid' => $owner->userid,
            'postuserid' => $visitor->userid,
            'postusername' => $visitor->username,
            'dateline' => time(),
            'state' => $state,
            'title' => '',
            'pagetext' => '<!-- HTML -->' . $content,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            // إرجاع HTML الرسالة الجديدة لإدراجها مباشرة في القائمة بدون إعادة تحميل الصفحة
            $html = '';
            if ($state === 'visible') {
                $html = view('user.partials.visitor-message', [
                    'message' => $message->load('author'),
                ])->render();
            }

            return response()->json([
                'success' => true,
                'visible' => $state === 'visible',
                'html' => $html,
                'message' => $state === 'visible'
                    ? 'تم نشر رسالتك على صفحة العضو.'
                    : 'تم استلام رسالتك وستظهر بعد مراجعتها.',
            ]);
        }

        return back()->with(
            $state === 'visible' ? 'success' : 'warning',
            $state === 'visible'
                ? 'تم نشر رسالتك على صفحة العضو.'
                : 'تم استلام رسالتك وستظهر بعد مراجعتها.'
        );
    }
}

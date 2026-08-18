<?php

namespace App\Http\Controllers;

use App\Helpers\HtmlSanitizer;
use App\Models\Forum;
use App\Models\ForumPermission;
use App\Models\Post;
use App\Models\Thread;
use App\Services\LocalAI\SpamShieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ThreadController extends Controller
{
    public function __construct(private readonly SpamShieldService $spamShield)
    {
    }

    /**
     * عرض نموذج إنشاء موضوع جديد (بالمحرر واختيار القسم)
     */
    public function create(Request $request)
    {
        $usergroupId = (int) auth()->user()->usergroupid;

        // الأقسام التي يملك المستخدم ترخيص الإنشاء فيها (حسب مجموعته)
        $allowedForums = Forum::active()
            ->ordered()
            ->get()
            ->filter(fn(Forum $forum) => ForumPermission::canPostNew($forum->forumid, $usergroupId))
            ->values();

        if ($allowedForums->isEmpty()) {
            return response()->view('errors.forbidden', [
                'title' => 'لا يمكنك إنشاء مواضيع',
                'message' => 'لا تملك صلاحية إنشاء مواضيع في أي قسم حالياً. يرجى التواصل مع الإدارة.',
            ]);
        }

        $selectedForumId = (int) $request->query('forum', $allowedForums->first()->forumid);

        return view('thread.create', compact('allowedForums', 'selectedForumId'));
    }

    /**
     * حفظ موضوع جديد مع أول مشاركة
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $usergroupId = (int) $user->usergroupid;

        $validated = $request->validate([
            'forumid' => ['required', 'integer'],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'content' => ['required', 'string'],
        ], [
            'forumid.required' => 'اختر القسم.',
            'title.required' => 'عنوان الموضوع مطلوب.',
            'title.min' => 'عنوان الموضوع قصير جداً — 5 أحرف على الأقل.',
            'title.max' => 'عنوان الموضوع طويل جداً — 150 حرفاً كحد أقصى.',
            'content.required' => 'محتوى الموضوع مطلوب.',
        ]);

        $forum = Forum::active()->find($request->integer('forumid'));
        if (!$forum || !ForumPermission::canPostNew($forum->forumid, $usergroupId)) {
            return back()->withErrors(['forumid' => 'القسم غير موجود أو لا تملك صلاحية الإنشاء فيه.'])
                ->withInput($request->only('title', 'forumid'));
        }

        $title = trim($validated['title']);
        $content = HtmlSanitizer::clean($validated['content']);

        // فحص الحدود (نفس منطق الرد السريع)
        $minChars = (int) config('security.firewall.quick_reply_min_chars', 10);
        $maxChars = (int) config('security.firewall.quick_reply_max_chars', 10000);
        $length = $this->plainTextLength($content);

        if ($length === 0) {
            return back()->withErrors(['content' => 'محتوى الموضوع مطلوب.'])
                ->withInput($request->only('title', 'forumid'));
        }
        if ($length < $minChars) {
            return back()->withErrors(['content' => 'المحتوى قصير جداً — الحد الأدنى ' . $minChars . ' أحرف.'])
                ->withInput($request->only('title', 'forumid'));
        }
        if ($length > $maxChars) {
            return back()->withErrors(['content' => 'حجم المحتوى يتجاوز الحد المسموح به (' . $maxChars . ' أحرف).'])
                ->withInput($request->only('title', 'forumid'));
        }

        // فحص تكرار العنوان في نفس القسم
        $duplicate = Thread::where('forumid', $forum->forumid)
            ->where('title', $title)
            ->visible()
            ->latest('threadid')
            ->select('threadid', 'title')
            ->first();

        if ($duplicate) {
            return back()->withErrors([
                'title' => 'يوجد موضوع بنفس العنوان في هذا القسم: "' . $duplicate->title . '". اختر عنواناً مختلفاً.',
            ])->withInput($request->only('title', 'forumid'));
        }

        $spamScore = $this->spamShield->calculateSpamScore($title, $content);
        $visible = $spamScore > 80 ? 0 : 1;

        $thread = DB::transaction(function () use ($forum, $user, $title, $content, $visible) {
            $now = time();

            $thread = Thread::create([
                'title' => $title,
                'forumid' => $forum->forumid,
                'postusername' => $user->username,
                'postuserid' => $user->userid,
                'dateline' => $now,
                'views' => 0,
                'replycount' => 0,
                'open' => 1,
                'visible' => $visible,
                'lastpost' => $now,
                'lastposter' => $user->username,
                'sticky' => 0,
            ]);

            $post = Post::create([
                'threadid' => $thread->threadid,
                'userid' => $user->userid,
                'username' => $user->username,
                'pagetext' => '<!-- HTML -->' . $content,
                'dateline' => $now,
                'visible' => $visible,
                'title' => $title,
            ]);

            $thread->firstpostid = $post->postid;
            $thread->save();

            if ($visible) {
                Forum::whereKey($forum->forumid)->increment('threadcount');
                $user->increment('posts');
            }

            return $thread;
        });

        if ($visible) {
            return redirect()->route('thread.show', ['id' => $thread->threadid, 'slug' => $thread->slug])
                ->with('success', 'تم نشر الموضوع بنجاح!');
        }

        return redirect()->route('forum.show', ['id' => $forum->forumid, 'slug' => $forum->slug])
            ->with('success', 'تم استلام الموضوع وسيظهر بعد المراجعة.');
    }

    private function plainTextLength(string $content): int
    {
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return mb_strlen(trim($text), 'UTF-8');
    }

    /**
     * عرض الموضوع مع جميع الردود
     */
    public function show(\App\Services\ThreadSeoService $seoService, int $id, ?string $slug = null)
    {
        // Load SEO-related relations up front to avoid hidden lazy queries in ThreadSeoService.
        $thread = Thread::with(['forum', 'author', 'firstPost.attachments'])->visible()->findOrFail($id);

        // التحقق من صلاحية الوصول لقسم الموضوع
        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;
        if ($thread->forumid && !ForumPermission::canView($thread->forumid, $usergroupId)) {
            $forumTitle = $thread->forum->title ?? 'هذا القسم';
            return response()->view('errors.forbidden', [
                'title' => 'هذا الموضوع في قسم مقيد',
                'message' => 'ليس لديك صلاحية لقراءة المواضيع في قسم "' . $forumTitle . '". يرجى تسجيل الدخول أو التواصل مع الإدارة.',
            ], 403);
        }

        // إعادة التوجيه للرابط الصحيح
        $correctSlug = $thread->slug;
        if (empty($slug) || $slug !== $correctSlug) {
            $redirectParams = ['id' => $thread->threadid];
            // Only add slug if it's not empty, otherwise routing might fail if the route demands it (wait, web.php has {slug?} so it's fine)
            if (!empty($correctSlug)) {
                $redirectParams['slug'] = $correctSlug;
            }

            $redirectUrl = route('thread.show', $redirectParams);

            $qs = request()->getQueryString();
            if (!empty($qs)) {
                $redirectUrl .= '?' . $qs;
            }

            return redirect($redirectUrl, 301);
        }

        // زيادة عدد المشاهدات — مُجمَّعة في الكاش (بدلاً من كتابة DB مباشرة)
        $cacheKey = "views_thread_{$id}";
        $current = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, 600); // 10 دقائق

        // الردود مع ترقيم
        $posts = $thread->posts()
            ->visible()
            ->chronological()
            ->with(['author', 'attachments'])
            // Avoid COUNT(*) on the large post table; next/previous links are enough here.
            ->simplePaginate(15);

        // الموضوع التالي والسابق في نفس القسم
        $nextThread = Thread::where('forumid', $thread->forumid)
            ->where('threadid', '>', $thread->threadid)
            ->visible()
            ->orderBy('threadid', 'asc')
            ->select('threadid', 'title')
            ->first();

        $prevThread = Thread::where('forumid', $thread->forumid)
            ->where('threadid', '<', $thread->threadid)
            ->visible()
            ->orderBy('threadid', 'desc')
            ->select('threadid', 'title')
            ->first();

        // توليد الـ SEO Object باستخدام الـ Service Layer
        $seoData = $seoService->generate($thread);
        $canReply = $this->canReply($thread);

        return view('thread.show', compact('thread', 'posts', 'nextThread', 'prevThread', 'seoData', 'canReply'));
    }

    private function canReply(Thread $thread): bool
    {
        $user = auth()->user();
        if (!$user || !$thread->forumid) {
            return false;
        }

        $usergroupId = (int) $user->usergroupid;

        return ($thread->open || $user->is_admin || $user->is_moderator)
            && ForumPermission::canReply($thread->forumid, $usergroupId);
    }

    /**
     * إرجاع جزء الردود (مستعمًى لصفحة معينة) كـ HTML.
     * يُستخدم بعد إضافة رد عبر AJAX عندما يقع الرد في صفحة أحدث،
     * ليأخذ المتصفح الردود الكاملة لتلك الصفحة بدون إعادة تحميل الصفحة.
     */
    public function postsFragment(Request $request, int $id): JsonResponse
    {
        $thread = Thread::with(['forum', 'author', 'firstPost.attachments'])->visible()->findOrFail($id);

        $usergroupId = auth()->check() ? (int) auth()->user()->usergroupid : 1;
        if ($thread->forumid && !ForumPermission::canView($thread->forumid, $usergroupId)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $page = $request->integer('page', 1);

        $posts = $thread->posts()
            ->visible()
            ->chronological()
            ->with(['author', 'attachments'])
            ->simplePaginate(15, ['*'], 'page', max(1, $page));

        $startNumber = ($posts->currentPage() - 1) * $posts->perPage() + 1;

        $html = '';
        foreach ($posts as $index => $post) {
            $html .= view('thread.partials.post', [
                'post' => $post,
                'thread' => $thread,
                'postNumber' => $startNumber + $index,
                'isFirst' => $posts->currentPage() == 1 && $index === 0,
            ])->render();
        }

        return response()->json([
            'success' => true,
            'html' => $html,
            'page' => $posts->currentPage(),
            'has_more' => $posts->hasMorePages(),
        ]);
    }

}

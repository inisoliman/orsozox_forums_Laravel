<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\Post;

class SearchController extends Controller
{
    /**
     * عرض صفحة البحث
     */
    public function index()
    {
        return view('search', ['results' => null, 'query' => '']);
    }

    /**
     * تنفيذ البحث
     */
    public function results(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:threads,posts',
        ]);

        $query = $request->input('q');
        $type = $request->input('type', 'threads');
        $results = null;

        try {
            if ($type === 'posts') {
                $results = Post::where('pagetext', 'LIKE', '%' . $query . '%')
                    ->visible()
                    ->with(['thread', 'author'])
                    ->orderBy('dateline', 'desc')
                    ->paginate(20)
                    ->appends(['q' => $query, 'type' => $type]);
            } else {
                $results = Thread::where('title', 'LIKE', '%' . $query . '%')
                    ->visible()
                    ->orderBy('dateline', 'desc')
                    ->with(['forum', 'author'])
                    ->paginate(20)
                    ->appends(['q' => $query, 'type' => $type]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Search Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء البحث: ' . $e->getMessage());
        }

        return view('search', compact('results', 'query', 'type'));
    }
}

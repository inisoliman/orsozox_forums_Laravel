<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RedirectorController extends Controller
{
    /**
     * Handle legacy redirector.php requests
     */
    public function index(Request $request)
    {
        $url = $request->input('url');

        if (!$url) {
            return redirect()->route('home');
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return redirect()->route('home')->with('error', 'رابط غير صالح');
        }

        // Decode URL entities if mixed
        $url = html_entity_decode($url);

        // Simple direct redirect (mimics nodelay behavior)
        return redirect()->away($url);
    }
}

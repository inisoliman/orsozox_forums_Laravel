<?php

namespace App\Http\Controllers;

use App\Services\OnlineUsersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnlineUsersController extends Controller
{
    protected $service;

    public function __construct(OnlineUsersService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        // Double check admin permission
        if (!Auth::check() || !Auth::user()->is_admin) {
            abort(403);
        }

        $data = $this->service->getOnlineUsers();

        return view('online.index', [
            'total' => $data['total'],
            'members' => $data['members'],
            'guests' => $data['guests'],
            'bots' => $data['bots'],
        ]);
    }
}

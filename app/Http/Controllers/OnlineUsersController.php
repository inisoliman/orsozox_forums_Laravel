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
            'total_members' => $data['total_members'],
            'total_guests' => $data['total_guests'],
            'total_bots' => $data['total_bots'],
            'users' => $data['users'],
            'paginator' => $data['paginator'],
        ]);
    }
}

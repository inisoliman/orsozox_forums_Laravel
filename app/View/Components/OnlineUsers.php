<?php

namespace App\View\Components;

use App\Services\OnlineUsersService;
use Illuminate\View\Component;

class OnlineUsers extends Component
{
    public $total;
    public $total_members;
    public $total_guests;
    public $total_bots;
    public $members;
    public $bots;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OnlineUsersService $service)
    {
        $data = $service->getOnlineUsers();

        $this->total = $data['total'];
        $this->total_members = $data['total_members'];
        $this->total_guests = $data['total_guests'];
        $this->total_bots = $data['total_bots'];

        $this->members = [];
        $this->bots = [];

        foreach ($data['users'] as $user) {
            if ($user['type'] === 'member') {
                $this->members[] = $user;
            } elseif ($user['type'] === 'bot') {
                $this->bots[] = $user;
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.online-users');
    }
}

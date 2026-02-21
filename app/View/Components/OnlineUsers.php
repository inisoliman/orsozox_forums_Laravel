<?php

namespace App\View\Components;

use App\Services\OnlineUsersService;
use Illuminate\View\Component;

class OnlineUsers extends Component
{
    public $total;
    public $members;
    public $guests;
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
        $this->members = $data['members'];
        $this->guests = $data['guests'];
        $this->bots = $data['bots'];
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

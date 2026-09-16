<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Bell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    #[On('notification-received')]
    public function refresh(): void {}

    public function markAsRead(string $notificationId): void
    {
        Auth::user()?->notifications()->where('id', $notificationId)->first()?->markAsRead();
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.notifications.bell', [
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
            'items' => $user?->notifications()->latest()->limit(8)->get() ?? collect(),
        ]);
    }
}

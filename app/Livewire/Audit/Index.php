<?php

declare(strict_types=1);

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $action = '';

    public function render()
    {
        $logs = AuditLog::query()
            ->with('utilisateur')
            ->when($this->action, fn ($q) => $q->where('action', $this->action))
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('livewire.audit.index', ['logs' => $logs]);
    }
}

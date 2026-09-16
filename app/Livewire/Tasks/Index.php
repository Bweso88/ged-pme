<?php

declare(strict_types=1);

namespace App\Livewire\Tasks;

use App\Models\WorkflowInstance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function render()
    {
        $user = Auth::user();

        $instances = WorkflowInstance::query()
            ->where('statut', WorkflowInstance::STATUT_EN_COURS)
            ->whereNotNull('etape_courante_id')
            ->with(['document', 'etapeCourante'])
            ->get()
            ->filter(fn (WorkflowInstance $instance) => $instance->etapeCourante?->canBeActedOnBy($user));

        return view('livewire.tasks.index', ['instances' => $instances]);
    }
}

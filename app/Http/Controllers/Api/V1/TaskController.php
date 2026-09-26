<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WorkflowInstanceResource;
use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /** Tâches de validation en attente pour l'utilisateur courant (voir App\Livewire\Tasks\Index). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = Auth::user();

        $instances = WorkflowInstance::query()
            ->where('statut', WorkflowInstance::STATUT_EN_COURS)
            ->whereNotNull('etape_courante_id')
            ->with(['document', 'etapeCourante'])
            ->get()
            ->filter(fn (WorkflowInstance $instance) => $instance->etapeCourante?->canBeActedOnBy($user))
            ->values();

        return WorkflowInstanceResource::collection($instances);
    }
}

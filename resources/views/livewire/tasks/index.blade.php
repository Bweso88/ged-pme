<div class="space-y-3">
    @forelse ($instances as $instance)
        <a href="{{ route('documents.show', $instance->document) }}" wire:navigate
           class="block rounded-lg border border-slate-200 bg-white p-4 hover:shadow-sm">
            <p class="font-medium text-slate-900">{{ $instance->document->nom }}</p>
            <p class="text-sm text-slate-500">Étape : {{ $instance->etapeCourante->nom }}</p>
        </a>
    @empty
        <p class="text-sm text-slate-400">Aucune tâche de validation en attente.</p>
    @endforelse
</div>

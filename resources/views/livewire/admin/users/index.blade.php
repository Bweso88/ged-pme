<div>
    <button wire:click="$set('showForm', true)" class="mb-4 rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">+ Nouvel utilisateur</button>

    @if ($showForm)
        <form wire:submit="createUser" class="mb-6 grid grid-cols-4 gap-3 rounded-lg border border-slate-200 bg-white p-4">
            <input wire:model="name" type="text" placeholder="Nom" class="rounded-md border-slate-300 text-sm">
            <input wire:model="email" type="email" placeholder="E-mail" class="rounded-md border-slate-300 text-sm">
            <select wire:model="roleId" class="rounded-md border-slate-300 text-sm">
                <option value="">Rôle…</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->nom }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Créer</button>
        </form>
        @error('email') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
        @error('roleId') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
    @endif

    <table class="w-full text-sm bg-white rounded-lg ring-1 ring-slate-200 overflow-hidden">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-2">Nom</th>
                <th class="px-4 py-2">E-mail</th>
                <th class="px-4 py-2">Rôles</th>
                <th class="px-4 py-2">Statut</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-2 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $user->email }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $user->roles->pluck('nom')->implode(', ') }}</td>
                    <td class="px-4 py-2">{{ $user->statut }}</td>
                    <td class="px-4 py-2 text-right">
                        <button wire:click="toggleSuspend('{{ $user->id }}')" class="text-slate-500 hover:underline">
                            {{ $user->statut === 'actif' ? 'Suspendre' : 'Réactiver' }}
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

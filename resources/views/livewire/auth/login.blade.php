<div class="w-full max-w-sm">
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-semibold text-slate-900">{{ config('app.name') }}</h1>
        <p class="mt-1 text-sm text-slate-500">Gestion électronique des documents & archivage</p>
    </div>

    <form wire:submit="submit" class="bg-white shadow-sm ring-1 ring-slate-200 rounded-lg p-6 space-y-4">
        @if ($errors->any())
            <div class="rounded-md bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-slate-700" for="email">Adresse e-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required
                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700" for="password">Mot de passe</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input wire:model="remember" type="checkbox" class="rounded border-slate-300">
            Se souvenir de moi
        </label>

        <button type="submit"
                class="w-full inline-flex justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Se connecter
        </button>
    </form>
</div>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-slate-900 text-slate-200 flex flex-col shrink-0">
            <div class="px-5 py-5 text-lg font-semibold text-white border-b border-slate-800">
                {{ auth()->user()?->company?->nom ?? config('app.name') }}
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Tableau de bord</x-nav-link>
                <x-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.*')">Espace documentaire</x-nav-link>
                <x-nav-link :href="route('search')" :active="request()->routeIs('search')">Recherche avancée</x-nav-link>
                <x-nav-link :href="route('tasks.index')" :active="request()->routeIs('tasks.*')">Mes tâches</x-nav-link>
                <x-nav-link :href="route('archives.index')" :active="request()->routeIs('archives.*')">Archives</x-nav-link>
                <x-nav-link :href="route('trash.index')" :active="request()->routeIs('trash.*')">Corbeille</x-nav-link>
                @if (auth()->user()?->hasPermission('admin.audit'))
                    <x-nav-link :href="route('audit.index')" :active="request()->routeIs('audit.*')">Journal d'audit</x-nav-link>
                @endif
                @if (auth()->user()?->hasPermission('admin.users') || auth()->user()?->hasPermission('admin.settings'))
                    <div class="pt-4 mt-4 border-t border-slate-800 text-xs uppercase tracking-wide text-slate-500 px-3">Administration</div>
                    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Utilisateurs & rôles</x-nav-link>
                    <x-nav-link :href="route('admin.document-types.index')" :active="request()->routeIs('admin.document-types.*')">Types de documents</x-nav-link>
                    <x-nav-link :href="route('admin.workflows.index')" :active="request()->routeIs('admin.workflows.*')">Workflows</x-nav-link>
                    <x-nav-link :href="route('admin.retention.index')" :active="request()->routeIs('admin.retention.*')">Politiques d'archivage</x-nav-link>
                @endif
            </nav>
            <div class="px-4 py-4 border-t border-slate-800 text-xs text-slate-400">
                <p class="truncate">{{ auth()->user()?->name }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-white">Se déconnecter</button>
                </form>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <div class="px-6 py-4 border-b border-slate-200 bg-white flex items-center justify-between">
                <h1 class="text-lg font-semibold">{{ $header ?? '' }}</h1>
                <livewire:notifications.bell />
            </div>
            <div class="p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-3 py-2">
                        {{ session('status') }}
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
    @livewireScripts
</body>
</html>

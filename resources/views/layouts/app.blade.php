<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    @php
        $navItems = [
            ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'home'],
            ['route' => 'documents.index', 'pattern' => 'documents.*', 'label' => 'Espace documentaire', 'icon' => 'folder'],
            ['route' => 'search', 'pattern' => 'search', 'label' => 'Recherche avancée', 'icon' => 'search'],
            ['route' => 'tasks.index', 'pattern' => 'tasks.*', 'label' => 'Mes tâches', 'icon' => 'check'],
            ['route' => 'archives.index', 'pattern' => 'archives.*', 'label' => 'Archives', 'icon' => 'archive'],
            ['route' => 'trash.index', 'pattern' => 'trash.*', 'label' => 'Corbeille', 'icon' => 'trash'],
        ];
        $pageTitle = collect($navItems)->first(fn ($item) => request()->routeIs($item['pattern']))['label'] ?? config('app.name');
    @endphp
    <div class="min-h-screen flex">
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0">
            <div class="px-5 py-5 flex items-center gap-3 border-b border-slate-100">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white font-bold">Y</span>
                <span class="text-base font-semibold text-slate-900 truncate">{{ auth()->user()?->company?->nom ?? config('app.name') }}</span>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @foreach ($navItems as $item)
                    <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                        <x-slot name="icon">
                            <x-icon :name="$item['icon']" />
                        </x-slot>
                        {{ $item['label'] }}
                    </x-nav-link>
                @endforeach

                @if (auth()->user()?->hasPermission('admin.audit'))
                    <x-nav-link :href="route('audit.index')" :active="request()->routeIs('audit.*')">
                        <x-slot name="icon"><x-icon name="clipboard" /></x-slot>
                        Journal d'audit
                    </x-nav-link>
                @endif

                @if (auth()->user()?->hasPermission('admin.users') || auth()->user()?->hasPermission('admin.settings'))
                    <div class="pt-4 mt-4 border-t border-slate-100 text-xs font-semibold uppercase tracking-wide text-slate-400 px-3">Administration</div>
                    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        <x-slot name="icon"><x-icon name="users" /></x-slot>
                        Utilisateurs & rôles
                    </x-nav-link>
                    <x-nav-link :href="route('admin.document-types.index')" :active="request()->routeIs('admin.document-types.*')">
                        <x-slot name="icon"><x-icon name="tag" /></x-slot>
                        Types de documents
                    </x-nav-link>
                    <x-nav-link :href="route('admin.workflows.index')" :active="request()->routeIs('admin.workflows.*')">
                        <x-slot name="icon"><x-icon name="workflow" /></x-slot>
                        Workflows
                    </x-nav-link>
                    <x-nav-link :href="route('admin.retention.index')" :active="request()->routeIs('admin.retention.*')">
                        <x-slot name="icon"><x-icon name="shield" /></x-slot>
                        Politiques d'archivage
                    </x-nav-link>
                @endif
            </nav>
            <div class="px-4 py-4 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-sm font-semibold">
                        {{ Illuminate\Support\Str::of(auth()->user()?->name ?? '?')->substr(0, 1)->upper() }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ auth()->user()?->name }}</p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs text-slate-400 hover:text-brand-600">Se déconnecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 min-w-0 flex flex-col">
            <div class="px-6 py-4 bg-white border-b border-slate-200 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-slate-400">
                    <x-icon name="grid" class="h-4 w-4" />
                    <h1 class="text-lg font-semibold text-slate-900">{{ $header ?? $pageTitle }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <label class="hidden md:flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-400 focus-within:ring-1 focus-within:ring-brand-400">
                        <x-icon name="search" class="h-4 w-4" />
                        <input type="search" placeholder="Rechercher…" class="bg-transparent border-0 focus:ring-0 p-0 text-sm placeholder:text-slate-400" disabled>
                    </label>
                    <livewire:notifications.bell />
                </div>
            </div>
            <div class="p-6 flex-1">
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-3 py-2">
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

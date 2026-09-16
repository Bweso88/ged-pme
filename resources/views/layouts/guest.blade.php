<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>

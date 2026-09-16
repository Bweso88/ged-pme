<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Document partagé</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-sm ring-1 ring-slate-200 rounded-lg p-6 w-full max-w-sm">
        <h1 class="text-lg font-semibold mb-2">Document partagé</h1>

        @if ($errors->has('password'))
            <p class="text-sm text-red-600 mb-3">{{ $errors->first('password') }}</p>
        @elseif ($errors->any())
            <p class="text-sm text-red-600 mb-3">{{ $errors->first() }}</p>
        @endif

        <form method="GET" action="{{ route('share-link.show', $token) }}" class="space-y-3">
            <label class="block text-sm text-slate-600">Mot de passe</label>
            <input type="password" name="password" class="w-full rounded-md border-slate-300 text-sm" autofocus>
            <button type="submit" class="w-full rounded-md bg-slate-900 px-3 py-2 text-sm text-white">Accéder au document</button>
        </form>
    </div>
</body>
</html>

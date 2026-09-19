<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->nom }} — Édition en ligne</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 h-screen flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-4 py-2 bg-white border-b border-slate-200 shrink-0">
        <span class="text-sm font-medium text-slate-800">{{ $document->nom }}</span>
        <a href="{{ route('documents.show', $document) }}" class="text-sm text-slate-600 hover:underline">← Retour au document</a>
    </header>

    <div id="onlyoffice-editor" class="flex-1"></div>

    <script src="{{ $documentServerUrl }}/web-apps/apps/api/documents/api.js"></script>
    <script>
        new DocsAPI.DocEditor("onlyoffice-editor", @json($editorConfig));
    </script>
</body>
</html>

<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Documents\DownloadController;
use App\Http\Controllers\Sharing\ShareLinkController;
use App\Http\Controllers\WebDav\WebDavController;
use App\Livewire\Admin\DocumentTypes\Index as AdminDocumentTypes;
use App\Livewire\Admin\Retention\Index as AdminRetention;
use App\Livewire\Admin\Users\Index as AdminUsers;
use App\Livewire\Admin\Workflows\Index as AdminWorkflows;
use App\Livewire\Archives\Index as ArchivesIndex;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Documents\Explorer;
use App\Livewire\Documents\Show as DocumentShow;
use App\Livewire\Search\Index as SearchIndex;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Livewire\Trash\Index as TrashIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::get('/partage/{token}', ShareLinkController::class)->name('share-link.show');

// Hors du groupe "auth" Laravel : l'authentification WebDAV (Basic Auth) est gérée par
// Sabre\DAV lui-même (voir App\Domain\WebDav\AuthBackend), un client réseau (Windows/Mac) ne
// portant pas de cookie de session.
Route::match(
    ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'PROPFIND', 'PROPPATCH', 'MKCOL', 'COPY', 'MOVE', 'LOCK', 'UNLOCK', 'OPTIONS'],
    '/webdav/{path?}',
    WebDavController::class,
)->where('path', '.*')->name('webdav');

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');

    Route::get('/documents', Explorer::class)->name('documents.index');
    Route::get('/documents/dossier/{folder}', Explorer::class)->name('documents.show-folder');
    Route::get('/documents/d/{document}', DocumentShow::class)->name('documents.show');
    Route::get('/documents/d/{document}/telecharger', DownloadController::class)->name('documents.download');

    Route::get('/recherche', SearchIndex::class)->name('search');
    Route::get('/mes-taches', TasksIndex::class)->name('tasks.index');
    Route::get('/archives', ArchivesIndex::class)->name('archives.index');
    Route::get('/corbeille', TrashIndex::class)->name('trash.index');
    Route::get('/audit', AuditIndex::class)->name('audit.index');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/utilisateurs', AdminUsers::class)->name('users.index');
        Route::get('/types-documents', AdminDocumentTypes::class)->name('document-types.index');
        Route::get('/workflows', AdminWorkflows::class)->name('workflows.index');
        Route::get('/retention', AdminRetention::class)->name('retention.index');
    });
});

<?php

declare(strict_types=1);

// API REST v1 — voir ged-pme/docs/02-architecture-technique.md, §9.
// Les routes sont ajoutées au fil des modules (documents, workflow, partage, audit...).

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentTypeController;
use App\Http\Controllers\Api\V1\DocumentVersionController;
use App\Http\Controllers\Api\V1\FolderController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\WorkflowController;
use App\Http\Controllers\Documents\DownloadController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

    Route::get('/folders', [FolderController::class, 'index'])->name('api.v1.folders.index');
    Route::get('/folders/{folder}', [FolderController::class, 'show'])->name('api.v1.folders.show');

    Route::get('/documents', [DocumentController::class, 'index'])->name('api.v1.documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('api.v1.documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('api.v1.documents.show');
    Route::get('/documents/{document}/download', DownloadController::class)->name('api.v1.documents.download');
    Route::post('/documents/{document}/versions', [DocumentVersionController::class, 'store'])->name('api.v1.documents.versions.store');

    Route::post('/documents/{document}/workflow/submit', [WorkflowController::class, 'submit'])->name('api.v1.workflow.submit');
    Route::post('/documents/{document}/workflow/approve', [WorkflowController::class, 'approve'])->name('api.v1.workflow.approve');
    Route::post('/documents/{document}/workflow/reject', [WorkflowController::class, 'reject'])->name('api.v1.workflow.reject');
    Route::post('/documents/{document}/workflow/request-changes', [WorkflowController::class, 'requestChanges'])->name('api.v1.workflow.request-changes');

    Route::get('/document-types', [DocumentTypeController::class, 'index'])->name('api.v1.document-types.index');
    Route::get('/tasks', [TaskController::class, 'index'])->name('api.v1.tasks.index');
});

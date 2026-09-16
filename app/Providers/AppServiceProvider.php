<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Ocr\Engines\NullOcrEngine;
use App\Domain\Ocr\Engines\TesseractOcrEngine;
use App\Domain\Ocr\OcrEngine;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);

        $this->app->bind(OcrEngine::class, function () {
            return match (config('ged.ocr.engine')) {
                'tesseract' => new TesseractOcrEngine((string) config('ged.ocr.tesseract_binary')),
                default => new NullOcrEngine(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Les codes de permission (ex. "admin.settings", "folder.manage") sont vérifiés
        // directement via User::hasPermission, sans Policy dédiée : voir doc 04, §12.
        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (! str_contains($ability, '.')) {
                return null;
            }

            return $user->hasPermission($ability, $arguments[0] ?? null);
        });
    }
}

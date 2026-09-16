<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Les routes API v1 sont enregistrées dans routes/api_v1.php (voir doc 02, §9).
Route::prefix('v1')->group(base_path('routes/api_v1.php'));

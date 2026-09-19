<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sans effet tant que GED_EMAIL_CAPTURE_ENABLED=false (voir config/ged.php) : la commande
// s'arrête immédiatement dans ce cas, donc rien à ajuster ici une fois la capture activée.
Schedule::command('ged:capture-emails')->everyFiveMinutes();

<?php

use App\Services\MediaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $ttl = (int) config('education.media.pending_ttl_hours', 24);
    app(MediaService::class)->deleteOrphans($ttl);
})->hourly()->name('media:cleanup-orphans')->withoutOverlapping();

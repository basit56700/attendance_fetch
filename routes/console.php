<?php

use App\Models\SystemSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('zk:poll')
    ->everyMinute()
    ->when(function (): bool {
        if (! Schema::hasTable('system_settings')) {
            return false;
        }
        if (SystemSetting::get('auto_fetch_enabled', '0') !== '1') {
            return false;
        }

        $intervalMinutes = max(1, (int) SystemSetting::get('auto_fetch_interval', '15'));
        $lastRun = Cache::get('zk_poll_last_run');
        if ($lastRun && now()->diffInMinutes($lastRun) < $intervalMinutes) {
            return false;
        }

        Cache::put('zk_poll_last_run', now(), now()->addDay());

        return true;
    })
    ->withoutOverlapping();

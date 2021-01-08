<?php

namespace Coremetrics\CoremetricsLaravel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot()
    {
//        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
//            $schedule->command('cm:metrics:report')->everyMinute();
//        });

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('cm:metrics:report')->everyMinute();
        });
    }

    public function register()
    {
    }
}

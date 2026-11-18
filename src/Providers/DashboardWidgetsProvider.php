<?php

namespace ShuraBlack\DashboardWidgets\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class DashboardWidgetsProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Pelican runs its egg update check daily but keeps the result only 1 hour,
        // so the egg widget would show "up to date" most of the day. Re-run it every 30 minutes.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            if (config('dashboard-widgets.widgets.egg_updates.enabled', true)) {
                $schedule->command('p:egg:check-updates')->everyThirtyMinutes()->withoutOverlapping();
            }
        });
    }
}

<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// Laravel's failed queue jobs (Pelican keeps them in the database, the framework default).
class FailedJobsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        // Exception messages can contain paths and internals, so only for admins who may see the health page.
        return DashboardWidgetsPlugin::isEnabled('failed_jobs') && (user()?->can('view health') ?? false);
    }

    protected function getViewData(): array
    {
        $table = fn () => DB::connection(config('queue.failed.database'))->table(config('queue.failed.table', 'failed_jobs'));
        $jobs = $table()->latest('failed_at')->limit(10)->get(['payload', 'exception', 'failed_at']);

        return [
            'heading' => 'Failed jobs',
            'icon' => 'tabler-alert-octagon',
            'description' => $table()->count() . ' failed · retry with "php artisan queue:retry all", clear with "php artisan queue:flush"',
            'items' => $jobs->map(fn (object $job) => [
                'name' => class_basename(json_decode($job->payload, true)['displayName'] ?? 'Unknown job'),
                'detail' => Str::limit(strtok((string) $job->exception, "\n"), 120) . ' · ' . Carbon::parse($job->failed_at)->diffForHumans(),
            ]),
            'empty' => 'No failed jobs.',
            'url' => null,
            'linkLabel' => null,
        ];
    }
}

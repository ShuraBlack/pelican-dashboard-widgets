<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Schedule;
use App\Models\Server;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// Long-running schedules first (possibly stuck), then the next ones due.
class SchedulesWidget extends Widget
{
    private const LONG_RUNNING_MINUTES = 15;

    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('schedules') && (user()?->can('viewAny', Server::class) ?? false);
    }

    protected function getViewData(): array
    {
        $nodeIds = user()->accessibleNodes()->pluck('id');
        $schedules = fn () => Schedule::query()
            ->with('server:id,name')
            ->whereHas('server', fn (Builder $query) => $query->whereIn('node_id', $nodeIds));

        // Pelican flips is_processing on when a run starts, so updated_at marks the start of the current run.
        $longRunning = $schedules()
            ->where('is_processing', true)
            ->where('updated_at', '<', now()->subMinutes(self::LONG_RUNNING_MINUTES))
            ->get()
            ->map(fn (Schedule $schedule) => [
                'name' => ($schedule->server?->name ?? 'Deleted server') . ' · ' . $schedule->name,
                'detail' => 'running since ' . $schedule->updated_at?->diffForHumans(),
                'badge' => ['Running long', 'warning'],
            ]);

        $upcoming = $schedules()
            ->where('is_active', true)
            ->where('is_processing', false)
            ->whereNotNull('next_run_at')
            ->orderBy('next_run_at')
            ->limit(10)
            ->get()
            ->map(fn (Schedule $schedule) => [
                'name' => ($schedule->server?->name ?? 'Deleted server') . ' · ' . $schedule->name,
                'detail' => $schedule->next_run_at->diffForHumans(),
            ]);

        return [
            'heading' => 'Schedules',
            'icon' => 'tabler-clock',
            'description' => 'Next runs; schedules running over ' . self::LONG_RUNNING_MINUTES . ' minutes are listed first',
            'items' => $longRunning->concat($upcoming),
            'empty' => 'No active schedules.',
            'url' => null,
            'linkLabel' => null,
        ];
    }
}

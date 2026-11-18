<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Backup;
use App\Models\Server;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

class BackupsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('backups') && (user()?->can('viewAny', Server::class) ?? false);
    }

    protected function getViewData(): array
    {
        $nodeIds = user()->accessibleNodes()->pluck('id');
        $backups = fn () => Backup::query()->whereHas('server', fn (Builder $query) => $query->whereIn('node_id', $nodeIds));

        $stored = $backups()->where('is_successful', true);
        $running = $backups()->whereNull('completed_at')->count();

        $failed = $backups()
            ->with('server:id,name')
            ->where('is_successful', false)
            ->whereNotNull('completed_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(50)
            ->get();

        return [
            'heading' => 'Backups',
            'icon' => 'tabler-file-zip',
            'description' => (clone $stored)->count() . ' backups · ' . convert_bytes_to_readable((int) $stored->sum('bytes')) . ' stored'
                . ($running ? " · $running running" : ''),
            'items' => $failed->map(fn (Backup $backup) => [
                'name' => $backup->server?->name ?? 'Deleted server',
                'detail' => $backup->name . ' · ' . $backup->created_at?->diffForHumans(),
                'badge' => ['Failed', 'danger'],
            ]),
            'empty' => 'No failed backups in the last 7 days.',
            'url' => null,
            'linkLabel' => null,
        ];
    }
}

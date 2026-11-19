<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Server;
use Filament\Widgets\Widget;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// Servers with any Pelican status (failed installs first, then suspended, installing, restoring a backup).
class ProblemServersWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('problem_servers') && (user()?->can('viewAny', Server::class) ?? false);
    }

    protected function getViewData(): array
    {
        $servers = Server::query()
            ->with('node:id,name')
            ->whereIn('node_id', user()->accessibleNodes()->pluck('id'))
            ->whereNotNull('status')
            // CASE instead of FIELD(): works on MySQL, PostgreSQL and SQLite.
            ->orderByRaw("CASE status WHEN 'install_failed' THEN 0 WHEN 'reinstall_failed' THEN 0 WHEN 'suspended' THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->limit(50)
            ->get();

        return [
            'heading' => 'Servers needing attention',
            'icon' => 'tabler-heart-x',
            'description' => 'Failed installs, suspended, installing or restoring a backup',
            'items' => $servers->map(fn (Server $server) => [
                'name' => $server->name,
                'detail' => ($server->node?->name ?? 'Unknown node') . ' · since ' . $server->updated_at?->diffForHumans(),
                'badge' => [$server->status->getLabel(), $server->status->getColor()],
                'url' => DashboardWidgetsPlugin::serverUrl($server),
            ]),
            'empty' => 'All servers are running normally.',
            'url' => null,
            'linkLabel' => null,
        ];
    }
}

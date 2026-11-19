<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Server;
use Filament\Widgets\Widget;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

class RecentServersWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('recent_servers') && (user()?->can('viewAny', Server::class) ?? false);
    }

    protected function getViewData(): array
    {
        $servers = Server::query()
            ->with(['user:id,username', 'egg:id,name', 'node:id,name'])
            ->whereIn('node_id', user()->accessibleNodes()->pluck('id'))
            ->latest()
            ->limit(5)
            ->get();

        return [
            'heading' => 'Newest servers',
            'icon' => 'tabler-server-bolt',
            'description' => 'The last 5 servers created',
            'items' => $servers->map(fn (Server $server) => [
                'name' => $server->name,
                'detail' => implode(' · ', array_filter([
                    $server->user?->username,
                    $server->egg?->name,
                    $server->node?->name,
                    $server->created_at?->diffForHumans(),
                ])),
                'url' => DashboardWidgetsPlugin::serverUrl($server),
            ]),
            'empty' => 'No servers yet.',
            'url' => DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Servers\ServerResource'),
            'linkLabel' => 'All servers',
        ];
    }
}

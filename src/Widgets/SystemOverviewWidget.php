<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Node;
use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;
use Spatie\Health\ResultStores\ResultStore;

class SystemOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'System';

    // Configurable in the plugin settings (default 30 s); node checks are cached 30 s by Pelican anyway.
    protected function getPollingInterval(): ?string
    {
        $seconds = DashboardWidgetsPlugin::refreshSeconds();

        return $seconds ? "{$seconds}s" : null;
    }

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('system');
    }

    protected function getStats(): array
    {
        $user = user();

        return array_values(array_filter([
            $this->panelStat(),
            $user?->can('viewAny', Server::class) ? $this->serverStat() : null,
            $user?->can('viewAny', Node::class) ? $this->nodeStat() : null,
        ]));
    }

    private function panelStat(): Stat
    {
        $stat = Stat::make('Panel', config('app.version'))->icon('tabler-layout-dashboard');

        if (!user()?->can('view health')) {
            return $stat;
        }

        $results = app(ResultStore::class)->latestResults();
        $failed = $results?->storedCheckResults->whereIn('status', ['failed', 'crashed'])->count();

        return $stat
            ->description(match (true) {
                $failed === null => 'Health checks have not run yet',
                $failed > 0 => "$failed health check(s) failing",
                default => 'All health checks passing',
            })
            ->descriptionIcon($failed ? 'tabler-alert-triangle' : 'tabler-heart-check')
            ->color($failed ? 'danger' : 'success')
            ->url(DashboardWidgetsPlugin::url('App\Filament\Admin\Pages\Health'));
    }

    private function serverStat(): Stat
    {
        // Same scope as Pelican's server list: only servers on nodes this admin may access.
        $query = Server::query()->whereIn('node_id', user()->accessibleNodes()->pluck('id'));
        $total = (clone $query)->count();
        $byStatus = $query->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $failed = ($byStatus['install_failed'] ?? 0) + ($byStatus['reinstall_failed'] ?? 0);
        $parts = array_filter([
            $failed ? "$failed failed" : null,
            ($byStatus['suspended'] ?? 0) ? "{$byStatus['suspended']} suspended" : null,
            ($byStatus['installing'] ?? 0) ? "{$byStatus['installing']} installing" : null,
        ]);

        return Stat::make('Servers', $total)
            ->icon('tabler-server')
            ->description($parts ? implode(' · ', $parts) : 'None failed, suspended or installing')
            ->color($failed ? 'danger' : (($byStatus['suspended'] ?? 0) ? 'warning' : 'success'))
            ->url(DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Servers\ServerResource'));
    }

    private function nodeStat(): Stat
    {
        $nodes = user()->accessibleNodes()->get();
        $maintenance = $nodes->filter(fn (Node $node) => $node->isUnderMaintenance());
        // Pelican's systemInformation() returns an "exception" key when Wings can't be reached.
        $offline = $nodes->diff($maintenance)->filter(fn (Node $node) => array_key_exists('exception', $node->systemInformation()));
        $online = $nodes->count() - $maintenance->count() - $offline->count();

        $parts = array_filter([
            $offline->isNotEmpty() ? 'Offline: ' . $offline->pluck('name')->take(3)->implode(', ') . ($offline->count() > 3 ? ' …' : '') : null,
            $maintenance->isNotEmpty() ? "{$maintenance->count()} in maintenance" : null,
        ]);

        return Stat::make('Nodes', "$online / {$nodes->count()} online")
            ->icon('tabler-server-2')
            ->description($parts ? implode(' · ', $parts) : 'All nodes reachable')
            ->color($offline->isNotEmpty() ? 'danger' : ($maintenance->isNotEmpty() ? 'warning' : 'success'))
            ->url(DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Nodes\NodeResource'));
    }
}

<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Node;
use App\Services\Helpers\SoftwareVersionService;
use Filament\Widgets\Widget;
use ShuraBlack\DashboardWidgets\Bars;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;
use ShuraBlack\DashboardWidgets\PanelHost;

// One row per node with live usage from Wings, via Pelican's cached statistics() (5 s) and systemInformation() (30 s).
// Optionally the panel's own host as the first row.
class NodeDetailsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::node-details';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('node_details') && (user()?->can('viewAny', Node::class) ?? false);
    }

    protected function getViewData(): array
    {
        $showPanelHost = config('dashboard-widgets.panel_host') && user()?->can('view health');

        return [
            // Panel host data describes the whole machine, so only admins who may see health checks get it.
            'panelHost' => $showPanelHost ? PanelHost::stats() : null,
            // Same scope as Pelican's node list: only nodes this admin may access.
            'nodes' => user()->accessibleNodes()->orderBy('name')->get()->map(fn (Node $node) => $this->details($node)),
            'poll' => DashboardWidgetsPlugin::refreshSeconds(),
        ];
    }

    /** @return array<string, mixed> */
    private function details(Node $node): array
    {
        $details = ['name' => $node->name, 'fqdn' => $node->fqdn];

        if ($node->isUnderMaintenance()) {
            return $details + ['status' => 'maintenance', 'badge' => 'warning'];
        }

        $info = $node->systemInformation();
        if (array_key_exists('exception', $info)) {
            return $details + ['status' => 'offline', 'badge' => 'danger', 'error' => $info['exception']];
        }

        $stats = $node->statistics();
        $bars = [
            // Wings reports CPU as a share of all cores (0-100).
            Bars::bar('CPU', $stats['cpu_percent'], ($info['cpu_count'] ?? '?') . ' cores'),
            Bars::usage('Memory', $stats['memory_used'], $stats['memory_total']),
        ];
        if ($stats['swap_total'] > 0) {
            $bars[] = Bars::usage('Swap', $stats['swap_used'], $stats['swap_total']);
        }
        $bars[] = Bars::usage('Disk', $stats['disk_used'], $stats['disk_total']);

        $version = $info['version'] ?? null;

        return $details + [
            'status' => 'online',
            'badge' => 'success',
            'bars' => $bars,
            'load' => sprintf('%.2f / %.2f / %.2f', $stats['load_average1'], $stats['load_average5'], $stats['load_average15']),
            'system' => trim(sprintf('Wings %s · %s %s', $version ?? '?', $info['os'] ?? '', $info['architecture'] ?? '')),
            'wingsOutdated' => $version !== null && !app(SoftwareVersionService::class)->isLatestWings($version),
        ];
    }
}

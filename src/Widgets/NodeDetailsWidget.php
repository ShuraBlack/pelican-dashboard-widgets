<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Node;
use Filament\Widgets\Widget;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// One card per node with live usage from Wings, via Pelican's cached statistics() (5 s) and systemInformation() (30 s).
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
        // Same scope as Pelican's node list: only nodes this admin may access.
        return ['nodes' => user()->accessibleNodes()->orderBy('name')->get()->map(fn (Node $node) => $this->details($node))];
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
            self::bar('CPU', $stats['cpu_percent'], ($info['cpu_count'] ?? '?') . ' cores'),
            self::usage('Memory', $stats['memory_used'], $stats['memory_total']),
        ];
        if ($stats['swap_total'] > 0) {
            $bars[] = self::usage('Swap', $stats['swap_used'], $stats['swap_total']);
        }
        $bars[] = self::usage('Disk', $stats['disk_used'], $stats['disk_total']);

        return $details + [
            'status' => 'online',
            'badge' => 'success',
            'bars' => $bars,
            'load' => sprintf('%.2f / %.2f / %.2f', $stats['load_average1'], $stats['load_average5'], $stats['load_average15']),
            'system' => trim(sprintf('Wings %s · %s %s', $info['version'] ?? '?', $info['os'] ?? '', $info['architecture'] ?? '')),
        ];
    }

    /** @return array{label: string, percent: float, detail: string, color: string} */
    private static function usage(string $label, int|float $used, int|float $total): array
    {
        $detail = convert_bytes_to_readable((int) $used) . ' / ' . convert_bytes_to_readable((int) $total);

        return self::bar($label, $total > 0 ? $used / $total * 100 : 0, $detail);
    }

    /** @return array{label: string, percent: float, detail: string, color: string} */
    private static function bar(string $label, int|float $percent, string $detail): array
    {
        $percent = round(min(100, max(0, $percent)), 1);

        return [
            'label' => $label,
            'percent' => $percent,
            'detail' => $detail,
            // 70 / 90 % are the warning/danger thresholds Pelican uses for its own server cards.
            'color' => $percent >= 90 ? '#ef4444' : ($percent >= 70 ? '#f59e0b' : '#10b981'),
        ];
    }
}

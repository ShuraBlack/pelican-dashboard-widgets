<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Node;
use Filament\Widgets\Widget;
use ShuraBlack\DashboardWidgets\Bars;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// How much of each node's memory/disk/CPU limit is already handed out to servers, the way Pelican's isViable() counts it.
class NodeCapacityWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::node-capacity';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('node_capacity') && (user()?->can('viewAny', Node::class) ?? false);
    }

    protected function getViewData(): array
    {
        $nodes = user()->accessibleNodes()->withAllocatedResources()->withCount('servers')->orderBy('name')->get();

        return ['nodes' => $nodes->map(fn (Node $node) => [
            'name' => $node->name,
            'servers' => $node->servers_count,
            'bars' => [
                self::capacity('Memory', (int) $node->allocated_memory, $node->memory, $node->memory_overallocate, fn ($mib) => convert_bytes_to_readable($mib * 1024 * 1024)),
                self::capacity('Disk', (int) $node->allocated_disk, $node->disk, $node->disk_overallocate, fn ($mib) => convert_bytes_to_readable($mib * 1024 * 1024)),
                self::capacity('CPU', (int) $node->allocated_cpu, $node->cpu, $node->cpu_overallocate, fn ($percent) => "$percent %"),
            ],
        ])];
    }

    /** @return array<string, mixed> */
    private static function capacity(string $label, int $allocated, int $limit, int $overallocate, \Closure $format): array
    {
        // Pelican: a limit of 0 or a negative overallocation means "no limit".
        if ($limit <= 0 || $overallocate < 0) {
            return Bars::bar($label, null, $format($allocated) . ' allocated · no limit');
        }

        $max = $limit * (1 + $overallocate / 100);
        $detail = $format($allocated) . ' / ' . $format((int) $max) . ($overallocate > 0 ? " (incl. $overallocate % over)" : '');

        return Bars::bar($label, $allocated / $max * 100, $detail);
    }
}

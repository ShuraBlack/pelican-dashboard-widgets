<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Node;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// Free allocations (ports without a server) per node.
class AllocationsWidget extends Widget
{
    private const LOW = 5;

    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('allocations') && (user()?->can('viewAny', Node::class) ?? false);
    }

    protected function getViewData(): array
    {
        $nodes = user()->accessibleNodes()
            ->withCount(['allocations', 'allocations as free_allocations_count' => fn (Builder $query) => $query->whereNull('server_id')])
            ->orderBy('name')
            ->get();

        return [
            'heading' => 'Free allocations',
            'icon' => 'tabler-plug-connected',
            'description' => $nodes->sum('free_allocations_count') . ' of ' . $nodes->sum('allocations_count') . ' ports free · ' . self::LOW . ' or fewer is marked low',
            'items' => $nodes->map(fn (Node $node) => [
                'name' => $node->name,
                'detail' => "{$node->free_allocations_count} of {$node->allocations_count} free",
                'badge' => match (true) {
                    (int) $node->free_allocations_count === 0 => ['None free', 'danger'],
                    $node->free_allocations_count <= self::LOW => ['Low', 'warning'],
                    default => ['OK', 'success'],
                },
            ]),
            'empty' => 'No nodes yet.',
            'url' => null,
            'linkLabel' => null,
        ];
    }
}

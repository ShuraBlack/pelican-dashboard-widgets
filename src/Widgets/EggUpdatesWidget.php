<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Egg;
use Filament\Widgets\Widget;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

class EggUpdatesWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::update-list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('egg_updates') && (user()?->can('viewAny', Egg::class) ?? false);
    }

    protected function getViewData(): array
    {
        return [
            'heading' => 'Egg updates',
            'icon' => 'tabler-egg',
            // Pelican's p:egg:check-updates flags outdated eggs in this cache key (1 h); the plugin's provider keeps it fresh.
            'items' => Egg::query()
                ->whereNotNull('update_url')
                ->withCount('servers')
                ->get()
                ->filter(fn (Egg $egg) => cache()->get("eggs.$egg->uuid.update", false))
                ->map(fn (Egg $egg) => ['name' => $egg->name, 'detail' => trans_choice(':count server|:count servers', $egg->servers_count)])
                ->values(),
            'empty' => 'All eggs with an update URL are up to date.',
            'url' => DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Eggs\EggResource'),
            'linkLabel' => 'Manage eggs',
        ];
    }
}

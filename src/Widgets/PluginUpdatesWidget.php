<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Plugin;
use Filament\Widgets\Widget;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

class PluginUpdatesWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('plugin_updates') && (user()?->can('viewAny', Plugin::class) ?? false);
    }

    protected function getViewData(): array
    {
        return [
            'heading' => 'Plugin updates',
            'icon' => 'tabler-puzzle',
            // Pelican caches each plugin's update check for 10 minutes; the widget is lazy-loaded, so slow hosts don't block the page.
            'items' => Plugin::all()
                ->filter(fn (Plugin $plugin) => $plugin->isUpdateAvailable())
                ->map(fn (Plugin $plugin) => ['name' => $plugin->name, 'detail' => $plugin->version, 'badge' => ['Update available', 'warning']])
                ->values(),
            'empty' => 'All plugins are up to date.',
            'url' => DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Plugins\PluginResource'),
            'linkLabel' => 'Manage plugins',
        ];
    }
}

<?php

namespace ShuraBlack\DashboardWidgets;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use ShuraBlack\DashboardWidgets\Widgets\ActivityWidget;
use ShuraBlack\DashboardWidgets\Widgets\EggUpdatesWidget;
use ShuraBlack\DashboardWidgets\Widgets\NodeDetailsWidget;
use ShuraBlack\DashboardWidgets\Widgets\NotesWidget;
use ShuraBlack\DashboardWidgets\Widgets\PluginUpdatesWidget;
use ShuraBlack\DashboardWidgets\Widgets\SystemOverviewWidget;
use Throwable;

class DashboardWidgetsPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    /** setting key => label */
    private const WIDGETS = [
        'system' => 'System overview (panel, servers, nodes)',
        'node_details' => 'Node details (live usage per node)',
        'plugin_updates' => 'Plugin updates',
        'egg_updates' => 'Egg updates',
        'notes' => 'Notes',
        'activity' => 'Recent activity',
    ];

    public function getId(): string
    {
        return 'dashboard-widgets';
    }

    public function register(Panel $panel): void
    {
        // Registration order = dashboard order: all use sort 2, which keeps Pelican's update widget (0) above
        // and its support (3) / help (4) widgets below.
        $panel->widgets([
            SystemOverviewWidget::class,
            NodeDetailsWidget::class,
            PluginUpdatesWidget::class,
            EggUpdatesWidget::class,
            NotesWidget::class,
            ActivityWidget::class,
        ]);

        // String scope instead of the class, so a renamed Pelican dashboard can't crash the plugin.
        $panel->renderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => '<style id="dashboard-widgets">' . file_get_contents(plugin_path($this->getId(), 'resources/css/dashboard.css')) . '</style>',
            scopes: 'App\Filament\Admin\Pages\Dashboard',
        );
    }

    public function boot(Panel $panel): void {}

    public static function isEnabled(string $widget): bool
    {
        return (bool) config("dashboard-widgets.$widget", true);
    }

    public function getSettingsFormData(): array
    {
        return array_map(fn ($value) => (bool) $value, config('dashboard-widgets', []));
    }

    public function getSettingsForm(): array
    {
        return array_map(
            fn (string $key) => Toggle::make($key)->label(self::WIDGETS[$key])->default(true),
            array_keys(self::WIDGETS),
        );
    }

    public function saveSettings(array $data): void
    {
        $values = [];
        foreach (array_keys(self::WIDGETS) as $key) {
            // Explicit "true"/"false": an empty value would fall back to the default (on).
            $values['DASHBOARD_WIDGETS_' . strtoupper($key)] = ($data[$key] ?? true) ? 'true' : 'false';
        }

        // The panel swallows exceptions from saveSettings, so report failure ourselves.
        try {
            $this->writeToEnvironment($values);
        } catch (Throwable $exception) {
            Notification::make()->title('Could not save settings')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Dashboard widgets saved')->success()->send();
    }

    /** URL of a Pelican page or resource, or null if Pelican renamed it. */
    public static function url(string $class): ?string
    {
        return class_exists($class) ? $class::getUrl() : null;
    }
}

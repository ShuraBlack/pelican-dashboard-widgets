<?php

namespace ShuraBlack\DashboardWidgets;

use App\Contracts\Plugins\HasPluginSettings;
use App\Services\Helpers\SoftwareVersionService;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\View\PanelsRenderHook;
use ShuraBlack\DashboardWidgets\Widgets\ActivityWidget;
use ShuraBlack\DashboardWidgets\Widgets\BackupsWidget;
use ShuraBlack\DashboardWidgets\Widgets\EggUpdatesWidget;
use ShuraBlack\DashboardWidgets\Widgets\NodeCapacityWidget;
use ShuraBlack\DashboardWidgets\Widgets\NodeDetailsWidget;
use ShuraBlack\DashboardWidgets\Widgets\NotesWidget;
use ShuraBlack\DashboardWidgets\Widgets\PluginUpdatesWidget;
use ShuraBlack\DashboardWidgets\Widgets\SchedulesWidget;
use ShuraBlack\DashboardWidgets\Widgets\SystemOverviewWidget;
use ShuraBlack\DashboardWidgets\Widgets\UsersWidget;
use Throwable;

class DashboardWidgetsPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    /** setting key => [label, widget class]; this is also the default order */
    private const WIDGETS = [
        'system' => ['System overview', SystemOverviewWidget::class],
        'node_details' => ['Node details', NodeDetailsWidget::class],
        'node_capacity' => ['Node capacity', NodeCapacityWidget::class],
        'users' => ['Users & security', UsersWidget::class],
        'plugin_updates' => ['Plugin updates', PluginUpdatesWidget::class],
        'egg_updates' => ['Egg updates', EggUpdatesWidget::class],
        'backups' => ['Backups', BackupsWidget::class],
        'schedules' => ['Schedules', SchedulesWidget::class],
        'notes' => ['Notes', NotesWidget::class],
        'activity' => ['Recent activity', ActivityWidget::class],
    ];

    private const REFRESH = ['10' => 'Every 10 seconds', '30' => 'Every 30 seconds', '60' => 'Every minute', '0' => 'Off'];

    private const DASHBOARD = 'App\Filament\Admin\Pages\Dashboard';

    public function getId(): string
    {
        return 'dashboard-widgets';
    }

    public function register(Panel $panel): void
    {
        // Plugin configs are loaded before the panels are built, so the configured order applies here.
        // All widgets use sort 2: registration order = dashboard order, between Pelican's update widget (0)
        // and its support (3) / help (4) widgets.
        $panel->widgets(collect(self::WIDGETS)
            ->keys()
            ->sortBy(fn (string $key, int $index) => (int) config("dashboard-widgets.widgets.$key.order") ?: ($index + 1) * 10)
            ->map(fn (string $key) => self::WIDGETS[$key][1])
            ->values()
            ->all());

        // String scopes instead of the class, so a renamed Pelican dashboard can't crash the plugin.
        $panel->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '<style id="dashboard-widgets">' . self::css() . '</style>', scopes: self::DASHBOARD);
        $panel->renderHook(PanelsRenderHook::PAGE_HEADER_HEADING_BEFORE, fn (): string => self::heading(), scopes: self::DASHBOARD);
        $panel->renderHook(PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, fn (): string => self::links(), scopes: self::DASHBOARD);
    }

    public function boot(Panel $panel): void {}

    public static function isEnabled(string $widget): bool
    {
        return (bool) config("dashboard-widgets.widgets.$widget.enabled", true);
    }

    /** Refresh interval in seconds for the live widgets, or null when turned off. */
    public static function refreshSeconds(): ?int
    {
        return ((int) config('dashboard-widgets.refresh', 30)) ?: null;
    }

    /** URL of a Pelican page or resource, or null if Pelican renamed it. */
    public static function url(string $class): ?string
    {
        return class_exists($class) ? $class::getUrl() : null;
    }

    private static function css(): string
    {
        $css = file_get_contents(plugin_path('dashboard-widgets', 'resources/css/dashboard.css'));

        // Widgets set to half width, matched by the end of their class name in wire:name (backslash doubled for CSS).
        $half = collect(self::WIDGETS)
            ->filter(fn (array $widget, string $key) => config("dashboard-widgets.widgets.$key.width") === 'half')
            ->map(fn (array $widget) => '.fi-sc > :has(> [wire\:name$="\\\\' . class_basename($widget[1]) . '"])')
            ->implode(',');
        if ($half !== '') {
            $css .= "@media (min-width: 768px) { $half { grid-column: auto !important; } }";
        }

        if (config('dashboard-widgets.hide_canary')) {
            $css .= '.fi-sc > :has(> [wire\:name$="Admin\\\\Widgets\\\\CanaryWidget"]) { display: none !important; }';
        }

        return $css;
    }

    private static function heading(): string
    {
        $heading = config('dashboard-widgets.heading');
        $subheading = config('dashboard-widgets.subheading');

        if (blank($heading) && blank($subheading)) {
            return '';
        }

        $version = app(SoftwareVersionService::class)->currentPanelVersion();
        $hour = now()->hour;
        $placeholders = [
            '{user}' => user()?->username ?? '',
            '{app}' => config('app.name'),
            '{version}' => $version,
            '{greeting}' => $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'),
        ];

        // An empty field keeps Pelican's own text, since the view replaces both lines.
        return view('dashboard-widgets::heading', [
            'heading' => strtr(filled($heading) ? $heading : trans('admin/dashboard.heading', ['app' => config('app.name')]), $placeholders),
            'subheading' => strtr(filled($subheading) ? $subheading : trans('admin/dashboard.version', ['version' => $version]), $placeholders),
        ])->render();
    }

    private static function links(): string
    {
        $links = array_filter(config('dashboard-widgets.links', []), fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null));

        return $links === [] ? '' : view('dashboard-widgets::links', ['links' => $links])->render();
    }

    public function getSettingsFormData(): array
    {
        $config = config('dashboard-widgets', []);
        foreach (array_keys(self::WIDGETS) as $key) {
            $config['widgets'][$key]['enabled'] = (bool) ($config['widgets'][$key]['enabled'] ?? true);
        }
        foreach (['panel_host', 'hide_canary'] as $key) {
            $config[$key] = (bool) ($config[$key] ?? false);
        }

        return $config;
    }

    public function getSettingsForm(): array
    {
        return [
            Section::make('Page')->schema([
                TextInput::make('heading')->label('Heading')->maxLength(150)
                    ->helperText('Empty = Pelican\'s heading. Placeholders: {greeting}, {user}, {app}, {version}'),
                TextInput::make('subheading')->label('Description')->maxLength(300)
                    ->helperText('Empty = Pelican\'s version line. Same placeholders.'),
                Repeater::make('links')->label('Header links')->maxItems(5)->defaultItems(0)->columns(2)->schema([
                    TextInput::make('label')->required()->maxLength(40),
                    TextInput::make('url')->label('URL')->required()->url()->startsWith(['https://', 'http://']),
                ]),
                Toggle::make('hide_canary')->label('Hide Pelican\'s canary build notice')
                    ->helperText('"Support Pelican" and "Need help?" always stay.'),
            ]),
            Section::make('Widgets')
                ->description('Order: lower numbers come first; empty = default (10, 20, 30 …).')
                ->schema(array_map(
                    fn (string $key) => Grid::make(3)->schema([
                        Toggle::make("widgets.$key.enabled")->label(self::WIDGETS[$key][0])->inline(false),
                        Select::make("widgets.$key.width")->label('Width')->options(['full' => 'Full', 'half' => 'Half'])->selectablePlaceholder(false),
                        TextInput::make("widgets.$key.order")->label('Order')->integer()->minValue(1)->maxValue(999),
                    ]),
                    array_keys(self::WIDGETS),
                )),
            Section::make('Live widgets')->schema([
                Select::make('refresh')->label('Refresh system overview and node details')->options(self::REFRESH)->selectablePlaceholder(false),
                Toggle::make('panel_host')->label('Show the panel server in node details')
                    ->helperText('CPU, memory, swap and disk of the machine the panel runs on (Linux). Only for admins with "view health".'),
            ]),
        ];
    }

    public function saveSettings(array $data): void
    {
        $bool = fn ($value) => $value ? 'true' : 'false';

        $values = [
            'DASHBOARD_WIDGETS_PANEL_HOST' => $bool($data['panel_host'] ?? true),
            'DASHBOARD_WIDGETS_HIDE_CANARY' => $bool($data['hide_canary'] ?? false),
            'DASHBOARD_WIDGETS_REFRESH' => array_key_exists((string) ($data['refresh'] ?? ''), self::REFRESH) ? (string) $data['refresh'] : '30',
            // Single-line text: newlines would break the line-based .env writer.
            'DASHBOARD_WIDGETS_HEADING' => str_replace(["\r", "\n"], ' ', (string) ($data['heading'] ?? '')),
            'DASHBOARD_WIDGETS_SUBHEADING' => str_replace(["\r", "\n"], ' ', (string) ($data['subheading'] ?? '')),
            'DASHBOARD_WIDGETS_LINKS' => rawurlencode(json_encode(array_values(array_map(
                fn ($link) => ['label' => (string) ($link['label'] ?? ''), 'url' => (string) ($link['url'] ?? '')],
                $data['links'] ?? [],
            )))),
        ];

        foreach (array_keys(self::WIDGETS) as $key) {
            $prefix = 'DASHBOARD_WIDGETS_' . strtoupper($key);
            $widget = $data['widgets'][$key] ?? [];
            // Explicit "true"/"false": an empty value would fall back to the default (on).
            $values[$prefix] = $bool($widget['enabled'] ?? true);
            $values[$prefix . '_WIDTH'] = ($widget['width'] ?? '') === 'half' ? 'half' : 'full';
            $values[$prefix . '_ORDER'] = filled($widget['order'] ?? null) ? (string) (int) $widget['order'] : '';
        }

        // The panel swallows exceptions from saveSettings, so report failure ourselves.
        try {
            $this->writeToEnvironment($values);
        } catch (Throwable $exception) {
            Notification::make()->title('Could not save settings')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Dashboard settings saved')->body('Order and link changes apply on the next page load.')->success()->send();
    }
}

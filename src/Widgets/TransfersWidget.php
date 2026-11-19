<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\Server;
use App\Models\ServerTransfer;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// Running transfers (successful = null, as in Pelican's Server::transfer()), then failed ones of the last 7 days.
class TransfersWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'dashboard-widgets::list';

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('transfers') && (user()?->can('viewAny', Server::class) ?? false);
    }

    protected function getViewData(): array
    {
        $nodeIds = user()->accessibleNodes()->pluck('id');
        $transfers = fn () => ServerTransfer::query()
            ->with(['server:id,name', 'oldNode:id,name', 'newNode:id,name'])
            ->where(fn (Builder $query) => $query->whereIn('old_node', $nodeIds)->orWhereIn('new_node', $nodeIds));

        $running = $transfers()->whereNull('successful')->oldest()->get();
        $failed = $transfers()->where('successful', false)->where('updated_at', '>=', now()->subDays(7))->latest('updated_at')->limit(20)->get();

        $item = fn (ServerTransfer $transfer, string $when, array $badge) => [
            'name' => $transfer->server?->name ?? 'Deleted server',
            'detail' => ($transfer->oldNode?->name ?? '?') . ' → ' . ($transfer->newNode?->name ?? '?') . " · $when",
            'badge' => $badge,
            'url' => $transfer->server ? DashboardWidgetsPlugin::serverUrl($transfer->server) : null,
        ];

        return [
            'heading' => 'Server transfers',
            'icon' => 'tabler-transfer',
            'description' => $running->count() . ' running · failed transfers of the last 7 days below',
            'items' => $running->map(fn (ServerTransfer $transfer) => $item($transfer, 'started ' . $transfer->created_at?->diffForHumans(), ['Running', 'primary']))
                ->concat($failed->map(fn (ServerTransfer $transfer) => $item($transfer, 'failed ' . $transfer->updated_at?->diffForHumans(), ['Failed', 'danger']))),
            'empty' => 'No running or recently failed transfers.',
            'url' => null,
            'linkLabel' => null,
        ];
    }
}

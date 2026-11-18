<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

class ActivityWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        // Same permission as Pelican's activity log page.
        return DashboardWidgetsPlugin::isEnabled('activity') && (user()?->can('view activityLog') ?? false);
    }

    public function table(Table $table): Table
    {
        $url = DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Activities\ActivityResource');

        return $table
            ->heading('Recent activity')
            ->query(fn () => ActivityLog::query()
                ->with('actor')
                ->whereNotIn('event', ActivityLog::DISABLED_EVENTS)
                ->latest('timestamp')
                ->limit(8))
            ->paginated(false)
            ->columns([
                // Rendered like Pelican's own activity log.
                TextColumn::make('event')
                    ->html()
                    ->icon(fn (ActivityLog $activity) => $activity->getIcon())
                    ->formatStateUsing(fn (ActivityLog $activity) => $activity->getLabel())
                    ->description(fn ($state) => $state),
                TextColumn::make('actor')
                    ->state(fn (ActivityLog $activity) => $activity->actor instanceof User
                        ? $activity->actor->username
                        : ($activity->actor_id === null ? 'System' : 'Deleted user')),
                TextColumn::make('timestamp')
                    ->since()
                    ->alignEnd(),
            ])
            ->headerActions([
                Action::make('all')
                    ->label('View all')
                    ->link()
                    ->url($url)
                    ->visible($url !== null),
            ]);
    }
}

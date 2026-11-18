<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

class UsersWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Users & security';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('users') && (user()?->can('viewAny', User::class) ?? false);
    }

    protected function getStats(): array
    {
        $total = User::query()->count();
        $new = User::query()->where('created_at', '>=', now()->subWeek())->count();
        // App (TOTP) or email two-factor; passkeys are not counted.
        $mfa = User::query()->where(fn (Builder $query) => $query->whereNotNull('mfa_app_secret')->orWhere('mfa_email_enabled', true))->count();
        $mfaPercent = $total > 0 ? (int) round($mfa / $total * 100) : 0;

        $stats = [
            Stat::make('Users', $total)
                ->icon('tabler-users')
                ->description("$new new in the last 7 days")
                ->url(DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Users\UserResource')),
            Stat::make('Two-factor auth', "$mfaPercent %")
                ->icon('tabler-shield-lock')
                ->description("$mfa of $total users")
                ->color($mfaPercent >= 50 ? 'success' : 'warning'),
        ];

        if (user()?->can('view activityLog')) {
            $failed = ActivityLog::query()->where('event', 'auth:fail')->where('timestamp', '>=', now()->subDay())->count();

            $stats[] = Stat::make('Failed logins', $failed)
                ->icon('tabler-lock-exclamation')
                ->description('last 24 hours')
                ->color($failed > 0 ? 'warning' : 'success')
                ->url(DashboardWidgetsPlugin::url('App\Filament\Admin\Resources\Activities\ActivityResource'));
        }

        return $stats;
    }
}

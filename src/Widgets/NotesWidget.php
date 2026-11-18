<?php

namespace ShuraBlack\DashboardWidgets\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use ShuraBlack\DashboardWidgets\DashboardWidgetsPlugin;

// Shared notes for all admins who can see the dashboard. Stored as plain text, rendered escaped.
class NotesWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    private const TABLE = 'dashboard_widgets_notes';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected string $view = 'dashboard-widgets::notes';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canView(): bool
    {
        return DashboardWidgetsPlugin::isEnabled('notes');
    }

    public function mount(): void
    {
        $this->form->fill(['content' => DB::table(self::TABLE)->where('id', 1)->value('content')]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Notes')
                    ->icon('tabler-notes')
                    ->description(fn () => $this->lastSaved())
                    ->headerActions([
                        Action::make('save')
                            ->label('Save')
                            ->icon('tabler-device-floppy')
                            ->action(fn () => $this->save()),
                    ])
                    ->schema([
                        Textarea::make('content')
                            ->hiddenLabel()
                            ->placeholder('Notes for the whole admin team…')
                            ->rows(6)
                            ->autosize()
                            ->maxLength(10000),
                    ]),
            ]);
    }

    // Protected: only reachable through the save action, not as a public Livewire method.
    protected function save(): void
    {
        $content = $this->form->getState()['content'] ?? null;

        DB::table(self::TABLE)->updateOrInsert(
            ['id' => 1],
            ['content' => $content, 'updated_by' => user()?->id, 'updated_at' => now()],
        );

        Notification::make()->title('Notes saved')->success()->send();
    }

    private function lastSaved(): ?string
    {
        $row = DB::table(self::TABLE)->where('id', 1)->first(['updated_at', 'updated_by']);

        if (!$row?->updated_at) {
            return null;
        }

        $by = User::find($row->updated_by)?->username;

        return 'Last saved ' . now()->parse($row->updated_at)->diffForHumans() . ($by ? " by $by" : '');
    }
}

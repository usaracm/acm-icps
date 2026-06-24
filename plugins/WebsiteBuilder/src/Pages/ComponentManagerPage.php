<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use App\Tables\Columns\IndexColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteWidget;

class ComponentManagerPage extends Page implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $title = 'Widgets Manager';

    protected static ?string $navigationIcon = "heroicon-o-puzzle-piece";

    protected static ?string $navigationGroup = 'Website Builder';

    protected static string $view = 'WebsiteBuilder::site-manager';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $slug = 'component-manager';

    public function mount(): void
    {
        $plugin = Plugin::getPlugin('WebsiteBuilder');
        if (!$plugin->isAlreadySetup()) {
            $plugin->firstSetup();
            return;
        }
    }

    public function getViewData(): array
    {
        $plugin = Plugin::getPlugin('WebsiteBuilder');
        return [
            'isPluginActive' => $plugin->getIsPluginActive(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WebsiteWidget::query())
            ->columns([
                IndexColumn::make('no'),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    TableAction::make('builder')
                        ->icon('heroicon-o-cursor-arrow-rays')
                        ->label('Open Content Builder')
                        ->color('secondary')
                        ->url(fn(Model $record): string => ContentBuilderComponentPage::getUrl(['WebsiteWidget' => $record->id]))
                        ->openUrlInNewTab(),
                    TableAction::make('edit')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->label('Edit')
                        ->modalWidth('3xl')
                        ->fillForm(function (Model $record, Table $table): array {
                            $data = $record->attributesToArray();

                            return $data;
                        })
                        ->form($this->getFormSchemas())
                        ->action(function ($record, array $data) {
                            $record->fill($data);
                            $record->save();
                        }),
                    TableAction::make('delete')
                        ->icon('heroicon-o-trash')
                        ->label('Delete')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->delete();
                        }),
                ])
            ])
            ->recordUrl(function (Model $record): string {
                return ContentBuilderComponentPage::getUrl(['WebsiteWidget' => $record->id]);
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Widget')
                ->action(function (array $data) {
                    $component = new WebsiteWidget();
                    $component->fill($data);
                    $component->save();
                })
                ->modalWidth('3xl')
                ->form($this->getFormSchemas()),
        ];
    }

    public function getFormSchemas(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),
        ];
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('WebsiteBuilder')->isUserAllowedToAccessPlugin(auth()->user());
    }
}

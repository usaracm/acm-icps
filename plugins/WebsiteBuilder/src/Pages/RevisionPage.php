<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use App\Tables\Columns\IndexColumn;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteRevision;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ActionGroup;

class RevisionPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'WebsiteBuilder::revision';

    protected static ?string $title = '';

    protected static string $layout = 'WebsiteBuilder::layout.form';

    protected static ?string $slug = 'revision/{website}';

    protected static bool $shouldRegisterNavigation = false;

    public Website $website;

    public function table(Table $table): Table
    {
        return $table
            ->query(WebsiteRevision::query()->where('website_id', $this->website->id)->latest())
            ->columns([
                IndexColumn::make('no'),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Created By'),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->actions([
                ActionGroup::make([
                    TableAction::make('restore')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->label('Restore')
                        ->color('primary')
                        ->action(function (Model $record) {
                            $this->website->update([
                                'slug' => $record->slug,
                                'name' => $record->name,
                            ]);

                            $this->website->syncMeta($record->getAllMeta()->toArray());

                            $this->dispatch('refresh-iframe');
                        }),
                    TableAction::make('preview')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->label('Preview')
                        ->action(function (Model $record) {
                            $this->dispatch('redirect-iframe', ['url' => PreviewRevisionPage::getUrl(['websiteRevision' => $record->id]), 'target' => '_blank']);
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
            ]);
    }

    protected function getPlugin()
    {
        return Plugin::getPlugin('WebsiteBuilder');
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('WebsiteBuilder')->isUserAllowedToAccessPlugin(auth()->user());
    }
}

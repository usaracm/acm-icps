<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use App\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Tables\Columns\IndexColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Unique;
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
use Illuminate\Database\Eloquent\Builder;

class SiteManagerPage extends Page implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $title = 'Site Manager';

    protected static ?string $navigationIcon = "heroicon-o-code-bracket";

    protected static ?string $navigationGroup = 'Website Builder';

    protected static string $view = 'WebsiteBuilder::site-manager';

    protected static bool $shouldRegisterNavigation = true;

    public ?int $newWebsiteId = null;

    public function mount(): void
    {
        $plugin = $this->getPlugin();
        if (!$plugin->isAlreadySetup()) {
            $plugin->firstSetup();
            return;
        }
    }

    public function getViewData(): array
    {
        $plugin = $this->getPlugin();
        return [
            'isPluginActive' => $plugin->getIsPluginActive(),
        ];
    }

    public static function getRoutePath(): string
    {
        return '/site-manager';
    }

    public static function getEloquentQuery(): Builder
    {
        return Website::query()->select(['name', 'slug', 'is_published', 'is_default', 'id', 'scheduled_conference_id']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery())
            ->columns([
                IndexColumn::make('no'),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                ToggleColumn::make('is_published')
                    ->label('Published')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(fn(Website $record) => $record->is_default)
                    ->tooltip(
                        fn(Website $record) =>
                        $record->is_default
                        ? 'Cannot be disabled because this page is the default home page.'
                        : ''
                    ),
                IconColumn::make('is_default')
                    ->label('Home')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->recordClasses(
                fn($record) =>
                $record->is_published ? null : 'bg-gray-100 dark:bg-gray-800'
            )
            ->actions([
                ActionGroup::make([
                    TableAction::make('builder')
                        ->icon('heroicon-o-cursor-arrow-rays')
                        ->label('Content Builder')
                        ->color('secondary')
                        ->url(fn(Model $record): string => ContentBuilderPage::getUrl(['website' => $record->id]))
                        ->openUrlInNewTab(),
                    TableAction::make('view')
                        ->icon('heroicon-o-eye')
                        ->label('View')
                        ->hidden(fn() => !$this->getPlugin()->getIsPluginActive())
                        ->color('secondary')
                        ->url(function ($record) {
                            if ($record->is_default) {
                                $url = route('livewirePageGroup.scheduledConference.pages.home');
                            } else {
                                try {
                                    $url = route('livewirePageGroup.scheduledConference.pages.sites', ['website' => $record->slug]);
                                } catch (\Throwable $th) {
                                    $url = '#';
                                }
                            }
                            return $url;
                        })
                        ->openUrlInNewTab(),
                    TableAction::make('copy')
                        ->icon('heroicon-o-clipboard')
                        ->label('Copy URL')
                        ->color('secondary')
                        ->hidden(fn() => !$this->getPlugin()->getIsPluginActive())
                        ->action(function (Model $record) {
                            if ($record->is_default) {
                                $url = route('livewirePageGroup.scheduledConference.pages.home');
                            } else {
                                $url = route('livewirePageGroup.scheduledConference.pages.sites', ['website' => $record->slug]);
                            }

                            // Dispatch Livewire event (listen for it in JS to copy to clipboard)
                            $this->dispatch('copy-to-clipboard', $url);

                            Notification::make()
                                ->title('URL copied')
                                ->body($url)
                                ->success()
                                ->send();
                        }),
                    TableAction::make('set_as_home')
                        ->icon('heroicon-o-home')
                        ->color('warning')
                        ->label('Set as Home')
                        ->hidden(fn(Model $record) => $record->is_default)
                        ->action(function ($record) {
                            if (!$record->is_published) {
                                Notification::make()
                                    ->body('Cannot set an unpublished page as home page. Please publish the page first.')
                                    ->title('Page not published')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            Website::where('is_default', true)
                                ->where('scheduled_conference_id', app()->getCurrentScheduledConferenceId())
                                ->update(['is_default' => false]);
                            $record->is_default = true;
                            $record->save();
                        }),
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
                            if ($record->is_default) {
                                Notification::make()
                                    ->body('Cannot delete home page')
                                    ->title('Cannot delete page')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $record->delete();
                        }),
                ])
            ])
            ->recordUrl(function (Model $record): string {
                return ContentBuilderPage::getUrl(['website' => $record->id]);
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create page')
                ->modalWidth('3xl')
                ->url(fn() => CreateContentBuilderPage::getUrl()),
            \Filament\Actions\ActionGroup::make([
                Action::make('header')
                    ->label('Edit Header Page')
                    ->color('gray')
                    ->icon('heroicon-o-pencil-square')
                    ->action(function () {
                        return redirect()->to(ContentBuilderLayoutPage::getUrl(['websiteLayout' => 'header']));
                    }),
                Action::make('footer')
                    ->label('Edit Footer Page')
                    ->color('gray')
                    ->icon('heroicon-o-pencil-square')
                    ->action(function () {
                        return redirect()->to(ContentBuilderLayoutPage::getUrl(['websiteLayout' => 'footer']));
                    }),
                Action::make('settings')
                    ->hiddenLabel()
                    ->icon('heroicon-o-cog-6-tooth')
                    ->tooltip('General Website Settings')
                    ->fillForm(fn() => [
                        // penting: isi state field favicon
                        'favicon' => app()
                            ->getCurrentScheduledConference()
                            ->getMedia('favicon'),
                    ])
                    ->form([
                        SpatieMediaLibraryFileUpload::make('favicon')
                            ->label('Website Logo (Favicon)')
                            ->image()
                            ->collection('favicon')
                            ->disk('media-library')
                            ->helperText('Not related to the Logo in the header, this is used as favicon for the website.')
                            ->dehydrated(false)
                            ->imageCropAspectRatio('1:1')
                            ->maxFiles(1)
                            ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg', 'image/jpg'])
                            ->imageResizeTargetWidth(180)
                            ->imageResizeTargetHeight(180)
                            ->model(fn() => app()->getCurrentScheduledConference()),
                    ])
            ]),
        ];
    }

    public function getFormSchemas(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->helperText('Only lowercase letters, numbers, and hyphens are allowed. e.g., my-page-slug')
                ->unique(
                    table: Website::class,
                    column: 'slug',
                    ignoreRecord: true,
                    modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('scheduled_conference_id', app()->getCurrentScheduledConferenceId());
                    }
                ),
        ];
    }

    public function applyTemplate($templateId, $websiteId)
    {
        try {
            $website = Website::find($websiteId);

            if (!$website) {
                Notification::make()
                    ->title('Error')
                    ->body('Website not found')
                    ->danger()
                    ->send();
                return ['success' => false];
            }

            // Handle custom/blank template
            if ($templateId === 'custom') {
                $website->setManyMeta([
                    'main_css' => '',
                    'section_css' => '',
                    'content_html' => '',
                ]);

                Notification::make()
                    ->title('Success')
                    ->body('Blank page created successfully')
                    ->success()
                    ->send();
            } else {
                // Fetch template from API
                $response = Http::acceptJson()->get(app()->getApiUrl('service/website-templates/' . $templateId));

                if ($response->failed()) {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to fetch template')
                        ->danger()
                        ->send();
                    return ['success' => false];
                }

                $website->setManyMeta([
                    'main_css' => $response->json('main_css', ''),
                    'section_css' => $response->json('section_css', ''),
                    'content_html' => $response->json('html', ''),
                ]);

                Notification::make()
                    ->title('Success')
                    ->body('Template applied successfully')
                    ->success()
                    ->send();
            }

            // Close modal and redirect
            $this->dispatch('close-modal', id: 'template');

            return [
                'success' => true,
                'redirect' => ContentBuilderPage::getUrl(['website' => $website->id]),
            ];
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
            return ['success' => false];
        }
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('WebsiteBuilder')->isUserAllowedToAccessPlugin(auth()->user());
    }

    public function getPlugin()
    {
        return Plugin::getPlugin('WebsiteBuilder');
    }
}

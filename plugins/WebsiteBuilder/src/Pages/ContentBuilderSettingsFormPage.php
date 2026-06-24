<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use App\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Validation\Rules\Unique;
use WebsiteBuilder\Models\Website;
use Filament\Forms\Form;

class ContentBuilderSettingsFormPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $layout = 'WebsiteBuilder::layout.form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = '';

    protected static string $view = 'WebsiteBuilder::content-builder-settings-form';

    protected static ?string $slug = 'content-builder-settings/{website}';

    public Website $website;

    public ?array $data = [];

    public function mount(Website $website): void
    {
        $this->website = $website;

        $this->form->fill([...$website->attributesToArray(), 'description' => $website->getMeta('description') ?? '']);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General')
                    ->schema([
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
                    ]),
                Section::make('This Page')
                    ->schema([
                        Fieldset::make('Settings')
                            ->schema([
                                Grid::make()
                                    ->columns([
                                        'default' => 2,
                                    ])
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Website Title')
                                            ->columnSpanFull()
                                            ->required(),
                                        TextInput::make('slug')
                                            ->label('Slug')
                                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                            ->helperText('Only lowercase letters, numbers, and hyphens are allowed. e.g., my-page-slug')
                                            ->prefix(fn() => route('livewirePageGroup.scheduledConference.pages.home') . '/')
                                            ->columnSpanFull()
                                            ->required()
                                            ->unique(
                                                table: Website::class,
                                                column: 'slug',
                                                ignorable: $this->website,
                                                modifyRuleUsing: fn(Unique $rule) => $rule->where('scheduled_conference_id', app()->getCurrentScheduledConference()?->id ?? null)
                                            ),
                                        Toggle::make('is_default')
                                            ->label('Set as Home Page')
                                            ->inline(false)
                                            ->helperText(function (Get $get) {
                                                if (!$get('is_published')) {
                                                    return 'Page must be published to be set as home page.';
                                                }
                                                if (!$get('is_default')) {
                                                    return null;
                                                }

                                                return 'This page will be accessible directly via the base URL: ' . route('livewirePageGroup.scheduledConference.pages.home');
                                            })
                                            ->disabled(fn(Get $get) => $get('is_published') === false && !$get('is_default'))
                                            ->reactive(),
                                        Toggle::make('is_published')
                                            ->reactive()
                                            ->label('Page Published')
                                            ->inline(false)
                                            ->hidden(fn(Get $get) => $get('is_default') === true),
                                    ]),
                            ]),
                        Fieldset::make('Meta')
                            ->schema([
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->maxLength(160)
                                    ->helperText('A brief description of the page for SEO purposes. Recommended length is up to 160 characters.')
                                    ->columnSpanFull(),
                            ]),
                    ])

            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (!empty($data['description'])) {
            $this->website->setMeta('description', $data['description']);
        }

        if (isset($data['is_default']) && $data['is_default']) {
            $data['is_published'] = true;
            Website::where('scheduled_conference_id', app()->getCurrentScheduledConference()->id)
                ->where('id', '!=', $this->website->id)
                ->update(['is_default' => false]);
        } else {
            $data['is_default'] = false;
        }

        $this->website->update($data);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save'),
            DeleteAction::make()
                ->action(function () {
                    if ($this->website->is_default) {
                        Notification::make()
                            ->danger()
                            ->title('Default website cannot be deleted')
                            ->send();
                        return;
                    }
                    $this->dispatch('redirect-iframe', ["url" => SiteManagerPage::getUrl()]);
                    $this->website->delete();
                })
                ->hidden(fn() => !$this->website || $this->website->is_default)
                ->record(fn() => $this->website),
        ];
    }

    public function getFormActionsAlignment(): Alignment
    {
        return Alignment::Between;
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

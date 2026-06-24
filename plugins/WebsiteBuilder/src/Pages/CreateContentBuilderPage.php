<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Unique;
use WebsiteBuilder\Models\Website;

class CreateContentBuilderPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Create New Page';

    protected static string $view = 'WebsiteBuilder::create-content-builder';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'create-content-builder';

    public ?array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                ViewField::make('templateId')
                    ->label('Template')
                    ->view('WebsiteBuilder::components.insert-template-modal')
                    ->viewData(function () {
                        $response = Http::acceptJson()->get(app()->getApiUrl('service/website-templates'));
                        return [
                            'templates' => $response->json('templates', []),
                        ];
                    })
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $website = new Website();
        $website->fill($data);
        $website->save();

        // Apply template if selected
        if (!$data['templateId']) {
            Notification::make()
                ->title('Error')
                ->body('Something went wrong. Please try again.')
                ->danger()
                ->send();
            return;
        }

        // If custom template selected, just redirect to builder
        if ($data['templateId'] === 'custom') {
            $this->redirect(ContentBuilderPage::getUrl(['website' => $website->id]));
            return;
        }

        // Fetch and apply template content
        $response = Http::acceptJson()->get(app()->getApiUrl('service/website-templates/' . $data['templateId']));
        $website->setManyMeta([
            'main_css' => $response->json('main_css', ''),
            'section_css' => $response->json('section_css', ''),
            'content_html' => $response->json('html', ''),
        ]);

        $this->redirect(ContentBuilderPage::getUrl(['website' => $website->id]));
    }

    public function getFormActions()
    {
        return [
            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(fn() => SiteManagerPage::getUrl()),
            Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
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

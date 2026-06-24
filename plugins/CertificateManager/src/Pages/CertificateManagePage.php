<?php

namespace CertificateManager\Pages;

use App\Facades\Plugin;
use App\Facades\Setting;
use App\Managers\PaymentManager;
use App\Models\Author;
use App\Models\Enums\SubmissionStatus;
use App\Models\Payment;
use App\Models\Submission;
use App\Tables\Columns\IndexColumn;
use CertificateManager\Enums\CertificateTemplateType;
use CertificateManager\Facades\CertificateFacade;
use CertificateManager\Models\CertificateTemplate;
use CertificateManager\Models\Template;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Amp\async;

class CertificateManagePage extends Page implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $title = 'Manage Certificates';

    protected static string $view = 'CertificateManager::index';

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationGroup = 'Certificate Manager';

    // protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $plugin = Plugin::getPlugin('CertificateManager');
        if (!$plugin->isAlreadySetup()) {
            $plugin->firstSetup();
            return;
        } else {
            async(fn() => CertificateFacade::check());
        }
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('CertificateManager')->isUserAllowedToAccessPlugin(auth()->user());
    }

    public static function getRoutePath(): string
    {
        return '/certificate-manager';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Template')
                ->modalWidth(MaxWidth::Medium)
                ->model(CertificateTemplate::class)
                ->form(function ($form) {
                    return $form
                        ->schema([
                            TextInput::make('name')
                                ->label("Template Name")
                                ->required(),
                            TextInput::make('email')
                                ->required()
                                ->email()
                                ->helperText('Email address to access the template (must be a Google account)'),
                            Select::make('template')
                                ->required()
                                ->live()
                                ->options(Template::pluck('name', 'id')),
                            Grid::make()
                                ->columns(1)
                                ->visible(function (Get $get) {
                                    $template = $get('template');

                                    if (is_null($template)) return false;

                                    $template = Template::find($template);

                                    if (is_null($template)) return false;

                                    return $template->type->is(CertificateTemplateType::Submission);
                                })
                                ->schema([
                                    CheckboxList::make('submission_status')
                                        ->required()
                                        ->options(collect(array_combine(SubmissionStatus::values(), SubmissionStatus::values()))->filter(fn($value) => !in_array($value, ['Payment Declined', 'On Payment', 'Incomplete'])))
                                        ->helperText('Choose the submission status to include on the certificate')
                                ]),
                            Grid::make()
                                ->columns(1)
                                ->visible(function (Get $get) {
                                    $template = $get('template');

                                    if (is_null($template)) return false;

                                    $template = Template::find($template);

                                    if (is_null($template)) return false;

                                    return $template->type->is(CertificateTemplateType::Participant);
                                })
                                ->schema([
                                    Select::make('paid_status')
                                        ->required()
                                        ->default('Paid')
                                        ->options([
                                            'All' => 'All',
                                            'Paid' => 'Paid',
                                            'Unpaid' => 'Unpaid',
                                        ]),
                                ]),
                        ]);
                })
                ->using(function (array $data, CreateAction $action) {
                    try {
                        DB::beginTransaction();

                        $template = Template::find($data['template']);
                        $templateData = CertificateFacade::createTemplate($template, $data['name'], $data['email']);

                        $record = new CertificateTemplate;
                        $record->fill($data);
                        $record->template_id = Arr::get($templateData, 'id');
                        $record->save();

                        $record->setMeta('template_url', Arr::get($templateData, 'document_url'));

                        if ($template->type->is(CertificateTemplateType::Submission)) {
                            $this->handleSubmissionType($record, $data['submission_status']);
                        }

                        if ($template->type->is(CertificateTemplateType::Participant)) {
                            $this->handleParticipantType($record, $data['paid_status']);
                        }

                        DB::commit();
                    } catch (\Throwable $th) {
                        Log::error($th);
                        DB::rollBack();

                        $action->failureNotificationTitle($th->getMessage());
                        $action->sendFailureNotification();

                        $action->cancel();
                    }

                    return $record;
                }),
            Action::make('license')
                ->label('Insert License')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->modalWidth(MaxWidth::Medium)
                ->form([
                    TextInput::make('license')
                        ->required(),
                ])
                ->action(function (Action $action, array $data) {
                    try {
                        $message = CertificateFacade::license($data['license']);

                        CertificateFacade::check();

                        Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    } catch (\Throwable $th) {
                        Notification::make()
                            ->title($th->getMessage())
                            ->danger()
                            ->send();
                        $action->halt();
                        return;
                    }
                })
        ];
    }

    protected function handleSubmissionType(CertificateTemplate $record, array $submissionStatuses)
    {
        $currentNumber = $record->getMeta('number');

        $record->setMeta('fields', [
            'Submission Title',
            'Authors',
            'Track',
        ]);

        Submission::query()
            ->with([
                'meta',
                'user',
                'authors' => fn($query) => $query->ordered(),
                'track',
            ])
            ->whereIn('status', $submissionStatuses)
            ->each(function (Submission $submission, $key) use ($record, &$currentNumber) {

                $certificate = $record->certificates()->create([
                    'email' => $submission->user->email,
                    'number' => ++$currentNumber,
                ]);

                $certificate->certifiable()->associate($submission);
                $certificate->save();

                $certificate->setMeta('form_data', [
                    'Submission Title' => $submission->getMeta('title'),
                    'Authors' => $submission->authors->map(fn(Author $author) => $author->full_name)->implode(', '),
                    'Track' => $submission->track->title,
                ]);
            });
    }

    protected function handleParticipantType(CertificateTemplate $record, string $paidStatus)
    {
        $record->setMeta('fields', [
            'Participant Name',
            'Fee Name',
            'Fee Amount',
            'Paid At',
            'Registered At',
            'Invoice'
        ]);

        Payment::query()
            ->type(PaymentManager::TYPE_PARTICIPANT_FEE)
            ->when($paidStatus == 'Paid', fn($query) => $query->whereNotNull('paid_at'))
            ->when($paidStatus == 'Unpaid', fn($query) => $query->whereNull('paid_at'))
            ->with([
                'fee',
                'model',
                'user',
                'scheduledConference'
            ])
            ->each(function (Payment $payment, int $key) use ($record) {
                $certificate = $record->certificates()->create([
                    'email' => $payment->model->email,
                    'number' => $key + 1,
                ]);

                $certificate->certifiable()->associate($payment);
                $certificate->save();

                $certificate->setMeta('form_data', [
                    'Participant Name' => $payment->model->full_name,
                    'Fee Name' => $payment->fee->name,
                    'Fee Amount' => $payment->amount ? $payment->getFormattedFee() : 0,
                    'Paid At' => $payment->paid_at ? $payment->paid_at->format(Setting::get('format_date')) : '-',
                    'Registered At' => $payment->created_at ? $payment->created_at->format(Setting::get('format_date')) : '-',
                    'Invoice' => $payment->invoice,
                ]);
            });
    }

    protected function getViewData(): array
    {
        $plugin = Plugin::getPlugin('CertificateManager');

        return [
            'isExpired' => $plugin->isExpired(),
            'expiredAt' => $plugin->expiredAt(),
            'plugin' => Plugin::getPlugin('CertificateManager'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return CertificateTemplate::query();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery())
            ->recordUrl(fn(CertificateTemplate $record) => CertificateTemplateDetailPage::getUrl(['record' => $record]))
            ->columns([
                IndexColumn::make('no'),
                TextColumn::make('name')
                    ->grow(false)
                    ->searchable(),
                TextColumn::make('certificates_count')
                    ->label("Certificates")
                    ->counts('certificates')
            ])
            ->actions([
                TableAction::make('open_template')
                    ->url(fn(CertificateTemplate $record) => $record->getMeta('template_url'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->using(function (CertificateTemplate $record, DeleteAction $action) {
                        if ($record->certificates_count) {
                            $action->failureNotificationTitle("Template cannot be deleted when there is data in it");
                            return false;
                        }

                        $record->delete();
                        return true;
                    }),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label("Template Name"),
                TextInput::make('email')
                    ->required()
                    ->email()
                    ->helperText('Email address to access the template (must be a Google account)'),
            ]);
    }
}

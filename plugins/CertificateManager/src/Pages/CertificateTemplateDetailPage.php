<?php

namespace CertificateManager\Pages;

use App\Facades\Plugin;
use App\Forms\Components\TinyEditor;
use App\Models\Author;
use App\Models\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Tables\Columns\IndexColumn;
use CertificateManager\Enums\CertificateTemplateType;
use CertificateManager\Facades\CertificateFacade;
use CertificateManager\Mail\Templates\CertificateInfoMail;
use CertificateManager\Models\Certificate;
use CertificateManager\Models\CertificateTemplate;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ActionGroup as TableActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Amp\async;

class CertificateTemplateDetailPage extends Page implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    protected static string $view = 'CertificateManager::template-detail';

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static bool $shouldRegisterNavigation = false;

    public CertificateTemplate $record;

    public function mount(CertificateTemplate $record): void {}

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('CertificateManager')->isUserAllowedToAccessPlugin(auth()->user());
    }

    public static function getRoutePath(): string
    {
        return '/certificate-manager/{record}';
    }

    public function getBreadcrumbs(): array
    {
        return [
            CertificateManagePage::getUrl() => "Certificate Templates"
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->model(Certificate::class)
                ->modalWidth(MaxWidth::ExtraLarge)
                ->fillForm([
                    'number' => $this->record->getMeta('number'),
                ])
                ->form(fn($form) => $this->form($form))
                ->using(function ($data) {
                    try {
                        DB::beginTransaction();

                        $record = $this->record->certificates()->create(array_merge($data, ['number' => $this->record->getMeta('number')]));

                        if (data_get($data, 'meta')) {
                            $record->setManyMeta(data_get($data, 'meta'));
                        }

                        DB::commit();
                    } catch (\Throwable $th) {
                        DB::rollBack();

                        throw $th;
                    }

                    return $record;
                }),
            ActionGroup::make([
                Action::make('open_template')
                    ->url($this->record->getMeta('template_url'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->openUrlInNewTab(),
                Action::make('edit_mail_template')
                    ->label("Edit Mail Template")
                    ->icon('heroicon-o-envelope')
                    ->fillForm(fn() => [
                        'subject' => $this->record->getMeta('custom_mail_subject') ?? CertificateInfoMail::getDefaultSubject(),
                        'message' => $this->record->getMeta('custom_mail_html') ?? CertificateInfoMail::getDefaultHtmlTemplate(),
                    ])
                    ->form(
                        fn($form) => $form->schema([
                            TextInput::make('subject'),
                            RichEditor::make('message')
                                ->label(__('general.message'))
                        ])->fill([
                            'subject' => $this->record->getMeta('custom_mail_subject') ?? CertificateInfoMail::getDefaultSubject(),
                            'message' => $this->record->getMeta('custom_mail_html') ?? CertificateInfoMail::getDefaultHtmlTemplate(),
                        ])
                    )
                    ->action(function (array $data, Action $action) {
                        $this->record->setManyMeta([
                            'custom_mail_subject' => $data['subject'],
                            'custom_mail_html' => $data['message'],
                        ]);
                        $action->successNotificationTitle('Mail template saved.');
                        $action->success();
                    }),
                Action::make('generate_all_certificate')
                    ->label("Generate All Certificate")
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->form([
                        Checkbox::make('send_email_notification')
                            ->label("Send email notification to certificate receiver."),
                        Checkbox::make('force_regenerate_certificate')
                            ->label("Regenerate existing certificate."),
                    ])
                    ->action(function (Action $action, array $data) {
                        $plugin = Plugin::getPlugin('CertificateManager');

                        if ($plugin->isExpired()) {
                            $action->failureNotificationTitle('The plugin license has expired and cannot continue the action.');
                            $action->failure();
                            return;
                        }

                        CertificateFacade::generateDocumentByTemplate($this->record, $data['force_regenerate_certificate'], ['email' => $data['send_email_notification']]);


                        $action->successNotificationTitle('Certificate is generating in background.');
                        $action->sendSuccessNotification();
                    }),
                Action::make('import_submission')
                    ->label("Import Submissions")
                    ->icon('heroicon-o-arrow-down-circle')
                    ->modalWidth(MaxWidth::ExtraLarge)
                    ->visible(fn() => $this->record->type == CertificateTemplateType::Submission)
                    ->form([
                        Grid::make()
                            ->columns(1)
                            ->schema([
                                CheckboxList::make('submission_status')
                                    ->required()
                                    ->options(collect(array_combine(SubmissionStatus::values(), SubmissionStatus::values()))->filter(fn($value) => !in_array($value, ['Payment Declined', 'On Payment', 'Incomplete'])))
                                    ->helperText('Choose the submission status to include on the certificate')
                            ]),
                    ])
                    ->action(function ($data) {
                        $currentNumber = $this->record->getMeta('number');

                        Submission::query()
                            ->with([
                                'meta',
                                'user',
                                'authors' => fn($query) => $query->ordered(),
                            ])
                            ->whereIn('status', $data['submission_status'])
                            ->each(function (Submission $submission) use (&$currentNumber) {

                                $certificate = Certificate::query()
                                    ->firstOrCreate([
                                        'certifiable_id' => $submission->getKey(),
                                        'certifiable_type' => Submission::class,
                                    ], [
                                        'email' => $submission->user->email,
                                        'number' => $currentNumber++,
                                        'certificate_template_id' => $this->record->id,
                                    ]);

                                $certificate->setMeta('form_data', [
                                    'Submission Title' => $submission->getMeta('title'),
                                    'Authors' => $submission->authors->map(fn(Author $author) => $author->full_name)->implode(', '),
                                    'Track' => $submission->track->title,
                                ]);
                            });
                    }),
                Action::make('sync_submission_data')
                    ->label("Sync Submissions Data")
                    ->icon('heroicon-o-arrow-path')
                    ->modalWidth(MaxWidth::ExtraLarge)
                    ->visible(fn() => $this->record->type == CertificateTemplateType::Submission)
                    ->requiresConfirmation()
                    ->action(function (Action $action) {
                        $certificates = Certificate::query()
                            ->with(['meta', 'template', 'certifiable'])
                            ->where('certificate_template_id', $this->record->id)
                            ->lazy();

                        foreach ($certificates as $certificate) {
                            $submission = $certificate->certifiable;

                            if (!$submission instanceof Submission) continue;

                            $submission->load(['meta', 'authors' => fn($query) => $query->ordered(), 'track']);

                            $certificate->setMeta('form_data', [
                                'Submission Title' => $submission->getMeta('title'),
                                'Authors' => $submission->authors->map(fn(Author $author) => $author->full_name)->implode(', '),
                                'Track' => $submission->track->title,
                            ]);
                        }

                        $action->successNotificationTitle('Sync Submission Data Complete.');
                        $action->sendSuccessNotification();
                    }),
                Action::make('settings')
                    ->icon('heroicon-o-cog')
                    ->color('success')
                    ->modalWidth(MaxWidth::ExtraLarge)
                    ->fillForm([
                        'name' => $this->record->name,
                        'meta' => $this->record->getAllMeta()->toArray(),
                    ])
                    ->form(fn(Form $form) => $form->schema([
                        TextInput::make('name'),
                        TextInput::make('meta.number')
                            ->label('Certificate Counter Number (Next)')
                            ->integer(),
                        Repeater::make('meta.fields')
                            ->label('Fields (Input Data)')
                            ->simple(TextInput::make('field')->required())
                    ]))
                    ->action(function (Action $action, array $data) {
                        try {
                            DB::beginTransaction();

                            $this->record->update($data);

                            if (data_get($data, 'meta')) {
                                $this->record->setManyMeta(data_get($data, 'meta'));
                            }

                            DB::commit();
                        } catch (\Throwable $th) {
                            DB::rollBack();

                            throw $th;
                        }

                        $action->sendSuccessNotification();
                    }),
            ])
                ->button()
                ->color('gray'),

        ];
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getEloquentQuery(): Builder
    {
        return Certificate::query()
            ->with(['meta', 'template'])
            ->where('certificate_template_id', $this->record->id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('20s')
            ->query($this->getEloquentQuery())
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')
                    ->sortable()
                    ->extraCellAttributes([
                        'style' => 'width: 1px',
                    ])
                    ->searchable(),
                TextColumn::make('email')
                    ->grow(false)
                    ->toggleable()
                    ->searchable(),
                ...collect($this->record->getMeta('fields'))
                    ->filter()
                    ->map(
                        fn($field, $key) => TextColumn::make($field)
                            ->label($field)
                            ->grow(false)
                            ->wrap()
                            ->toggleable()
                            ->state(fn($record) => data_get($record->getMeta('form_data'), $field))
                            ->when($key == 0, fn($column) => $column->searchable(query: function (Builder $query, string $search): Builder {
                                return $query->whereMeta('form_data', 'like', "%{$search}%");
                            }))
                    ),
                IconColumn::make('email_sent')
                    ->state(fn($record) => filter_var($record->getMeta('email_sent'), FILTER_VALIDATE_BOOLEAN))
                    ->boolean(),
            ])
            ->actions([
                TableAction::make('download')
                    ->label('Download Certificate')
                    ->outlined()
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn(Certificate $record) => $record->hasMedia('document'))
                    ->action(fn(Certificate $record) => $record->getFirstMedia('document')),
                TableActionGroup::make([
                    TableAction::make('generate')
                        ->label(fn(Certificate $record) => $record->hasMedia('document') ? 'Regenerate Certificate' : 'Generate Certificate')
                        ->icon('heroicon-o-document-text')
                        ->form([
                            Checkbox::make('download')
                                ->label('Download after success generate certificate.')
                        ])
                        ->action(function (Certificate $record, array $data) {
                            try {
                                $media = CertificateFacade::generateDocumentForCertificate($record);

                                Notification::make()
                                    ->title('Certificate generated successfully')
                                    ->success()
                                    ->send();

                                if ($data['download']) {
                                    return $media;
                                }

                                return;
                            } catch (RequestException $e) {
                                Notification::make()
                                    ->title($e->response->json('message') ?? $e->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            } catch (\Throwable $th) {
                                Notification::make()
                                    ->title($th->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }
                        })
                        ->requiresConfirmation(),
                    TableAction::make('send_email_notification')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        ->action(function (Certificate $record, TableAction $action) {
                            CertificateFacade::sendEmail($record);

                            $action->successNotificationTitle('Sending Mail Notification.');
                            $action->sendSuccessNotification();
                        }),
                    TableAction::make('sync_data')
                        ->visible(fn(Certificate $record) => $record->template->type == CertificateTemplateType::Submission)
                        ->requiresConfirmation()
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Certificate $record, TableAction $action) {
                            $certifiable = $record->certifiable;

                            if (!$certifiable) {
                                $action->failureNotificationTitle('Failed to sync data, related data not found.');
                                $action->failure();
                                return;
                            }

                            $certifiable->load(['meta', 'authors' => fn($query) => $query->ordered(), 'track']);

                            $record->setMeta('form_data', [
                                'Submission Title' => $certifiable->getMeta('title'),
                                'Authors' => $certifiable->authors->map(fn(Author $author) => $author->full_name)->implode(', '),
                                'Track' => $certifiable->track->title,
                            ]);
                        }),
                    EditAction::make()
                        ->mutateRecordDataUsing(function ($record, $data) {
                            $data['meta'] = $record->getAllMeta()->toArray();

                            return $data;
                        })
                        ->form(fn(Form $form) => $this->form($form))
                        ->modalWidth(MaxWidth::ExtraLarge)
                        ->using(function (array $data, Certificate $record) {
                            $record->update($data);

                            if (data_get($data, 'meta')) {
                                $record->setManyMeta(data_get($data, 'meta'));
                            }
                        }),
                    DeleteAction::make(),
                ]),

            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('number'),
                TextInput::make('email'),
                ...collect($this->record->getMeta('fields'))->map(fn($field) => TextInput::make('meta.form_data.' . $field)->label($field))->toArray(),
            ]);
    }
}

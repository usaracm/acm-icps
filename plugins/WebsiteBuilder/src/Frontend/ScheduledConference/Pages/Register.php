<?php

namespace WebsiteBuilder\Frontend\ScheduledConference\Pages;

use App\Actions\User\UserCreateAction;
use App\Frontend\ScheduledConference\Pages\PrivacyStatement;
use App\Frontend\ScheduledConference\Pages\Register as WebsiteRegister;
use App\Models\Enums\UserRole;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;
use Squire\Models\Country;

class Register extends WebsiteRegister implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions, InteractsWithFormActions;

    protected static string $view = 'WebsiteBuilder::frontend.register';
    protected static string $layout = 'filament-panels::components.layout.simple';

    public function getMaxWidth(): MaxWidth|string|null
    {
        return MaxWidth::FourExtraLarge;
    }

    public static function getLayout(): string
    {
        return static::$layout;
    }

    protected function getLayoutData(): array
    {
        return [
            'maxWidth' => $this->getMaxWidth(),
        ];
    }

    /**
     * @return array<string>
     */
    public function getRenderHookScopes(): array
    {
        return [static::class];
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return true;
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        TextInput::make('given_name')
                            ->label(__('general.given_name'))
                            ->required()
                            ->autofocus()
                            ->columnSpan(1),
                        TextInput::make('family_name')
                            ->label(__('general.family_name'))
                            ->columnSpan(1),
                        TextInput::make('public_name')
                            ->label(__('general.public_name'))
                            ->helperText(__('general.public_name_helper'))
                            ->columnSpanFull(),
                        TextInput::make('affiliation')
                            ->label(__('general.affiliation'))
                            ->columnSpan(1),
                        Select::make('country')
                            ->label(__('general.country'))
                            ->options(fn() => Country::all()->pluck('name', 'id'))
                            ->searchable()
                            ->columnSpan(1),
                        TextInput::make('phone')
                            ->label(__('general.phone'))
                            ->tel()
                            ->helperText(__('general.phone_format_international'))
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label(__('general.email'))
                            ->email()
                            ->required()
                            ->rules(['indisposable'])
                            ->unique('users', 'email')
                            ->columnSpanFull(),
                        TextInput::make('password')
                            ->label(__('general.password'))
                            ->password()
                            ->required()
                            ->minLength(12)
                            ->confirmed()
                            ->columnSpan(1),
                        TextInput::make('password_confirmation')
                            ->label(__('general.password_confirmation'))
                            ->password()
                            ->required()
                            ->columnSpan(1),
                        CheckboxList::make('selfAssignRoles')
                            ->label(__('general.register_as'))
                            ->options(fn() => array_combine(
                                UserRole::getAllowedSelfAssignRoleNames(),
                                UserRole::getAllowedSelfAssignRoleNames()
                            ))
                            ->required()
                            ->columnSpanFull(),
                        Checkbox::make('privacy_statement_agree')
                            ->label(fn() => new HtmlString(__('general.privacy_statement_agree', [
                                'url' => route(PrivacyStatement::getRouteName())
                            ])))
                            ->accepted()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ),
        ];
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->registerAction(),
            $this->loginAction(),
        ];
    }

    protected function registerAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('general.register'))
            ->submit('register');
    }

    public function areFormActionsSticky(): bool
    {
        return false;
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraBodyAttributes(): array
    {
        return [];
    }

    public function loginAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('general.login'))
            ->url(app()->getLoginUrl());
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    public function register()
    {
        if (!app()->getCurrentScheduledConference()->getMeta('allow_registration')) {
            abort(403);
        }

        try {
            $this->rateLimit(5, 300);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->validate($this->rules());
        $allowedRoles = array_values(UserRole::getAllowedSelfAssignRoleNames());

        // Filter only allowed roles to register
        $selfAssignRoles = collect($data['selfAssignRoles'])
            ->filter(fn($role) => in_array($role, $allowedRoles))
            ->toArray();

        try {
            DB::beginTransaction();
            $user = UserCreateAction::run([
                ...Arr::only($data, ['given_name', 'family_name', 'email', 'password']),
                'meta' => Arr::only($data, ['affiliation', 'country', 'phone', 'public_name']),
            ]);

            if (app()->getCurrentConference()) {
                $user->assignRole($selfAssignRoles);
            } else {
                foreach ($selfAssignRoles as $conferenceId => $roles) {
                    // get keys of roles where value is true
                    $roles = array_keys(array_filter($roles));
                    $user->assignRole($roles);
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }


        if (config('app.must_verify_email')) {
            $user->sendEmailVerificationNotification();
        }

        Filament::auth()->login($user);

        session()->regenerate();

        $this->redirect(route('filament.scheduledConference.pages.profile'));

        $this->registerComplete = true;
    }
}

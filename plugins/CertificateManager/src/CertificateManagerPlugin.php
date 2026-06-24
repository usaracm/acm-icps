<?php

namespace CertificateManager;

use App\Classes\Plugin;
use App\Models\Conference;
use App\Models\Enums\UserRole;
use App\Models\ScheduledConference;
use App\Models\Scopes\ScheduledConferenceScope;
use App\Models\User;
use CertificateManager\Facades\CertificateFacade;
use CertificateManager\Managers\CertificateApiManager;
use CertificateManager\Models\Certificate;
use CertificateManager\Models\CertificateTemplate;
use CertificateManager\Pages\CertificateTemplateDetailPage;
use CertificateManager\Pages\CertificateManagePage;
use CertificateManager\Pages\CertificatePage;
use Filament\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Rahmanramsi\LivewirePageGroup\PageGroup;

class CertificateManagerPlugin extends Plugin
{
    public string $id = '01k2nrr25c6tvxraq1989mjdy9';

    public function boot()
    {
        if (!app()->getCurrentScheduledConference()) return;

        app()->scoped(CertificateApiManager::class, function (): CertificateApiManager {
            return new CertificateApiManager($this);
        });

        CertificateTemplate::addGlobalScope(new ScheduledConferenceScope);
    }

    public function firstSetup()
    {
        $this->migrate();

        CertificateFacade::check();

        $this->updateSetting('first_setup3', true);
    }

    public function isAlreadySetup()
    {
        return $this->getSetting('first_setup3');
    }

    public function migrate(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if (!$this->isAlreadySetup()) {
            $migration = $this->getMigration();
            $migration->up();
        }
    }

    public function getMigration(): CertificateTableMigration
    {
        return new CertificateTableMigration;
    }

    public function onPanel(Panel $panel): void
    {
        if ($panel->getId() !== 'scheduledConference') return;

        $pages =  [
            CertificateManagePage::class,
            CertificateTemplateDetailPage::class,
        ];

        if ($this->isAlreadySetup()) {
            $pages[] = CertificatePage::class;
        }


        $panel->pages($pages)->navigationGroups([
            'Settings',
            'Conference',
            'Certificate Manager',
        ]);

    }

    public function getPluginPage(): ?string
    {
        if (!$this->isUserAllowedToAccessPlugin(auth()->user())) {
            return false;
        }

        try {
            return CertificateManagePage::getUrl();
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function isUserAllowedToAccessPlugin(User $user): bool
    {
        return $user->hasRole([UserRole::Admin, UserRole::ConferenceManager, UserRole::ScheduledConferenceEditor]);
    }

    public function formatNumber($number)
    {
        return str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function isExpired(): bool
    {
        $expiredAt = $this->expiredAt();

        if (!$expiredAt) {
            return true;
        }

        return $expiredAt->lessThanOrEqualTo(now());
    }

    public function expiredAt()
    {
        return $this->getSetting('expired_at') ? Date::parse($this->getSetting('expired_at')) : null;
    }

    public function onFrontend(PageGroup $frontend): void
    {
        if ($frontend->getId() !== 'scheduledConference') return;
        Route::domain($frontend->getDomain())
            ->prefix($frontend->getPath())
            ->group(function () use ($frontend) {
                Route::post('api/certificate-manager/callbackGenerateCertificate', function (Request $request) {
                    $validated = $request->validate([
                        'id' => ['required', 'string', 'exists:certificates,id'],
                        'file' => ['required', 'file', 'mimes:pdf'],
                        'params' => ['json']
                    ]);

                    $certificate = Certificate::findOrFail($validated['id']);
                    $certificate
                        ->addMediaCollection('document')
                        ->singleFile();

                    $certificate
                        ->addMediaFromRequest('file')
                        ->toMediaCollection('document', 'private-files');

                    if(array_key_exists('params', $validated)){
                        $params = json_decode($validated['params'], JSON_OBJECT_AS_ARRAY);
    
                        if($params['email'] ?? false){
                            CertificateFacade::sendEmail($certificate);
                        }
                    }
                    return response(status: 201);
                })->name('certificate-manager.callbackGenerateCertificate');
            });

    }
}

<?php

namespace WebsiteBuilder;

use App\Classes\Plugin;
use App\Classes\Theme;
use App\Facades\Hook;
use App\Managers\HookManager;
use App\Models\Enums\UserRole;
use App\Models\User;
use Filament\Panel;
use Illuminate\Support\Facades\Http;
use Rahmanramsi\LivewirePageGroup\PageGroup;
use WebsiteBuilder\Frontend\ScheduledConference\Pages\Login;
use WebsiteBuilder\Frontend\ScheduledConference\Pages\Register;
use WebsiteBuilder\Frontend\ScheduledConference\Pages\ShowSitePage;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteWidget;
use App\Models\Scopes\ScheduledConferenceScope;
use WebsiteBuilder\Pages\ComponentManagerPage;
use WebsiteBuilder\Pages\ContentBuilderAssetsPickerPage;
use WebsiteBuilder\Pages\ContentBuilderBlankPage;
use WebsiteBuilder\Pages\ContentBuilderComponentPage;
use WebsiteBuilder\Pages\ContentBuilderLayoutPage;
use WebsiteBuilder\Pages\ContentBuilderPage;
use WebsiteBuilder\Pages\ContentBuilderSettingsFormPage;
use WebsiteBuilder\Pages\CreateContentBuilderPage;
use WebsiteBuilder\Pages\OnBoardingPage;
use WebsiteBuilder\Pages\PreviewPage;
use WebsiteBuilder\Pages\PreviewRevisionPage;
use WebsiteBuilder\Pages\RevisionPage;
use WebsiteBuilder\Pages\SiteManagerPage;

class WebsiteBuilderPlugin extends Theme
{
    public function boot()
    {
        if (!app()->getCurrentScheduledConference())
            return;

        Website::addGlobalScope(new ScheduledConferenceScope);
        WebsiteWidget::addGlobalScope(new ScheduledConferenceScope);

        $this->enablePublicAsset();

        if (app()->isOnScheduledConference()) {
            $this->registerHookToOverwriteScheduledConference();
        }
    }

    public function firstSetup()
    {
        $this->migrate();
        $this->updateSetting('first_setup2', true);
    }

    public function isAlreadySetup()
    {
        if (!$this->getSetting('onboarding_completed', false)) {
            redirect()->to(OnBoardingPage::getUrl());
            return false;
        }
        return $this->getSetting('first_setup2');

    }

    public function migrate(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if (!$this->isAlreadySetup()) {
            $migration = $this->getMigration();
            $seeders = $this->getSeeder();
            $migration->up();
            $seeders->run(app()->getCurrentScheduledConference()->id);
        }
    }

    public function getMigration(): WebsiteBuilderTableMigration
    {
        return new WebsiteBuilderTableMigration;
    }

    public function getSeeder(): WebsiteBuilderTableSeeder
    {
        return new WebsiteBuilderTableSeeder;
    }

    public function onPanel(Panel $panel): void
    {
        $panel->pages([
            SiteManagerPage::class,
            ComponentManagerPage::class,
            ContentBuilderPage::class,
            ContentBuilderSettingsFormPage::class,
            ContentBuilderAssetsPickerPage::class,
            PreviewRevisionPage::class,
            PreviewPage::class,
            RevisionPage::class,
            ContentBuilderBlankPage::class,
            ContentBuilderComponentPage::class,
            ContentBuilderLayoutPage::class,
            OnBoardingPage::class,
            CreateContentBuilderPage::class,
        ]);
    }

    public function onFrontend(PageGroup $frontend): void
    {
        if ($frontend->getId() !== 'scheduledConference' || !$this->getIsPluginActive()) {
            return;
        }

        $frontend->pages([
            ShowSitePage::class,
            Login::class,
            Register::class,
        ]);
    }

    public function getPluginPage(): ?string
    {
        if (!$this->isUserAllowedToAccessPlugin(auth()->user())) {
            return false;
        }

        try {
            return SiteManagerPage::getUrl();
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function getOverridablePageSlugs()
    {
        return [
            'home' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\Home::class) . '::render',
            'about' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\About::class) . '::render',
            'contact' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\Contact::class) . '::render',
            'announcements' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\Announcements::class) . '::render',
            'committees' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\Committees::class) . '::render',
            'editorial-team' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\EditorialTeam::class) . '::render',
            'privacy-statement' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\PrivacyStatement::class) . '::render',
            'publisher-library' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\PublisherLibrary::class) . '::render',
            'timelines' => str_replace('\\', '::', \App\Frontend\ScheduledConference\Pages\Timelines::class) . '::render',
        ];
    }

    public function registerHookToOverwriteScheduledConference()
    {
        foreach ($this->getOverridablePageSlugs() as $slug => $hook) {
            Hook::add($hook, function ($hookName, $page, &$data, &$layout, &$layoutData, &$view) use ($slug) {
                if ($this->getIsPluginActive() === false) {
                    return HookManager::CONTINUE;
                }

                if ($slug === 'home') {
                    $record = Website::where('is_default', true)
                        ->where('is_published', true)
                        ->first();
                } else {
                    $record = Website::where('slug', $slug);
                    if (!auth()->check() || !$this->isUserAllowedToAccessPlugin(auth()->user())) {
                        $record->where('is_published', true);
                    }
                    $record = $record->first();
                }

                if (!$record) {
                    return HookManager::CONTINUE;
                }

                $data = [
                    ...$data,
                    'record' => $record,
                ];

                $view = view('WebsiteBuilder::show', $data)
                    ->layout('WebsiteBuilder::layout.show', [
                        'livewire' => $page,
                        'name' => $record->name,
                        'pluginPath' => $this->getWebsiteBuilderPluginPath(),
                        'meta' => $record->getMetaAsStringHtmlTag(),
                        'header' => $this->getWebsiteHeader(),
                        'footer' => $this->getWebsiteFooter(),
                        'assetsBasePath' => $this->url('/', true, false),
                        'faviconUrl' => app()->getCurrentScheduledConference()->getFirstMediaUrl('favicon', 'favicon'),
                        'published' => $record->is_published,
                        ...$layoutData,
                    ])
                    ->title($page->getTitle());

                return HookManager::ABORT;
            }, HookManager::SEQUENCE_LAST);
        }
    }

    public function getWebsiteFooter()
    {
        return $this->getSetting('website_footer', []);
    }

    public function getWebsiteHeader()
    {
        return $this->getSetting('website_header', []);
    }

    public function getIsPluginActive()
    {
        // return $this->getSetting('is_plugin_active', false);
        return app()->getCurrentScheduledConference()?->getMeta('theme') == 'WebsiteBuilder';
    }

    public function isUserAllowedToAccessPlugin(User $user): bool
    {
        return $user->hasRole([UserRole::Admin, UserRole::ConferenceManager, UserRole::ScheduledConferenceEditor]);
    }

    public function getWebsiteBuilderPluginPath()
    {
        return preg_replace('#/api/?$#', '/services/website-builder/plugins', app()->getApiUrl());
    }

    public function getWidgetCollections($id = null)
    {
        if ($id !== null) {
            return Http::acceptJson()->get(app()->getApiUrl('service/website-widget-collections/' . $id));
        }
        return Http::acceptJson()->get(app()->getApiUrl('service/website-widget-collections'));
    }
}

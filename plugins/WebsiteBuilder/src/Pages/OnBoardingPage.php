<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use WebsiteBuilder\Models\Website;

class OnBoardingPage extends Page
{
    protected static ?string $title = '';

    protected static string $view = 'WebsiteBuilder::on-boarding';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $plugin = Plugin::getPlugin('WebsiteBuilder');

        if ($plugin->getSetting('onboarding_completed', false)) {
            redirect()->to(SiteManagerPage::getUrl());
            return;
        }

        if (Website::where('slug', 'home')->exists()) {
            $plugin->updateSetting('onboarding_completed', true);
            redirect()->to(SiteManagerPage::getUrl());
            return;
        }
    }

    protected function getViewData(): array
    {
        $response = Http::acceptJson()->get(app()->getApiUrl('service/website-templates'));
        return [
            'templates' => $response->json('templates', []),
        ];
    }

    #[On('completeOnboarding')]
    public function completeOnboarding($templateId)
    {
        $plugin = Plugin::getPlugin('WebsiteBuilder');
        $plugin->updateSetting('onboarding_completed', true);

        $response = Http::acceptJson()->get(app()->getApiUrl('service/website-templates/' . $templateId));
        $website = Website::create([
            'scheduled_conference_id' => app()->getCurrentScheduledConference()->id,
            'slug' => "home",
            'name' => 'Home',
            'is_published' => true,
            'is_default' => true,
        ]);
        $website->setManyMeta([
            'main_css' => $response->json('main_css', ''),
            'section_css' => $response->json('section_css', ''),
            'content_html' => $response->json('html', ''),
        ]);

        // auto set theme to website builder after completing onboarding
        app()->getCurrentScheduledConference()->setMeta('theme', 'WebsiteBuilder');

        return [
            'redirect' => ContentBuilderPage::getUrl(['website' => $website->id]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('WebsiteBuilder')->isUserAllowedToAccessPlugin(auth()->user());
    }
}

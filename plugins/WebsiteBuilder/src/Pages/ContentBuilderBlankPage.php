<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteWidget;

class ContentBuilderBlankPage extends Page
{
    protected static string $view = 'WebsiteBuilder::content-builder-blank';

    protected static string $layout = 'WebsiteBuilder::layout.blank';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'content-builder-blank/{doNotShowHeaderFooter?}/{layoutShowedName?}';

    public $doNotShowHeaderFooter = false;

    public $layoutShowedName = false;

    protected function getViewData(): array
    {
        $plugin = $this->getPlugin();
        return [
            'assetsBasePath' => $plugin->url('/', true, false),
            'layoutShowedName' => $this->layoutShowedName,
        ];
    }

    protected function getLayoutData(): array
    {
        $isNotShowComponent = filter_var($this->doNotShowHeaderFooter, FILTER_VALIDATE_BOOLEAN);
        // $showSample = filter_var($this->ShowSample, FILTER_VALIDATE_BOOLEAN);
        $plugin = $this->getPlugin();
        $data = [
            'assetsBasePath' => $plugin->url('/', true, false),
            'getAsset' => fn($path) => $plugin->asset($path),
            'layoutShowedName' => $this->layoutShowedName,
            'pluginPath' => $plugin->getWebsiteBuilderPluginPath(),
        ];
        $headerComponent = $plugin->getWebsiteHeader();
        $footerComponent = $plugin->getWebsiteFooter();
        if ($headerComponent && !$isNotShowComponent) {
            // $data['header'] = str_replace('is-container', '', ($headerComponent->content_html ?? ''));
            $data['header'] = $headerComponent['content_html'] ?? '';
            $data['headerCss'] = ($headerComponent['main_css'] ?? '') . ($headerComponent['section_css'] ?? '');
        }
        if ($footerComponent && !$isNotShowComponent) {
            // $data['footer'] = str_replace('is-container', '', ($footerComponent->content_html ?? ''));
            $data['footer'] = $footerComponent['content_html'] ?? '';
            $data['footerCss'] = ($footerComponent['main_css'] ?? '') . ($footerComponent['section_css'] ?? '');
        }
        if (!$isNotShowComponent) {
            $data['headerUrl'] = ContentBuilderLayoutPage::getUrl(['websiteLayout' => 'header']);
            $data['footerUrl'] = ContentBuilderLayoutPage::getUrl(['websiteLayout' => 'footer']);
        }

        return $data;
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

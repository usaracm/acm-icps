<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Pages\Page;
use WebsiteBuilder\Models\Website;

class PreviewPage extends Page
{
    protected static string $view = 'WebsiteBuilder::show';

    protected static string $layout = 'WebsiteBuilder::layout.show';

    protected static ?string $slug = 'preview/{website}';

    protected static bool $shouldRegisterNavigation = false;

    public Website $website;

    protected function getViewData(): array
    {
        return [
            'record' => $this->website,
        ];
    }

    protected function getLayoutData(): array
    {
        $plugin = $this->getPlugin();
        return [
            'assetsBasePath' => $plugin->url('/', true, false),
            'pluginPath' => $plugin->getWebsiteBuilderPluginPath(),
            'meta' => $this->website->getMetaAsStringHtmlTag(),
            'description' => $this->website->getMeta('description') ?? null,
            'name' => $this->website->name,
            'footer' => $plugin->getWebsiteFooter(),
            'header' => $plugin->getWebsiteHeader(),
            'faviconUrl' => app()->getCurrentScheduledConference()->getFirstMediaUrl('favicon', 'favicon'),
            'published' => $this->website->is_published,
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

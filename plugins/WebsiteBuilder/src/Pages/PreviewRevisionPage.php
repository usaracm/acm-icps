<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Pages\Page;
use WebsiteBuilder\Models\WebsiteRevision;

class PreviewRevisionPage extends Page
{
    protected static string $view = 'WebsiteBuilder::show';

    protected static string $layout = 'WebsiteBuilder::layout.show';

    protected static ?string $slug = 'preview-revision/{websiteRevision}';

    protected static bool $shouldRegisterNavigation = false;

    public WebsiteRevision $websiteRevision;

    protected function getViewData(): array
    {
        return [
            'record' => $this->websiteRevision,
        ];
    }

    protected function getLayoutData(): array
    {
        $plugin = $this->getPlugin();
        return [
            'assetsBasePath' => $plugin->url('/', true, false),
            'pluginPath' => $plugin->getWebsiteBuilderPluginPath(),
            'meta' => $this->websiteRevision->getMetaAsStringHtmlTag(),
            'description' => $this->websiteRevision->getMeta('description') ?? null,
            'name' => $this->websiteRevision->name,
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

<?php

namespace WebsiteBuilder\Frontend\ScheduledConference\Pages;

use App\Facades\Plugin;
use App\Frontend\Website\Pages\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Rahmanramsi\LivewirePageGroup\PageGroup;
use WebsiteBuilder\Models\Website;

class ShowSitePage extends Page
{
    protected static string $view = 'WebsiteBuilder::show';

    protected static string $layout = 'WebsiteBuilder::layout.show';

    protected static ?string $slug = 'sites';

    public Website $website;

    public function mount(Request $request)
    {
        $website = $request->route('website');

        if ((!$website->is_published && (!auth()->check() || !$this->getPlugin()->isUserAllowedToAccessPlugin(auth()->user()))) || $website->is_default) {
            abort(404);
        }
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->website,
        ];
    }

    public static function getLayout(): string
    {
        return static::$layout;
    }

    protected function getLayoutData(): array
    {
        $plugin = $this->getPlugin();
        app()->getCurrentScheduledConference();
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

    public static function routes(PageGroup $pageGroup): void
    {
        $slug = static::getSlug();

        Route::get('/{website:slug}', static::class)
            ->middleware(static::getRouteMiddleware($pageGroup))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($pageGroup))
            ->name((string) str($slug)->replace('/', '.'));
    }
}

<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteWidget;

class ContentBuilderComponentPage extends Page
{
    protected static string $layout = 'WebsiteBuilder::layout.editor';

    protected static ?string $title = '';

    protected static ?string $navigationIcon = "heroicon-o-code-bracket";

    protected static string $view = 'WebsiteBuilder::content-builder-component';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'content-builder-component/{WebsiteWidget}';

    public WebsiteWidget $WebsiteWidget;

    protected function getViewData(): array
    {
        $plugin = $this->getPlugin();

        $response = $plugin->getWidgetCollections();
        $templates = [];
        if (isset($response->json()['templates'])) {
            $templates = array_merge($templates, array_map(function ($category) {
                return [
                    'id' => $category['id'],
                    'sections' => route('filament.scheduledConference.pages.content-builder-template-leconfe', ['template' => $category['id']]),
                    'is_paid' => $category['is_paid'],
                    'name' => $category['name'] ?? 'Unnamed',
                    'section_count' => $category['section_count'] ?? 0,
                    'section_keywords' => $category['section_keywords'] ?? [],
                ];
            }, $response->json()['templates']));
        }

        return [
            'record' => $this->WebsiteWidget,
            'assetsBasePath' => $plugin->url('/', true, false),
            'backUrl' => $plugin->getPluginPage(),
            'templates' => $templates,
        ];
    }

    protected function getLayoutData(): array
    {
        $plugin = $this->getPlugin();
        return [
            'contentBoxCss' => $plugin->asset('contentbox/contentbox.css'),
            'contentBuilderCss' => $plugin->asset('contentbuilder/contentbuilder.css'),
            'contentBoxJs' => $plugin->asset('contentbox/contentbox.min.js'),
            'assetsBasePath' => $plugin->url('/', true, false),
            'name' => $this->WebsiteWidget->name,
        ];
    }

    protected function getPlugin()
    {
        return Plugin::getPlugin('WebsiteBuilder');
    }

    public function save($data)
    {
        $this->skipRender();
        if (!is_array($data)) {
            return ['error' => 'Invalid payload.'];
        }

        $html = (string) ($data['content'] ?? $data['html'] ?? '');
        $mainCss = (string) ($data['mainCss'] ?? '');
        $sectionCss = (string) ($data['sectionCss'] ?? '');

        $isUpdateSuccess = $this->WebsiteWidget->setManyMeta([
            'content_html' => $html,
            'main_css' => $mainCss,
            'section_css' => $sectionCss,
        ]) || false;

        return ['success' => $isUpdateSuccess];
    }

    public function uploadFile($base64Data)
    {
        $this->skipRender();
        if (!$base64Data) {
            return ['error' => 'No file uploaded.'];
        }
        try {
            // Ambil mime dari base64 header
            preg_match('/data:(.*?);base64/', $base64Data, $matches);
            $mime = $matches[1] ?? null;

            // Mapping mime → extension
            $extension = match ($mime) {
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'audio/mpeg' => 'mp3',
                'video/mp4' => 'mp4',
                default => 'error',
            };

            if ($extension === 'error') {
                return [
                    'error' => 'Unsupported file type.',
                    'success' => false,
                ];
            }

            $media = app()->getCurrentScheduledConference()
                ->addMediaFromBase64($base64Data)
                ->usingFileName(Str::uuid() . '.' . $extension)
                ->toMediaCollection('website_files');

            return [
                'url' => $media->getUrl(),
                'success' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'success' => false,
            ];
        }
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('WebsiteBuilder')->isUserAllowedToAccessPlugin(auth()->user());
    }
}

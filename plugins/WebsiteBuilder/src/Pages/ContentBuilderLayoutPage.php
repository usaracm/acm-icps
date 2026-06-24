<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Pages\Page;

class ContentBuilderLayoutPage extends Page
{
    protected static string $layout = 'WebsiteBuilder::layout.editor';

    protected static ?string $title = '';

    protected static ?string $navigationIcon = "heroicon-o-code-bracket";

    protected static string $view = 'WebsiteBuilder::content-builder-layout';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'content-builder-layout/{websiteLayout}';

    public $websiteLayout;
    public $name;

    public function mount($websiteLayout)
    {
        $plugin = $this->getPlugin();
        $this->name = $websiteLayout;

        $default = [
            'content_html' => '',
            'main_css' => '',
            'section_css' => '',
        ];

        if ($websiteLayout === 'header')
            $this->websiteLayout = $plugin->getSetting('website_header', []);
        else if ($websiteLayout === 'footer') {
            $this->websiteLayout = $plugin->getSetting('website_footer', []);
        } else {
            abort(404);
        }

        if (!$this->websiteLayout) {
            $this->websiteLayout = $default;
        }
    }

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
            'record' => $this->websiteLayout,
            'assetsBasePath' => $plugin->url('/', true, false),
            'backUrl' => $plugin->getPluginPage(),
            'templates' => $templates
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
            'name' => $this->name,
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
            return ['success' => false, 'error' => 'Invalid payload.'];
        }

        $html = (string) ($data['content'] ?? $data['html'] ?? '');
        $mainCss = (string) ($data['mainCss'] ?? '');
        $sectionCss = (string) ($data['sectionCss'] ?? '');

        $layoutData = [
            'content_html' => $html,
            'main_css' => $mainCss,
            'section_css' => $sectionCss,
        ];

        $maxBytes = 63 * 1024; // 63kb
        if (strlen(json_encode($layoutData)) > $maxBytes) {
            return ['success' => false, 'error' => 'Layout terlalu besar. Ukuran melebihi 63KB.'];
        }

        $plugin = $this->getPlugin();
        $settingKey = ($this->name === 'header') ? 'website_header' : 'website_footer';
        $isUpdateSuccess = (bool) $plugin->updateSetting($settingKey, $layoutData);

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

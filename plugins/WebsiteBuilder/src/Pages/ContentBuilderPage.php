<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteWidget;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class ContentBuilderPage extends Page
{
    protected static string $layout = 'WebsiteBuilder::layout.editor';

    protected static ?string $title = '';

    protected static ?string $navigationIcon = "heroicon-o-code-bracket";

    protected static string $view = 'WebsiteBuilder::content-builder';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'content-builder/{website}';

    public Website $website;

    protected function getViewData(): array
    {
        $plugin = $this->getPlugin();
        $footer = $plugin->getWebsiteFooter();
        $header = $plugin->getWebsiteHeader();

        $response = $plugin->getWidgetCollections();
        $components = WebsiteWidget::where('scheduled_conference_id', app()->getCurrentScheduledConference()->id)
            ->get()
            ->load('meta')
            ->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'thumbnail' => 'https://dummyimage.com/600x300/000/fff&text=' . $component->name,
                    'is_paid' => false,
                    'main_css' => $component->getMeta('main_css', ''),
                    'content_css' => $component->getMeta('section_css', ''),
                    'html' => $component->getMeta('content_html', ''),
                ];
            })->toArray();

        $templates = [
            [
                'id' => 0,
                'sections' => $components,
                'is_paid' => false,
                'name' => 'My Widgets',
                'section_count' => count($components),
                'section_keywords' => collect($components)
                    ->map(fn($item) => ['id' => $item['id'], 'name' => $item['name']])
                    ->unique('id')
                    ->values()
                    ->toArray(),
            ]
        ];
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
            'record' => $this->website,
            'footer' => [
                'main_css' => $footer['main_css'] ?? '',
                'section_css' => $footer['section_css'] ?? '',
            ],
            'header' => [
                'main_css' => $header['main_css'] ?? '',
                'section_css' => $header['section_css'] ?? '',
            ],
            'templates' => $templates,
            'assetsBasePath' => $plugin->url('/', true, false),
            'backUrl' => $plugin->getPluginPage(),
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
            'name' => $this->website->name,
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
            Notification::make()
                ->title('Something was wrong. Please try again.')
                ->danger()
                ->send();
            return ['error' => 'Invalid payload.'];
        }

        $slug = $data['slug'] ?? null;
        if (!is_string($slug) || $slug === '') {
            return ['error' => 'Missing slug.'];
        }

        $html = (string) ($data['content'] ?? $data['html'] ?? '');
        $mainCss = (string) ($data['mainCss'] ?? '');
        $sectionCss = (string) ($data['sectionCss'] ?? '');

        // Simpan state sebelum update untuk revision
        $previousState = [
            'name' => $this->website->name,
            'slug' => $this->website->slug,
            'content_html' => $this->website->getMeta('content_html', ''),
        ];

        $newMeta = array_merge($this->website->getAllMeta()->toArray() ?? [], [
            'main_css' => $mainCss,
            'section_css' => $sectionCss,
            'content_html' => $html,
        ]);

        // Skip jika tidak ada perubahan content
        $hasChanges = $this->website->getMeta('content_html', '') !== $html;

        if (!$hasChanges) {
            return ['success' => true];
        }

        try {
            $isUpdateSuccess = DB::transaction(function () use ($html, $newMeta, $previousState) {
                $this->website->setManyMeta($newMeta);

                // Cek revision terakhir dari user yang sama dalam 2 jam
                $latestRevision = $this->website->revisions()
                    ->where('user_id', auth()->id())
                    ->where('created_at', '>=', now()->subHours(2))
                    ->latest()
                    ->first();

                if ($latestRevision) {
                    // Update revision yang sudah ada (tidak perlu create baru)
                    $latestRevision->update($previousState);
                } else {
                    // Buat revision baru dengan state sebelumnya
                    $this->website->revisions()->create([
                        'website_id' => $this->website->id,
                        'user_id' => auth()->id(),
                        ...$previousState,
                    ]);
                }

                return true;
            });

            return ['success' => $isUpdateSuccess];
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Failed to save changes. Please try again.')
                ->danger()
                ->send();
            return ['success' => false, 'error' => 'Failed to save changes.'];
        }
    }

    public function uploadFile($base64Data)
    {
        $this->skipRender();
        if (!$base64Data) {
            Notification::make()
                ->title('No file detected.')
                ->danger()
                ->send();
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
                Notification::make()
                    ->title('Failed to upload file')
                    ->body('Unsupported file type.')
                    ->danger()
                    ->send();
                return [
                    'error' => 'Failed to upload file. Unsupported file type.',
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
            Notification::make()
                ->title('Failed to upload file. Please try again.')
                ->danger()
                ->send();
            return [
                'error' => $e->getMessage(),
                'success' => false,
            ];
        }
    }

    public function togglePublish()
    {
        $this->skipRender();

        if ($this->website->is_default && $this->website->is_published) {
            Notification::make()
                ->title('Unpublish Failed')
                ->body('Page set as home cannot be unpublished.')
                ->danger()
                ->send();
            return ['success' => false, 'is_published' => $this->website->is_published];
        }

        $this->website->is_published = !$this->website->is_published;
        $this->website->save();

        if ($this->website->is_published) {
            Notification::make()
                ->title('Website published successfully.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Website unpublished successfully.')
                ->success()
                ->send();
        }

        return ['success' => true, 'is_published' => $this->website->is_published];
    }

    public static function routes(Panel $panel): void
    {
        Route::get(static::getRoutePath(), static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getRelativeRouteName());

        Route::get('/{template}/content-builder-template-leconfe', [static::class, 'templateLeconfe'])
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name('content-builder-template-leconfe');
    }

    public function templateLeconfe(Request $request)
    {
        $plugin = $this->getPlugin();
        $template = $request->route('template');

        $templateResponse = $plugin->getWidgetCollections($template);
        if (!$templateResponse->ok()) {
            $status = $templateResponse->status();
            try {
                $json = $templateResponse->json();
            } catch (\Throwable $e) {
                $json = null;
            }
            $message = $json['error'] ?? $json['message'] ?? $templateResponse->body();
            // sanitize message to keep it safe inside a JS comment
            $safeMessage = str_replace(["\r", "\n", "*/"], ['', '', ''], (string) $message);
            $comment = '// HTTP error: ' . $status . ' - ' . $safeMessage;
            return response()->json([
                'error' => $comment,
            ], $status);
        }

        if (isset($templateResponse->json()['sections'])) {
            return response()->json([
                'sections' => [
                    ...$templateResponse->json()['sections']
                ],
            ]);
        }

        return response()->json([
            'error' => '// No sections found in the template.',
        ], 404)
            ->header('Cache-Control', 'public, max-age=86400, s-maxage=86400')
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT'); // Cache for 1 day
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('WebsiteBuilder')->isUserAllowedToAccessPlugin(auth()->user());
    }
}

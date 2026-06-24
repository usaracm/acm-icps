<?php

namespace WebsiteBuilder\Pages;

use App\Facades\Plugin;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class ContentBuilderAssetsPickerPage extends Page
{
    protected static string $layout = 'WebsiteBuilder::layout.form';

    protected static ?string $navigationIcon = "heroicon-o-code-bracket";

    protected static string $view = 'WebsiteBuilder::content-builder-assets-picker';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'content-builder-assets';

    public $files = [];

    public function mount(): void
    {
        $this->refreshFiles();
    }

    public function deleteMedia(int $mediaId): void
    {
        app()->getCurrentScheduledConference()->deleteMedia($mediaId);
        $this->refreshFiles();
    }

    public function uploadFile($base64Data)
    {
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
                Notification::make()
                    ->title('Unsupported file type.')
                    ->danger()
                    ->send();
                return [
                    'error' => 'Unsupported file type.',
                    'success' => false,
                ];
            }

            $media = app()->getCurrentScheduledConference()
                ->addMediaFromBase64($base64Data)
                ->usingFileName(Str::uuid() . '.' . $extension)
                ->toMediaCollection('website_files');

            $this->refreshFiles();

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

    public function refreshFiles()
    {
        $conference = app()->getCurrentScheduledConference();
        $conference->refresh();
        $this->files = $conference->getMedia('website_files')->map(function ($media) {
            return [
                'id' => $media->id,
                'type' => $media->mime_type,
                'url' => $media->getUrl(),
            ];
        })->reverse()->toArray();
    }

    public static function canAccess(): bool
    {
        return Plugin::getPlugin('WebsiteBuilder')->isUserAllowedToAccessPlugin(auth()->user());
    }
}

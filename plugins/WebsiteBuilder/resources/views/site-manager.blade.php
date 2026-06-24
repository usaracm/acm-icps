<x-filament-panels::page>
    <div class="space-y-4">
        @if (!$isPluginActive)
            <div class="flex flex-row-reverse gap-2">
                <x-shout::shout color="warning">
                    Website Builder <b>theme</b> is <b>inactive</b>. Update it in <a style="text-decoration: underline;"
                        href="{{ route('filament.scheduledConference.pages.website-setting', ['conference' => app()->getCurrentConference()->path, 'serie' => app()->getCurrentScheduledConference()->path]) }}">Website
                        Settings</a>
                </x-shout::shout>
            </div>
        @endif

        {{ $this->table }}
    </div>

    <div x-data="{}" x-init="$wire.on('copy-to-clipboard', async text => {
        if (!navigator.clipboard) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed'; // Avoid scrolling to bottom
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        } else {
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                console.error(e);
            }
        }
    })"></div>
</x-filament-panels::page>

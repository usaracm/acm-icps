<x-filament-panels::page>
    {{ $this->table }}

    <div x-data="{}" x-init="$wire.on('redirect-iframe', (params) => {
        const { url, target = '_self' } = params[0];
        if (target === '_blank') {
            window.parent.open(url, target);
        } else {
            window.parent.location.href = url;
        }
    });
    $wire.on('refresh-iframe', () => {
        window.parent.location.reload();
    });"></div>
</x-filament-panels::page>

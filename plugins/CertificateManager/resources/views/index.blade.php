<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex flex-row-reverse">
            @if($isExpired)
                <x-shout::shout
                    color="danger"
                >
                    Plugin is expired on {{ $expiredAt?->format('d M Y') }}
                </x-shout::shout>
            @else
                <x-shout::shout
                    color="success"
                >
                    Plugin active until {{ $expiredAt?->format('d M Y') }}
                </x-shout::shout>
            @endif
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
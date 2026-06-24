@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div style="margin: 0 1.5rem;">
        {{ $slot }}
    </div>
</x-filament-panels::layout.base>

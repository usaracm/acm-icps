<x-filament-panels::page>
    <form wire:submit='submit' class="space-y-4 max-w-lg">
        {{ $this->form }}
		<div class="actions flex items-center">
			<x-filament::button type="submit" icon="iconpark-save-o" wire:target="submit">
				{{ __('general.submit') }}
			</x-filament::button>
		</div>
    </form>
</x-filament-panels::page>

<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-4 justify-start mt-6" style="margin-top: 1.5rem;">
            <x-filament::button type="submit" size="md" color="warning">
                Simpan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

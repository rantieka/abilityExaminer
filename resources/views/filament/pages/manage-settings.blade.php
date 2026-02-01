<x-filament-panels::page>
    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}
        
        <div class="flex justify-end gap-x-3">
             <x-filament::button type="submit">
                Save Changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

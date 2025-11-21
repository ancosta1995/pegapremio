<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button wire:click="save" size="lg" color="primary">
                Salvar Configurações
            </x-filament::button>
        </div>
    </div>
</x-filament::page>


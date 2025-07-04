<x-filament::page>
    {{ $this->form }}

    <div class="flex justify-end mt-4">
        <x-filament::button 
            type="button" 
            wire:click="createCustomerOrder"
            color="primary"
        >
            Create Customer Order
        </x-filament::button>
    </div>
</x-filament::page>
<x-filament::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        <div class="mt-6">
            <x-filament::button type="submit">
                Update Profile
            </x-filament::button>
        </div>
    </form>
</x-filament::page>

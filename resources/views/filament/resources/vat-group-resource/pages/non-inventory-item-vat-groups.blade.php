<x-filament::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- Table -->
        <div class="lg:col-span-2">
            <x-filament::card>
                {{ $this->table }}
            </x-filament::card>
        </div>

    </div>
</x-filament::page>

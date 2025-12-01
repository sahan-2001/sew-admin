<x-filament-widgets::widget>
    <x-filament::section class="p-0">
        <div class="w-full flex flex-wrap gap-3 mb-4">

            <x-filament::button
                tag="a"
                color="success"
                class="flex-1 min-w-[150px] justify-center"
                href="{{ route('filament.admin.resources.control-accounts.customer') }}"
            >
                <x-heroicon-o-user-group class="w-5 h-5 mr-2" />
                Customer Contol Account
            </x-filament::button>

            <x-filament::button
                tag="a"
                color="warning"
                class="flex-1 min-w-[150px] justify-center"
                href="{{ route('filament.admin.resources.control-accounts.supplier') }}"
            >
                <x-heroicon-o-truck class="w-5 h-5 mr-2" />
                Supplier Contol Account
            </x-filament::button>

            <x-filament::button
                tag="a"
                color="info"
                class="flex-1 min-w-[150px] justify-center"
                href="{{ route('filament.admin.resources.control-accounts.vat') }}"
            >
                <x-heroicon-o-banknotes class="w-5 h-5 mr-2" />
                VAT Contol Account
            </x-filament::button>

            <x-filament::button
                tag="a"
                color="success"
                class="flex-1 min-w-[150px] justify-center"
                href="{{ route('filament.admin.resources.control-accounts.cash_bank') }}"
            >
                <x-heroicon-o-currency-dollar class="w-5 h-5 mr-2" />
                Cash & Bank Contol Account
            </x-filament::button>

            <x-filament::button
                tag="a"
                color="primary"
                class="flex-1 min-w-[150px] justify-center"
                href="{{ route('filament.admin.resources.control-accounts.fixed_assets') }}"
            >
                <x-heroicon-o-building-office class="w-5 h-5 mr-2" />
                Fixed Assets Contol Account
            </x-filament::button>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>

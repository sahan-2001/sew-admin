<x-filament-panels::page>
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-heroicon-o-chart-bar class="w-6 h-6 text-primary-500" />
            Income Statement
            <x-filament::badge color="gray">
                For Period Ending {{ now()->format('F j, Y') }}
            </x-filament::badge>
        </div>
    </x-slot>

    @php
        // Fetch all COA records marked as "income_statement"
        $accounts = \App\Models\ChartOfAccount::where('statement_type', 'income_statement')
            ->orderBy('account_type')
            ->orderBy('code')
            ->get();

        $grouped = $accounts->groupBy('account_type');

        // Totals
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($accounts as $acc) {
            $type = strtolower($acc->account_type);

            if (str_contains($type, 'income') || str_contains($type, 'revenue')) {
                $totalIncome += $acc->balance_vat;
            }

            if (str_contains($type, 'expense') || str_contains($type, 'cost')) {
                $totalExpense += $acc->balance_vat;
            }
        }

        $netProfit = $totalIncome - $totalExpense;
    @endphp

    {{-- ============================== --}}
    {{--           SUMMARY CARDS        --}}
    {{-- ============================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        {{-- Total Income --}}
        <div class="bg-white dark:bg-gray-800 border shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-primary-300">Total Income</h3>
            <p class="text-2xl font-bold mt-2 text-green-600">
                LKR {{ number_format($totalIncome, 2) }}
            </p>
        </div>

        {{-- Total Expense --}}
        <div class="bg-white dark:bg-gray-800 border shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-primary-300">Total Expenses</h3>
            <p class="text-2xl font-bold mt-2 text-red-600">
                LKR {{ number_format($totalExpense, 2) }}
            </p>
        </div>

        {{-- Net Profit --}}
        <div class="bg-white dark:bg-gray-800 border shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-primary-300">Net Profit</h3>
            <p class="text-2xl font-bold mt-2 {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                LKR {{ number_format($netProfit, 2) }}
            </p>
        </div>

    </div>



    {{-- ============================== --}}
    {{--       DETAILED ACCOUNT TABLES   --}}
    {{-- ============================== --}}
    <div class="space-y-10">

        @foreach ($grouped as $type => $items)

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">

                <h3 class="text-xl font-semibold mb-4 text-primary-700 dark:text-primary-300 uppercase">
                    {{ $type }}
                </h3>

                <table class="w-full border border-gray-300 dark:border-gray-700 text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-900 text-left">
                            <th class="px-3 py-2 border dark:border-gray-700 font-semibold">Code</th>
                            <th class="px-3 py-2 border dark:border-gray-700 font-semibold">Account Name</th>
                            <th class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">Debit VAT (LKR)</th>
                            <th class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">Credit VAT (LKR)</th>
                            <th class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">Balance VAT (LKR)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $totalDebitVat = 0;
                            $totalCreditVat = 0;
                            $totalBalanceVat = 0;
                        @endphp

                        @foreach ($items as $acc)
                            @php
                                $totalDebitVat += $acc->debit_total_vat;
                                $totalCreditVat += $acc->credit_total_vat;
                                $totalBalanceVat += $acc->balance_vat;
                            @endphp

                            <tr class="dark:text-gray-200">
                                <td class="px-3 py-2 border dark:border-gray-700">{{ $acc->code }}</td>
                                <td class="px-3 py-2 border dark:border-gray-700">{{ $acc->name }}</td>

                                <td class="px-3 py-2 border dark:border-gray-700 text-right">
                                    {{ number_format($acc->debit_total_vat, 2) }}
                                </td>

                                <td class="px-3 py-2 border dark:border-gray-700 text-right">
                                    {{ number_format($acc->credit_total_vat, 2) }}
                                </td>

                                <td class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">
                                    {{ number_format($acc->balance_vat, 2) }}
                                </td>
                            </tr>
                        @endforeach

                        {{-- Totals Row --}}
                        <tr class="bg-gray-100 dark:bg-gray-900 font-bold dark:text-white">
                            <td class="px-3 py-2 border dark:border-gray-700" colspan="2">
                                TOTAL {{ strtoupper($type) }}
                            </td>
                            <td class="px-3 py-2 border dark:border-gray-700 text-right">
                                {{ number_format($totalDebitVat, 2) }}
                            </td>
                            <td class="px-3 py-2 border dark:border-gray-700 text-right">
                                {{ number_format($totalCreditVat, 2) }}
                            </td>
                            <td class="px-3 py-2 border dark:border-gray-700 text-right">
                                {{ number_format($totalBalanceVat, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>

        @endforeach

    </div>


    {{-- Bottom Summary --}}
    <div class="bg-primary-50 dark:bg-primary-900 border border-primary-300 dark:border-primary-700 text-primary-900 dark:text-primary-100 rounded-lg p-5 mt-12 shadow-sm text-center">
        <div class="text-xl font-semibold">
            Net Profit Calculation:
        </div>
        <div class="text-lg mt-2 font-bold">
            Income ({{ number_format($totalIncome, 2) }})
            −
            Expenses ({{ number_format($totalExpense, 2) }})
            =
            Net Profit ({{ number_format($netProfit, 2) }})
        </div>
    </div>

</x-filament-panels::page>

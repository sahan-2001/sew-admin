<x-filament-panels::page>
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-heroicon-o-banknotes class="w-6 h-6 text-primary-500" />
            <span class="dark:text-white">Cash Flow Statement</span>
            <x-filament::badge color="gray">
                For the period ending {{ now()->format('F j, Y') }}
            </x-filament::badge>
        </div>
    </x-slot>

    @php
        // Fetch cash flow–related accounts
        $accounts = \App\Models\ChartOfAccount::where('statement_type', 'cash_flow')
            ->orderBy('cash_flow_category') // operating, investing, financing
            ->orderBy('code')
            ->get();

        // Group accounts by category: operating / investing / financing
        $grouped = $accounts->groupBy('cash_flow_category');

        $totals = [
            'operating' => 0,
            'investing' => 0,
            'financing' => 0,
        ];

        // Calculate totals
        foreach ($accounts as $acc) {
            $category = $acc->cash_flow_category ?: 'operating';
            $totals[$category] += $acc->balance_vat;
        }

        $netCashFlow = $totals['operating'] + $totals['investing'] + $totals['financing'];
    @endphp

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-white">Net Cash from Operating Activities</h3>
            <p class="text-2xl font-bold mt-2 text-green-600 dark:text-green-300">
                LKR {{ number_format($totals['operating'], 2) }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-white">Net Cash from Investing Activities</h3>
            <p class="text-2xl font-bold mt-2 text-blue-600 dark:text-blue-300">
                LKR {{ number_format($totals['investing'], 2) }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-white">Net Cash from Financing Activities</h3>
            <p class="text-2xl font-bold mt-2 text-purple-600 dark:text-purple-300">
                LKR {{ number_format($totals['financing'], 2) }}
            </p>
        </div>
    </div>

    {{-- CASH FLOW SECTIONS --}}
    <div class="space-y-10">
        @foreach ($grouped as $category => $items)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-xl font-semibold mb-4 text-primary-700 dark:text-white uppercase">
                    {{ ucfirst($category) }} Activities
                </h3>

                <table class="w-full border border-gray-300 dark:border-gray-700 text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700 text-left dark:text-white">
                            <th class="px-3 py-2 border dark:border-gray-700 font-semibold">Code</th>
                            <th class="px-3 py-2 border dark:border-gray-700 font-semibold">Account Name</th>
                            <th class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">Cash Flow</th>
                        </tr>
                    </thead>
                    <tbody class="dark:text-gray-200">
                        @php $sectionTotal = 0; @endphp

                        @foreach ($items as $acc)
                            @php $sectionTotal += $acc->balance_vat; @endphp
                            <tr class="dark:bg-gray-900">
                                <td class="px-3 py-2 border dark:border-gray-700">{{ $acc->code }}</td>
                                <td class="px-3 py-2 border dark:border-gray-700">{{ $acc->name }}</td>
                                <td class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">
                                    {{ number_format($acc->balance_vat, 2) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-gray-100 dark:bg-gray-700 font-bold dark:text-white">
                            <td class="px-3 py-2 border dark:border-gray-700" colspan="2">
                                TOTAL {{ strtoupper($category) }} ACTIVITIES
                            </td>
                            <td class="px-3 py-2 border dark:border-gray-700 text-right">
                                {{ number_format($sectionTotal, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    {{-- NET CASH FLOW --}}
    <div class="
        bg-primary-50 dark:bg-gray-800
        border border-primary-300 dark:border-gray-700
        text-primary-900 dark:text-white
        rounded-lg p-5 mt-10 shadow-sm text-center
    ">
        <div class="text-xl font-semibold dark:text-white">
            Net Increase / (Decrease) in Cash:
        </div>
        <div class="text-lg mt-2 font-bold dark:text-gray-200">
            LKR {{ number_format($netCashFlow, 2) }}
        </div>
    </div>

</x-filament-panels::page>

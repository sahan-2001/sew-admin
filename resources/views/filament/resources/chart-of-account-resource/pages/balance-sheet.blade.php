<x-filament-panels::page>
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-heroicon-o-scale class="w-6 h-6 text-primary-500" />
            <span class="dark:text-white">Balance Sheet</span>
            <x-filament::badge color="gray">
                As of {{ now()->format('F j, Y') }}
            </x-filament::badge>
        </div>
    </x-slot>

    @php
        $accounts = \App\Models\ChartOfAccount::where('statement_type', 'balance_sheet')
            ->orderBy('account_type')
            ->orderBy('sub_category') // Current first, Non-Current later
            ->orderBy('code')
            ->get();

        // Group by type & sub-category for assets & liabilities
        $grouped = $accounts->groupBy(function($item) {
            $type = strtolower($item->account_type);
            if (in_array($type, ['asset', 'liability'])) {
                $sub = $item->sub_category ? ucfirst(str_replace('_', ' ', $item->sub_category)) : 'Uncategorized';
                return "{$type} ({$sub})"; // e.g., "Asset (Current)"
            }
            return ucfirst($type); // Equity or other types
        });

        // Totals
        $totalCurrentAssets = $totalNonCurrentAssets = 0;
        $totalCurrentLiabilities = $totalNonCurrentLiabilities = 0;
        $totalEquity = 0;

        foreach ($accounts as $acc) {
            $type = strtolower($acc->account_type);
            $sub = strtolower($acc->sub_category);

            if ($type === 'asset') {
                if ($sub === 'current') $totalCurrentAssets += $acc->balance_vat;
                elseif ($sub === 'non_current') $totalNonCurrentAssets += $acc->balance_vat;
            } elseif ($type === 'liability') {
                if ($sub === 'current') $totalCurrentLiabilities += $acc->balance_vat;
                elseif ($sub === 'non_current') $totalNonCurrentLiabilities += $acc->balance_vat;
            } elseif ($type === 'equity') {
                $totalEquity += $acc->balance_vat;
            }
        }
    @endphp

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-white">Total Assets</h3>
            <p class="text-2xl font-bold mt-2 text-green-600 dark:text-green-300">
                LKR {{ number_format($totalCurrentAssets + $totalNonCurrentAssets, 2) }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-white">Total Liabilities</h3>
            <p class="text-2xl font-bold mt-2 text-red-600 dark:text-red-300">
                LKR {{ number_format($totalCurrentLiabilities + $totalNonCurrentLiabilities, 2) }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary-700 dark:text-white">Total Equity</h3>
            <p class="text-2xl font-bold mt-2 text-blue-600 dark:text-blue-300">
                LKR {{ number_format($totalEquity, 2) }}
            </p>
        </div>
    </div>

    {{-- ACCOUNT SECTIONS --}}
    <div class="space-y-10">
        @foreach ($grouped as $type => $items)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-xl font-semibold mb-4 text-primary-700 dark:text-white uppercase">
                    {{ $type }}
                </h3>

                <table class="w-full border border-gray-300 dark:border-gray-700 text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700 text-left dark:text-white">
                            <th class="px-3 py-2 border dark:border-gray-700 font-semibold">Code</th>
                            <th class="px-3 py-2 border dark:border-gray-700 font-semibold">Account Name</th>
                            <th class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">Debit VAT</th>
                            <th class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">Credit VAT</th>
                            <th class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">Balance VAT</th>
                        </tr>
                    </thead>
                    <tbody class="dark:text-gray-200">
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

                            <tr class="dark:bg-gray-900">
                                <td class="px-3 py-2 border dark:border-gray-700">{{ $acc->code }}</td>
                                <td class="px-3 py-2 border dark:border-gray-700">{{ $acc->name }}</td>
                                <td class="px-3 py-2 border dark:border-gray-700 text-right">{{ number_format($acc->debit_total_vat, 2) }}</td>
                                <td class="px-3 py-2 border dark:border-gray-700 text-right">{{ number_format($acc->credit_total_vat, 2) }}</td>
                                <td class="px-3 py-2 border dark:border-gray-700 text-right font-semibold">{{ number_format($acc->balance_vat, 2) }}</td>
                            </tr>
                        @endforeach

                        <tr class="bg-gray-100 dark:bg-gray-700 font-bold dark:text-white">
                            <td class="px-3 py-2 border dark:border-gray-700" colspan="2">TOTAL {{ strtoupper($type) }}</td>
                            <td class="px-3 py-2 border dark:border-gray-700 text-right">{{ number_format($totalDebitVat, 2) }}</td>
                            <td class="px-3 py-2 border dark:border-gray-700 text-right">{{ number_format($totalCreditVat, 2) }}</td>
                            <td class="px-3 py-2 border dark:border-gray-700 text-right">{{ number_format($totalBalanceVat, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    {{-- BALANCE SHEET EQUATION --}}
    <div class="
        bg-primary-50 dark:bg-gray-800
        border border-primary-300 dark:border-gray-700
        text-primary-900 dark:text-white
        rounded-lg p-5 mb-10 shadow-sm text-center
    ">
        <div class="text-xl font-semibold dark:text-white">
            Balance Sheet Equation:
        </div>
        <div class="text-lg mt-2 font-bold dark:text-gray-200">
            Total Assets ({{ number_format($totalCurrentAssets + $totalNonCurrentAssets, 2) }})
            =
            Total Liabilities ({{ number_format($totalCurrentLiabilities + $totalNonCurrentLiabilities, 2) }})
            +
            Equity ({{ number_format($totalEquity, 2) }})
        </div>
    </div>
</x-filament-panels::page>

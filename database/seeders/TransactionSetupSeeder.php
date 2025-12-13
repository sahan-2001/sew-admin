<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class TransactionSetupSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            // ================= ASSETS =================
            [
                'code' => '1000',
                'name' => 'Customer Control Account',
                'account_type' => 'asset',
                'sub_category' => 'current',
                'is_control_account' => true,
                'control_account_type' => 'Customer',
            ],
            ['code' => '1010', 'name' => 'Trade Receivables', 'account_type' => 'asset', 'sub_category' => 'current'],
            ['code' => '1020', 'name' => 'Cash in Hand', 'account_type' => 'asset', 'sub_category' => 'current'],
            ['code' => '1030', 'name' => 'Bank Account', 'account_type' => 'asset', 'sub_category' => 'current'],
            ['code' => '1040', 'name' => 'Undeposited Funds', 'account_type' => 'asset', 'sub_category' => 'current'],
            ['code' => '1050', 'name' => 'Inventory', 'account_type' => 'asset', 'sub_category' => 'current'],
            ['code' => '1060', 'name' => 'VAT Receivable', 'account_type' => 'asset', 'sub_category' => 'current'],
            [
                'code' => '1400',
                'name' => 'Fixed Assets Control Account',
                'account_type' => 'asset',
                'sub_category' => 'non_current',
                'is_control_account' => true,
                'control_account_type' => 'Fixed Assets',
            ],

            // ================= LIABILITIES =================
            [
                'code' => '1100',
                'name' => 'Supplier Control Account',
                'account_type' => 'liability',
                'sub_category' => 'current',
                'is_control_account' => true,
                'control_account_type' => 'Supplier',
            ],
            [
                'code' => '1200',
                'name' => 'VAT Control Account',
                'account_type' => 'liability',
                'sub_category' => 'current',
                'is_control_account' => true,
                'control_account_type' => 'VAT',
            ],
            ['code' => '2010', 'name' => 'Trade Payables', 'account_type' => 'liability', 'sub_category' => 'current'],
            ['code' => '2020', 'name' => 'Customer Advances', 'account_type' => 'liability', 'sub_category' => 'current'],
            ['code' => '2030', 'name' => 'Supplier Advances', 'account_type' => 'liability', 'sub_category' => 'current'],
            ['code' => '2040', 'name' => 'GRNI', 'account_type' => 'liability', 'sub_category' => 'current'],
            ['code' => '2050', 'name' => 'VAT Output', 'account_type' => 'liability', 'sub_category' => 'current'],
            ['code' => '2060', 'name' => 'VAT Suspense', 'account_type' => 'liability', 'sub_category' => 'current'],

            // ================= INCOME =================
            ['code' => '3000', 'name' => 'Local Sales', 'account_type' => 'income'],
            ['code' => '3010', 'name' => 'Export Sales', 'account_type' => 'income'],
            ['code' => '3020', 'name' => 'Sales Returns', 'account_type' => 'income'],
            ['code' => '3030', 'name' => 'Sales Discounts', 'account_type' => 'income'],

            // ================= COST OF SALES =================
            ['code' => '4000', 'name' => 'Cost of Goods Sold', 'account_type' => 'expense'],

            // ================= EXPENSES =================
            ['code' => '5000', 'name' => 'Purchase Account', 'account_type' => 'expense'],
            ['code' => '5010', 'name' => 'Purchase Returns', 'account_type' => 'expense'],
            ['code' => '5020', 'name' => 'Purchase Discounts', 'account_type' => 'expense'],
            ['code' => '5030', 'name' => 'Freight In', 'account_type' => 'expense'],
            ['code' => '5040', 'name' => 'Direct Material Purchases', 'account_type' => 'expense'],
            ['code' => '5050', 'name' => 'Indirect Material Purchases', 'account_type' => 'expense'],
            ['code' => '5060', 'name' => 'Production Supplies', 'account_type' => 'expense'],
            ['code' => '5070', 'name' => 'Subcontracting Expenses', 'account_type' => 'expense'],
            ['code' => '5080', 'name' => 'Bad Debt Expense', 'account_type' => 'expense'],
            ['code' => '5090', 'name' => 'Bad Debt Recovery', 'account_type' => 'income'],
            ['code' => '5100', 'name' => 'Supplier Write-Off', 'account_type' => 'expense'],
            ['code' => '5110', 'name' => 'Purchase Price Variance', 'account_type' => 'expense'],

            // ================= EQUITY =================
            ['code' => '6000', 'name' => 'Allowance for Doubtful Debts', 'account_type' => 'equity'],
        ];

        foreach ($accounts as $acc) {
            $accountType = strtolower($acc['account_type']);

            ChartOfAccount::updateOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'account_type' => $accountType,
                    'sub_category' => $acc['sub_category'] ?? null,
                    'is_control_account' => $acc['is_control_account'] ?? false,
                    'control_account_type' => $acc['control_account_type'] ?? null,
                    'statement_type' => in_array($accountType, ['asset', 'liability', 'equity'])
                        ? 'balance_sheet'
                        : 'income_statement',
                    'description' => $acc['name'],
                    'status' => 'Active',
                ]
            );
        }
    }
}

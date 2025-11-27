<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransactionSetup;
use App\Models\ChartOfAccount;

class TransactionSetupSeeder extends Seeder
{
    public function run()
    {
        // Transaction Setup 
        $setups = [
            ['transaction_name' => 'Sales Invoice creation', 'description' => 'Customer sales'],
            ['transaction_name' => 'Purchase Invoice creation', 'description' => 'Supplier purchase'],
            ['transaction_name' => 'Payment Receive for Purchase Invoice', 'description' => 'Receive PO invoice payment'],
            ['transaction_name' => 'Payment for Purchase Invoice', 'description' => 'Payment for SO/CO invoices'],
        ];

        foreach ($setups as $setup) {
            TransactionSetup::firstOrCreate(
                ['transaction_name' => $setup['transaction_name']],
                $setup
            );
        }

        $accounts = [
            ['code' => '1000', 'name' => 'Customer Control Account',        'account_type' => 'Asset', 'is_control_account' => true, 'control_account_type' => 'Customer',       'statement_type' => 'balance_sheet'],
            ['code' => '1100', 'name' => 'Supplier/Vendor Control Account',        'account_type' => 'Liability', 'is_control_account' => true, 'control_account_type' => 'Supplier',       'statement_type' => 'balance_sheet'],
            ['code' => '1200', 'name' => 'VAT Control Account',             'account_type' => 'Liability', 'is_control_account' => true, 'control_account_type' => 'VAT',            'statement_type' => 'balance_sheet'],
            ['code' => '1300', 'name' => 'Cash & Bank Control Account',     'account_type' => 'Asset', 'is_control_account' => true, 'control_account_type' => 'Cash & Bank',    'statement_type' => 'balance_sheet'],
            ['code' => '1400', 'name' => 'Fixed Assets Control Account',    'account_type' => 'Asset', 'is_control_account' => true, 'control_account_type' => 'Fixed Assets',   'statement_type' => 'balance_sheet'],
        ];

        foreach ($accounts as $acc) {
                ChartOfAccount::firstOrCreate(
                    ['code' => $acc['code']],  

                    [   
                        'name' => $acc['name'],
                        'account_type' => strtolower($acc['account_type']),
                        'is_control_account' => $acc['is_control_account'],
                        'control_account_type' => $acc['control_account_type'],
                        'statement_type' => $acc['statement_type'],
                        'description' => $acc['name'],
                        'status' => 'Active',
                    ]
                );
            }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ProductionMachine;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductionMachinePdfController extends Controller
{
    public function show(ProductionMachine $production_machine)
    {
        // Fetch company details
        $company = Company::first();

        if (!$company) {
            return abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        $machineDetails = [
            'id' => $production_machine->id,
            'name' => $production_machine->name,
            'description' => $production_machine->description,
            'purchased_date' => $production_machine->purchased_date,
            'start_working_date' => $production_machine->start_working_date,
            'expected_lifetime' => $production_machine->expected_lifetime,
            'purchased_cost' => $production_machine->purchased_cost,
            'additional_cost' => $production_machine->additional_cost,
            'additional_cost_description' => $production_machine->additional_cost_description,
            'total_initial_cost' => $production_machine->total_initial_cost,
            'depreciation_rate' => $production_machine->depreciation_rate,
            'depreciation_calculated_from' => $production_machine->depreciation_calculated_from,
            'last_depreciation_calculated_date' => $production_machine->last_depreciation_calculated_date,
            'depreciation_last' => $production_machine->depreciation_last,
            'cumulative_depreciation' => $production_machine->cumulative_depreciation,
            'net_present_value' => $production_machine->net_present_value,
        ];

        return Pdf::loadView('production-machines.pdf', [
            'companyDetails' => $companyDetails,
            'machineDetails' => $machineDetails,
        ])
        ->setPaper('a4')
        ->stream('production-machine-' . $production_machine->id . '.pdf');
    }
}

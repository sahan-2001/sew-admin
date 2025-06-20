<?php

namespace App\Http\Controllers;

use App\Models\EnterPerformanceRecord;
use App\Models\Company;
use Illuminate\Http\Request;

class PerformanceRecordViewController extends Controller
{
    public function show($id)
    {
        $record = EnterPerformanceRecord::with([
            'employeePerformances',
            'machinePerformances',
            'supervisorPerformances',
            'servicePerformances',
            'invWastePerformances',
            'nonInvPerformances',
            'byProductsPerformances',
            'qcPerformances',
            'employeeLabelPerformances.label',
            'machineLabelPerformances.label',
            'qcLabelPerformances.label',
        ])->findOrFail($id);

        $company = Company::first();
        $companyDetails = $company ? [
            'name' => $company->name,
            'address' => trim("{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}", ', '),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ] : [
            'name' => 'N/A',
            'address' => 'N/A',
            'phone' => 'N/A',
            'email' => 'N/A',
        ];

    return view('filament.resources.enter-performance-record-resource.pages.view', compact('record', 'companyDetails'));
    }
}

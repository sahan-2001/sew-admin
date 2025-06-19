<?php
namespace App\Http\Controllers;

use App\Models\EnterPerformanceRecord;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Http\Request;

class PerformanceRecordPrintController extends Controller
{
    public function print(EnterPerformanceRecord $enter_performance_record)
    {
        // Fetch company details
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

        $enter_performance_record->load([
            'employeePerformances',
            'machinePerformances',
            'supervisorPerformances',
            'servicePerformances',
            'invWastePerformances',
            'nonInvPerformances',
            'byProductsPerformances',
            'qcPerformances',
            'employeeLabelPerformances',
            'machineLabelPerformances',
            'qcLabelPerformances',
            
            'employeeLabelPerformances.label',
            'machineLabelPerformances.label',
            'qcLabelPerformances.label',
        ]);

        $pdf = Pdf::loadView('performance-records.print', [
            'record' => $enter_performance_record,
            'companyDetails' => $companyDetails,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('performance-record-'.$enter_performance_record->id.'.pdf');
    }
}
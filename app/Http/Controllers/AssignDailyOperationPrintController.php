<?php

namespace App\Http\Controllers;

use App\Models\AssignDailyOperation;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;

class AssignDailyOperationPrintController extends Controller
{
    public function print(AssignDailyOperation $assignDailyOperation)
    {
        // Fetch company details
        $company = Company::first();
        if (!$company) {
            abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => implode(', ', array_filter([
                $company->address_line_1,
                $company->address_line_2,
                $company->address_line_3,
                $company->city,
                $company->country,
                $company->postal_code
            ])),
            'contact' => implode(' | ', array_filter([
                $company->primary_phone,
                $company->email
            ]))
        ];

        // Load relationships with nested relationships
        $assignDailyOperation->load([
            'lines' => function($query) {
                $query->with([
                    'productionLine',
                    'workstation',
                    'operation',
                    'assignedEmployees',
                    'assignedSupervisors',
                    'assignedProductionMachines',
                    'assignedThirdPartyServices'
                ]);
            },
            'labels',
        ]);

        $pdf = Pdf::loadView('pdf.assign-daily-operation', [
            'operation' => $assignDailyOperation,
            'companyDetails' => $companyDetails,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("daily-operation-{$assignDailyOperation->id}.pdf");
    }
}
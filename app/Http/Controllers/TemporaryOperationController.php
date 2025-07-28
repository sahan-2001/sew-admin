<?php

namespace App\Http\Controllers;

use App\Models\TemporaryOperation;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TemporaryOperationController extends Controller
{
    public function print(TemporaryOperation $operation)
    {
        // Load all related data
        $operation->load([
            'productionLine',
            'workstation',
            'employees',
            'supervisors',
            'productionMachines',
            'services',
        ]);

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
                $company->postal_code,
            ])),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Load PDF view
        $pdf = Pdf::loadView('pdf.temporary-operation', [
            'operation' => $operation,
            'companyDetails' => $companyDetails,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("temporary-operation-{$operation->id}.pdf");
    }
}

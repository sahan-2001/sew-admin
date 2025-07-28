<?php

namespace App\Http\Controllers;

use App\Models\MaterialQC;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MaterialQCPrintController extends Controller
{
    public function print(MaterialQC $materialQC)
    {
        // Fetch company details
        $company = Company::first();
        if (!$company) {
            abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Load relationships for MaterialQC
        $materialQC->load([
            'purchaseOrder',
            'inventoryItem',
            'inspectedBy',
            'storeLocation',
            'registerArrival',
        ]);

        $pdf = Pdf::loadView('pdf.material-qc-report', [
            'materialQC' => $materialQC,
            'companyDetails' => $companyDetails,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("material-qc-{$materialQC->id}.pdf");
    }
}

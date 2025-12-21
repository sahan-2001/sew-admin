<?php

namespace App\Http\Controllers;

use App\Models\MaterialQC;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;

class MaterialQCReturnScrapNotePrintController extends Controller
{
    public function printReturnNote(MaterialQC $materialQC)
    {
        $materialQC->load([
            'purchaseOrder.supplier',
            'inventoryItem',
            'storeLocation',
            'inspectedBy',
            'registerArrival.location',
            'registerArrival.items.inventoryItem',
        ]);

        if (($materialQC->returned_qty ?? 0) <= 0) {
            abort(404, 'No returned items available for this QC record.');
        }

        $company = Company::firstOrFail();

        $companyDetails = [
            'name'    => $company->name,
            'address' => trim(implode(', ', array_filter([
                $company->address_line_1,
                $company->address_line_2,
                $company->address_line_3,
                $company->city,
                $company->country,
                $company->postal_code,
            ]))),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        $pdf = Pdf::loadView('material_qc.return_scrap_note', [
            'qc'             => $materialQC,
            'companyDetails' => $companyDetails,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream(
            'QC_Return_Note_' . str_pad($materialQC->id, 5, '0', STR_PAD_LEFT) . '.pdf'
        );
    }
}

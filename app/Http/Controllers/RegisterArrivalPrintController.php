<?php

namespace App\Http\Controllers;

use App\Models\RegisterArrival;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;

class RegisterArrivalPrintController extends Controller
{
    public function print(RegisterArrival $registerArrival)
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

        // Eager load relationships
        $registerArrival->load([
            'purchaseOrder',
            'location',
            'items.inventoryItem'
        ]);

        // Calculate grand total using computed 'total' attribute
        $grandTotal = $registerArrival->items->sum(function ($item) {
            return $item->total;
        });

        $pdf = Pdf::loadView('pdf.register-arrival-report', [
            'registerArrival' => $registerArrival,
            'companyDetails' => $companyDetails,
            'grandTotal' => $grandTotal,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("arrival-report-{$registerArrival->id}.pdf");
    }
}

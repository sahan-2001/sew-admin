<?php

namespace App\Http\Controllers;

use App\Models\ThirdPartyService;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;

class ThirdPartyServicePdfController extends Controller
{
    public function show(ThirdPartyService $thirdPartyService)
    {
        $company = Company::first();

        if (!$company) {
            abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => trim("{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}", ", "),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Eager load supplier, processes, and payments
        $thirdPartyService->load('supplier', 'processes', 'payments');

        return Pdf::loadView('third-party-services.pdf', [
            'companyDetails' => $companyDetails,
            'service' => $thirdPartyService,
            'payments' => $thirdPartyService->payments, 
        ])
        ->setPaper('a4')
        ->stream('third-party-service-report-' . $thirdPartyService->id . '.pdf');
    }
}

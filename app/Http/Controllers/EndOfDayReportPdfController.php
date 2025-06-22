<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\EndOfDayReport;
use App\Models\Company;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class EndOfDayReportPdfController extends Controller
{
    public function show(EndOfDayReport $endOfDayReport)
    {
        // Fetch company details
        $company = Company::first(); 

        if (!$company) {
            return abort(500, 'Company details not found.');
        }

        // Load operations with their related performance records and assigned operations
        $operations = $endOfDayReport->operations()
            ->with(['performanceRecord', 'assignedOperation'])
            ->get();

        // Generate and return the PDF
        return Pdf::loadView('pdf.end-of-day-report', [
            'company' => [
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
            ],
            'report' => $endOfDayReport, // Pass the entire model
            'operations' => $operations,
        ])
        ->setPaper('a4', 'portrait')
        ->stream('end-of-day-report-' . $endOfDayReport->id . '.pdf');
    }
}
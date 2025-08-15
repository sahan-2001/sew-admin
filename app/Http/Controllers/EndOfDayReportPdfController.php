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

        // Load operations with their related records
        $operations = $endOfDayReport->operations()
            ->with([
                'performanceRecord', 
                'assignedOperation',
                'temporaryOperation'
            ])
            ->get();

        // Separate performance records and temporary operations
        $performanceRecords = $operations->filter(function($op) {
            return $op->enter_performance_record_id !== null;
        });
        
        $temporaryOperations = $operations->filter(function($op) {
            return $op->temporary_operation_id !== null;
        });

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
            'report' => $endOfDayReport,
            'performanceRecords' => $performanceRecords,
            'temporaryOperations' => $temporaryOperations,
        ])
        ->setPaper('a4', 'portrait')
        ->stream('end-of-day-report-' . $endOfDayReport->id . '.pdf');
    }
}
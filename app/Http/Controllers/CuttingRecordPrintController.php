<?php

namespace App\Http\Controllers;

use App\Models\CuttingRecord;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\DNS1D;

class CuttingRecordPrintController extends Controller
{
    public function print(CuttingRecord $cuttingRecord)
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

        // Generate barcodes for labels
        $labels = $cuttingRecord->cutPieceLabels->map(function ($label) {
            if (!empty($label->barcode_id)) {
                $barcode = new DNS1D();
                $barcode->setStorPath(storage_path('framework/barcodes'));
                
                $png = $barcode->getBarcodePNG($label->barcode_id, 'C128', 2, 50);
                $label->barcode_base64 = 'data:image/png;base64,' . $png;
            } else {
                $label->barcode_base64 = null;
            }
            return $label;
        });

        // Label display settings (configurable)
        $labelSettings = [
            'columns' => 2,                  // Number of columns (1 or 2)
            'labels_per_page' => 10,         // Total labels per page
            'label_width' => '50%',          // Width for each label (when using columns)
            'label_height' => '160px',       // Fixed height for each label
            'show_border' => true,           // Whether to show borders around labels
            'font_size' => '12px',          // Base font size
            'include_company_header' => true // Show company header on first page
        ];

        // Calculate labels per page based on columns
        $labelSettings['labels_per_page'] = $labelSettings['columns'] == 2 ? 10 : 5;
        
        $pdf = Pdf::loadView('pdf.cutting-record-report', [
            'cuttingRecord' => $cuttingRecord,
            'labels' => $labels,
            'companyDetails' => $companyDetails,
            'labelSettings' => $labelSettings // Pass settings to view
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("cutting-labels-{$cuttingRecord->id}.pdf");
    }

    // Optional: Add method to customize label settings via request
    public function printWithSettings(CuttingRecord $cuttingRecord, Request $request)
    {
        // Get default settings
        $labelSettings = [
            'columns' => $request->input('columns', 2),
            'labels_per_page' => $request->input('labels_per_page', 10),
            // ... other settings from request
        ];
        
        // Rest of the printing logic same as above
        // Just pass $labelSettings to the view
    }
}
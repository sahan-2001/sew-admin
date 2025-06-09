<?php

namespace App\Http\Controllers;

use App\Models\CuttingRecord;
use App\Models\Company;
use App\Models\CuttingEmployee;
use App\Models\CuttingQualityControl;
use App\Models\CuttingInventoryWaste;
use App\Models\CuttingNonInventoryWaste;
use App\Models\CuttingByProduct;
use App\Models\CuttingOrderItem;
use App\Models\CuttingOrderVariation;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

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
        
        // Fetch all related data separately
        $employees = CuttingEmployee::where('cutting_record_id', $cuttingRecord->id)->get();
        $qualityControls = CuttingQualityControl::where('cutting_record_id', $cuttingRecord->id)->get();
        $wasteRecords = CuttingInventoryWaste::where('cutting_record_id', $cuttingRecord->id)->get();
        $nonInventoryWaste = CuttingNonInventoryWaste::where('cutting_record_id', $cuttingRecord->id)->get();
        $byProductRecords = CuttingByProduct::where('cutting_record_id', $cuttingRecord->id)->get();
        $orderItems = CuttingOrderItem::where('cutting_record_id', $cuttingRecord->id)->get();
        $orderVariations = CuttingOrderVariation::where('cutting_record_id', $cuttingRecord->id)->get();

        $pdf = Pdf::loadView('pdf.cutting-record-report', [
            'cuttingRecord' => $cuttingRecord,
            'companyDetails' => $companyDetails,
            'employees' => $employees,
            'qualityControls' => $qualityControls,
            'wasteRecords' => $wasteRecords,
            'nonInventoryWaste' => $nonInventoryWaste,
            'byProductRecords' => $byProductRecords,
            'orderItems' => $orderItems,
            'orderVariations' => $orderVariations,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("cutting-report-{$cuttingRecord->id}.pdf");
    }
}
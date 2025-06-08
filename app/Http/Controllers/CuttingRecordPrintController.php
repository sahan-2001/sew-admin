<?php

namespace App\Http\Controllers;

use App\Models\CuttingRecord;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\DNS1D;

class CuttingRecordPrintController extends Controller
{
    public function print(CuttingRecord $cuttingRecord)
    {
        $labels = $cuttingRecord->cutPieceLabels->map(function ($label) {
            if (!empty($label->barcode_id)) {
                $barcode = new DNS1D();
                $barcode->setStorPath(storage_path('framework/barcodes')); // dummy path

                $png = $barcode->getBarcodePNG($label->barcode_id, 'C128', 2, 50);
                $label->barcode_base64 = 'data:image/png;base64,' . $png;
            } else {
                $label->barcode_base64 = null;
            }

            return $label;
        });

        $pdf = Pdf::loadView('pdf.cutting-record-report', [
            'cuttingRecord' => $cuttingRecord,
            'labels' => $labels,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("cutting-labels-{$cuttingRecord->id}.pdf");
    }


}
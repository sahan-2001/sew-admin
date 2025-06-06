<?php

namespace App\Http\Controllers;

use App\Models\CuttingRecord;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CuttingRecordPrintController extends Controller
{
    public function print(CuttingRecord $cuttingRecord)
    {
        $cutPieceLabels = $cuttingRecord->cutPieceLabels;

        $pdf = Pdf::loadView('pdf.cutting-record-report', [
            'cuttingRecord' => $cuttingRecord,
            'labels' => $cutPieceLabels,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("cutting-record-{$cuttingRecord->id}.pdf");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CuttingLabel;
use App\Models\CuttingRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\DNS1D;

class CuttingLabelPrintController extends Controller
{
    public function print(CuttingRecord $cuttingRecord)
{
    $labels = $cuttingRecord->cutPieceLabels;

    if ($labels->isEmpty()) {
        abort(404, 'No cutting labels found for this record.');
    }

    return Pdf::loadView('pdf.cutting-labels-grid', [
        'labels' => $labels,
        'labelSettings' => [
            'columns' => 1, 
            'labels_per_page' => null, 
            'label_width' => '100%', 
            'label_height' => 'auto',
            'show_border' => true,
            'font_size' => '12px',
        ]
    ])->setPaper([0, 0, 226.77, 999.99]) // 80mm width = 226.77 points; height is flexible
    ->setOption('isHtml5ParserEnabled', true)
    ->setOption('isPhpEnabled', true)
    ->stream("cutting-labels-{$cuttingRecord->id}.pdf");
    }
}

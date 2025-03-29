<?php

namespace App\Http\Controllers;

use App\Models\SampleOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SampleOrderController extends Controller
{
    public function showPdf(SampleOrder $sampleOrder)
    {
        // Load the sample order along with its items and variations
        $orderDescriptions = $sampleOrder->items()->with('variations')->get();

        // Calculate the grand total
        $grandTotal = $orderDescriptions->sum(function ($item) {
            return $item->total + $item->variations->sum('total');
        });

        // Create PDF
        $pdf = Pdf::loadView('pdf.sample_order', [
            'sampleOrder' => $sampleOrder,
            'orderDescriptions' => $orderDescriptions,
            'grandTotal' => $grandTotal,
            'printedBy' => auth()->user()->name,
            'generatedAt' => now(),
        ]);

        return $pdf->stream('sample_order_' . $sampleOrder->id . '.pdf');
    }
}


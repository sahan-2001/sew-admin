<?php
// app/Http/Controllers/PurchaseOrderPdfController.php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderPdfController extends Controller
{
    public function show(PurchaseOrder $purchaseOrder)
    {
        $pdf = Pdf::loadView('purchase-orders.pdf', ['purchaseOrder' => $purchaseOrder]);
        return $pdf->download('purchase-order-' . $purchaseOrder->id . '.pdf');
    }
}
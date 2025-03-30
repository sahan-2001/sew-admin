<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;

class PurchaseOrderPdfController extends Controller
{
    /**
     * Show the PDF for the given purchase order.
     *
     * @param  \App\Models\PurchaseOrder  $purchase_order
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseOrder $purchase_order)
    {
        // Ensure the status is not 'planned'
        if ($purchase_order->status === 'planned') {
            abort(403, 'PDF export is not allowed for planned purchase orders.');
        }

        // Prepare data for the PDF
        $companyDetails = [
            'name' => 'Your Company Name',
            'address' => '123 Main Street, City, Country',
            'phone' => '+123456789',
            'email' => 'info@company.com',
        ];

        $purchaseOrderDetails = [
            'id' => $purchase_order->id,
            'provider_type' => $purchase_order->provider_type,
            'provider_id' => $purchase_order->provider_id,
            'provider_name' => $purchase_order->provider_name,
            'wanted_date' => $purchase_order->wanted_date,
            'status' => $purchase_order->status,
            'created_at' => $purchase_order->created_at->format('Y-m-d H:i:s'), 
        ];

        $purchaseOrderItems = $purchase_order->items()->with('inventoryItem')->get();
        $grandTotal = $purchaseOrderItems->sum(fn ($item) => $item->quantity * $item->price);

        // Get QR code path
        $qrCodePath = $purchase_order->qr_code
            ? asset('storage/qrcodes/' . $purchase_order->qr_code)
            : null;

        // Generate the PDF
        $pdf = Pdf::loadView('purchase-orders.pdf', [
            'companyDetails' => $companyDetails,
            'purchaseOrderDetails' => $purchaseOrderDetails,
            'purchaseOrderItems' => $purchaseOrderItems,
            'grandTotal' => $grandTotal,
            'qrCodePath' => $qrCodePath,
        ]);

        return $pdf->stream('purchase_order_' . $purchase_order->id . '.pdf');
    }
}
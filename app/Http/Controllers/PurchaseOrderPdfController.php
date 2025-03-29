<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;


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

        // Fetch purchase order items with inventory item details
        $purchaseOrderItems = $purchase_order->items()->with('inventoryItem')->get();

        // Calculate the grand total
        $grandTotal = $purchaseOrderItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        // Generate the QR code
        $qrCodePath = $this->generateQrCode($purchase_order);

        // Pass the QR code path to the Blade view
        $pdf = Pdf::loadView('purchase-orders.pdf', [
            'companyDetails' => $companyDetails,
            'purchaseOrderDetails' => $purchaseOrderDetails,
            'purchaseOrderItems' => $purchaseOrderItems,
            'grandTotal' => $grandTotal,
            'qrCodePath' => $qrCodePath,
        ]);

        // Return the PDF as a streamed response
        return $pdf->stream('purchase_order_' . $purchase_order->id . '.pdf');
    }

    /**
     * Generate a QR code for the given purchase order.
     *
     * @param  \App\Models\PurchaseOrder  $purchase_order
     * @return string  The path to the QR code image.
     */
    private function generateQrCode(PurchaseOrder $purchase_order)
{
    // Extract details for the QR code
    $purchaseOrderId = $purchase_order->id;
    $providerId = $purchase_order->provider_id;
    $wantedDate = Carbon::parse($purchase_order->wanted_date)->format('Y-m-d');

    // Create the QR code content
    $qrContent = "Purchase Order: {$purchaseOrderId}\nProvider ID: {$providerId}\nWanted Date: {$wantedDate}";

    // Generate the QR code
    $qrCode = new QrCode($qrContent); // Use the constructor

    $writer = new PngWriter();
    $qrCodeResult = $writer->write($qrCode);

    // Define the file path
    $fileName = 'purchase_order_' . $purchaseOrderId . '.png';
    $path = 'public/qrcodes/' . $fileName;

    // Save the QR code image to storage
    Storage::put($path, $qrCodeResult->getString());

    // Return the public URL to the QR code
    return asset('storage/qrcodes/' . $fileName);
}
}
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; 
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseOrder;

class PurchaseOrderController extends Controller
{
        public function generateQrCode(PurchaseOrder $purchase_order)
    {
        // Extract details for the QR code
        $purchaseOrderId = $purchase_order->id;
        $providerId = $purchase_order->provider_id; // Assuming provider_id exists
        $wantedDate = Carbon::parse($purchase_order->wanted_date)->format('Y-m-d');

        // Create the QR code content
        $qrContent = "Purchase Order: {$purchaseOrderId}\nProvider ID: {$providerId}\nWanted Date: {$wantedDate}";

        // Generate the QR code
        $qrCode = new QrCode($qrContent);
        $writer = new PngWriter();

        // Generate the QR code image as binary data
        $qrCodeResult = $writer->write($qrCode);

        // Define the file path
        $fileName = 'purchase_order_' . $purchaseOrderId . '.png';
        $path = 'public/qrcodes/' . $fileName;

        // Save the QR code image to storage
        Storage::put($path, $qrCodeResult->getString());

        // Return the QR code as a downloadable response
        return response()->streamDownload(function () use ($qrCodeResult) {
            echo $qrCodeResult->getString();
        }, $fileName, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

}

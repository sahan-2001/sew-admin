<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Writer\SvgWriter;

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

        $purchaseOrderItems = $purchase_order->items()->with('inventoryItem')->get();
        $grandTotal = $purchaseOrderItems->sum(fn ($item) => $item->quantity * $item->price);

        // Generate QR Code URL
        $qrCodeData = url('/purchase-order/' . $purchase_order->id);

        // Create QR Code
        $qrCode = new QrCode($qrCodeData);


        // Generate PNG Image
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Define the filename and storage path
        $qrCodeFilename = 'qrcode_' . $purchase_order->id . '.png';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        // Ensure the directory exists
        Storage::makeDirectory('public/qrcodes');

        // Store the QR code in Laravel storage
        Storage::put($path, $result->getString());

        // Generate and return the PDF
        return Pdf::loadView('purchase-orders.pdf', [
            'companyDetails' => $companyDetails,
            'purchaseOrderDetails' => $purchaseOrderDetails,
            'purchaseOrderItems' => $purchaseOrderItems,
            'grandTotal' => $grandTotal,
            'qrCodePath' => storage_path('app/public/qrcodes/qrcode_' . $purchase_order->id . '.png'),
            'qrCodeData' => $qrCodeData
        ])->setPaper('a4')->stream('purchase-order-'.$purchase_order->id.'.pdf');
    }

}
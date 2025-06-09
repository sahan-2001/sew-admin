<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseOrder;
use App\Models\Company;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class PurchaseOrderPdfController extends Controller
{
    public function show(PurchaseOrder $purchase_order)
    {
        // Fetch company details
        $company = Company::first(); 

        if (!$company) {
            return abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',        
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

        // Generate QR Code URL using ID + RANDOM CODE
        $qrCodeData = url('/purchase-order/' . $purchase_order->id . '/' . $purchase_order->random_code);

        // Create QR Code
        $qrCode = new QrCode($qrCodeData);

        // Write SVG instead of PNG to avoid GD dependency
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // Save QR Code as SVG
        $qrCodeFilename = 'qrcode_' . $purchase_order->id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Path to be used in the Blade view
        $qrCodePath = storage_path('app/public/qrcodes/' . $qrCodeFilename);

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Generate and return the PDF
        return Pdf::loadView('purchase-orders.pdf', [
            'companyDetails' => $companyDetails,
            'purchaseOrderDetails' => $purchaseOrderDetails,
            'purchaseOrderItems' => $purchaseOrderItems,
            'grandTotal' => $grandTotal,
            'qrCodePath' => storage_path('app/public/qrcodes/' . $qrCodeFilename),
            'qrCodeData' => $qrCodeData
        ])->setPaper('a4')->stream('purchase-order-' . $purchase_order->id . '.pdf');
    }
}

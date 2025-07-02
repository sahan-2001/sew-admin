<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\SampleOrder;
use App\Models\Company;
use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class SampleOrderPdfController extends Controller
{
    
    public function show(SampleOrder $sample_order)
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

        $sampleOrderDetails = [
            'id' => $sample_order->order_id,
            'customer_id' => $sample_order->customer_id,
            'customer_name' => $sample_order->customer->name ?? 'N/A',
            'wanted_delivery_date' => $sample_order->wanted_delivery_date,
            'status' => $sample_order->status,
            'created_at' => $sample_order->created_at->format('Y-m-d H:i:s'),
        ];

        $sampleOrderItems = $sample_order->items()->with('variations')->get();
        $grandTotal = $sampleOrderItems->sum(function ($item) {
            $itemTotal = $item->quantity * $item->price;
            $variationsTotal = $item->variations->sum(fn ($variation) => $variation->quantity * $variation->price);
            return $itemTotal + $variationsTotal;
        });

        // Generate QR Code URL using ID + RANDOM CODE
        $qrCodeData = url('/sample-orders/' . $sample_order->order_id . '/' . $sample_order->random_code);

        // Create QR Code (SVG)
        $qrCode = new QrCode($qrCodeData);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // Save QR Code as SVG
        $qrCodeFilename = 'qrcode_' . $sample_order->order_id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Generate and return the PDF
        return Pdf::loadView('sample-orders.pdf', [
            'companyDetails' => $companyDetails,
            'sampleOrderDetails' => $sampleOrderDetails,
            'sampleOrderItems' => $sampleOrderItems,
            'grandTotal' => $grandTotal,
            'qrCodePath' => storage_path('app/public/qrcodes/' . $qrCodeFilename),
            'qrCodeData' => $qrCodeData,
        ])->setPaper('a4')->stream('sample-order-' . $sample_order->order_id . '.pdf');
    }
}
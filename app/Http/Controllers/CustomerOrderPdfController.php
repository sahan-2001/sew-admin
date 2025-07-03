<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\CustomerOrder;
use App\Models\Company;
use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class CustomerOrderPdfController extends Controller
{
    public function show(CustomerOrder $customer_order)
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

        $customerOrderDetails = [
            'id' => $customer_order->order_id,
            'name' => $customer_order->name,
            'customer_id' => $customer_order->customer_id,
            'customer_name' => $customer_order->customer->name ?? 'N/A',
            'wanted_delivery_date' => $customer_order->wanted_delivery_date,
            'status' => $customer_order->status,
            'special_notes' => $customer_order->special_notes ?? 'None',
            'created_at' => $customer_order->created_at->format('Y-m-d H:i:s'),
            'grand_total' => $customer_order->grand_total,
        ];

        $orderItems = $customer_order->orderItems()->with('variationItems')->get();
        
        // Generate QR Code URL using ID + RANDOM CODE
        $qrCodeData = url('/customer-orders/' . $customer_order->order_id . '/' . $customer_order->random_code);

        // Create QR Code (SVG)
        $qrCode = new QrCode($qrCodeData);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // Save QR Code as SVG
        $qrCodeFilename = 'customer_order_qrcode_' . $customer_order->order_id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Generate and return the PDF
        return Pdf::loadView('customer-orders.pdf', [
            'companyDetails' => $companyDetails,
            'orderDetails' => $customerOrderDetails,
            'orderItems' => $orderItems,
            'qrCodePath' => storage_path('app/public/qrcodes/' . $qrCodeFilename),
            'qrCodeData' => $qrCodeData,
        ])->setPaper('a4')->stream('customer-order-' . $customer_order->order_id . '.pdf');
    }
}
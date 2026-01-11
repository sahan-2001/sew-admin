<?php

namespace App\Http\Controllers;

use App\Models\RequestForQuotation;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class RequestForQuotationController extends Controller
{
    public function print(RequestForQuotation $rfq)
    {
        // Load relationships
        $rfq->load('supplier', 'user', 'items.inventoryItem', 'paymentTerm', 'deliveryTerm', 'deliveryMethod', 'currency');

        // Fetch company details
        $company = Company::first();
        if (!$company) {
            abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Fetch supplier details
        $supplier = $rfq->supplier;

        $rfqDetails = [
            'id' => $rfq->id,
            'supplier_id' => str_pad($supplier?->supplier_id ?? 0, 5, '0', STR_PAD_LEFT), 
            'supplier_name' => $supplier?->name ?? 'N/A',
            'supplier_email' => $supplier?->email ?? 'N/A',
            'supplier_phone' => $supplier?->phone_1 ?? 'N/A',
            'wanted_delivery_date' => $rfq->wanted_delivery_date,
            'valid_until' => $rfq->valid_until,
            'status' => $rfq->status,
            'created_at' => $rfq->created_at->format('Y-m-d H:i:s'),
            'created_by' => $rfq->user?->name ?? 'N/A',
            'payment_term' => $rfq->paymentTerm ? "{$rfq->paymentTerm->name} | {$rfq->paymentTerm->description}": 'N/A',
            'delivery_term' => $rfq->deliveryTerm ? "{$rfq->deliveryTerm->name} | {$rfq->deliveryTerm->description}": 'N/A',
            'delivery_method' => $rfq->deliveryMethod ? "{$rfq->deliveryMethod->name} | {$rfq->deliveryMethod->description}": 'N/A',
            'currency' => $rfq->currency ? "{$rfq->currency->code} | {$rfq->currency->name}": 'N/A',
        ];

        // Calculate totals
        $grandTotal = $rfq->items->sum(fn($item) => $item->item_subtotal);

        // Generate QR Code URL
        $qrCodeData = url('/request-for-quotation/' . $rfq->id . '/' . $rfq->random_code);

        $qrCode = new QrCode($qrCodeData);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // Save QR Code as SVG
        $qrCodeFilename = 'rfq_qrcode_' . $rfq->id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Generate PDF
        return Pdf::loadView('pdf.request-for-quotation-print', [
            'companyDetails' => $companyDetails, 
            'rfqDetails' => $rfqDetails,
            'items' => $rfq->items,
            'grandTotal' => $grandTotal,
            'qrCodePath' => storage_path('app/public/qrcodes/' . $qrCodeFilename),
            'qrCodeData' => $qrCodeData,
            'rfq' => $rfq,
        ])
        ->setPaper('a4')
        ->stream('rfq-' . str_pad($rfq->id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }
}

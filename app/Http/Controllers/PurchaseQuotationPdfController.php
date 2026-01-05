<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseQuotation;
use App\Models\Company;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class PurchaseQuotationPdfController extends Controller
{
    public function show(PurchaseQuotation $purchase_quotation)
    {
        // Fetch company details
        $company = Company::first();
        if (!$company) {
            return abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => trim("{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}", ', '),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Supplier details
        $supplier = $purchase_quotation->supplier;
        $supplierDetails = [
            'supplier_id' => str_pad($supplier?->supplier_id ?? 0, 5, '0', STR_PAD_LEFT),
            'name' => $supplier?->name ?? 'N/A',
            'email' => $supplier?->email ?? 'N/A',
            'phone' => $supplier?->phone_1 ?? 'N/A',
        ];

        // Quotation details
        $quotationDetails = [
            'id' => $purchase_quotation->id,
            'quotation_date' => $purchase_quotation->quotation_date,
            'valid_until' => $purchase_quotation->valid_until,
            'wanted_delivery_date' => $purchase_quotation->wanted_delivery_date,
            'promised_delivery_date' => $purchase_quotation->promised_delivery_date,
            'status' => $purchase_quotation->status,
            'vat_base' => $purchase_quotation->vat_base,
            'order_subtotal' => $purchase_quotation->order_subtotal,
            'vat_amount' => $purchase_quotation->vat_amount,
            'grand_total' => $purchase_quotation->grand_total,
        ];

        // Quotation items
        $quotationItems = $purchase_quotation->items()->with('inventoryItem')->get();

        // Generate QR code using ID + random code
        $qrCodeData = url('/purchase-quotation/' . $purchase_quotation->id . '/' . $purchase_quotation->random_code);
        $qrCode = new QrCode($qrCodeData);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        $qrCodeFilename = 'qrcode_quotation_' . $purchase_quotation->id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;
        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Generate PDF
        return Pdf::loadView('purchase-quotations.pdf', [
            'companyDetails' => $companyDetails,
            'supplierDetails' => $supplierDetails,
            'quotationDetails' => $quotationDetails,
            'quotationItems' => $quotationItems,
            'qrCodePath' => storage_path('app/public/qrcodes/' . $qrCodeFilename),
            'qrCodeData' => $qrCodeData,
            'purchase_quotation' => $purchase_quotation,
        ])->setPaper('a4')->stream('purchase-quotation-' . $purchase_quotation->id . '.pdf');
    }
}

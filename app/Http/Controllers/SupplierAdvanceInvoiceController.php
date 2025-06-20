<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\SupplierAdvanceInvoice;
use App\Models\Company;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class SupplierAdvanceInvoiceController extends Controller
{
    public function show(SupplierAdvanceInvoice $supplier_advance_invoice)
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

        // Get related purchase order and supplier
        $purchaseOrder = $supplier_advance_invoice->purchaseOrder;
        $supplier = $supplier_advance_invoice->supplier;

        $supplierDetails = [
            'name' => $supplier->name ?? 'N/A',
            'address' => $supplier->address ?? 'N/A',
            'phone' => $supplier->phone ?? 'N/A',
            'email' => $supplier->email ?? 'N/A',
        ];

        $invoiceDetails = [
            'id' => $supplier_advance_invoice->id,
            'purchase_order_id' => $supplier_advance_invoice->purchase_order_id,
            'status' => $supplier_advance_invoice->status,
            'grand_total' => $supplier_advance_invoice->grand_total,
            'payment_type' => $supplier_advance_invoice->payment_type,
            'fix_payment_amount' => $supplier_advance_invoice->fix_payment_amount,
            'payment_percentage' => $supplier_advance_invoice->payment_percentage,
            'percent_calculated_payment' => $supplier_advance_invoice->percent_calculated_payment,
            'paid_amount' => $supplier_advance_invoice->paid_amount,
            'remaining_amount' => $supplier_advance_invoice->remaining_amount,
            'paid_date' => $supplier_advance_invoice->paid_date ? Carbon::parse($supplier_advance_invoice->paid_date)->format('Y-m-d') : 'N/A',
            'paid_via' => $supplier_advance_invoice->paid_via,
            'created_at' => $supplier_advance_invoice->created_at->format('Y-m-d H:i:s'),
        ];

        // Generate QR Code URL using ID + RANDOM CODE (assuming you have a random_code field)
        $qrCodeData = url('/supplier-advance-invoice/' . $supplier_advance_invoice->id);

        // Create QR Code
        $qrCode = new QrCode($qrCodeData);

        // Write SVG instead of PNG to avoid GD dependency
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // Save QR Code as SVG
        $qrCodeFilename = 'qrcode_sai_' . $supplier_advance_invoice->id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Path to be used in the Blade view
        $qrCodePath = storage_path('app/public/qrcodes/' . $qrCodeFilename);

        // Generate and return the PDF
        return Pdf::loadView('supplier_advance_invoices.pdf', [
            'companyDetails' => $companyDetails,
            'supplierDetails' => $supplierDetails,
            'invoiceDetails' => $invoiceDetails,
            'purchaseOrder' => $purchaseOrder,
            'qrCodePath' => $qrCodePath,
            'qrCodeData' => $qrCodeData
        ])->setPaper('a4')->stream('supplier-advance-invoice-' . $supplier_advance_invoice->id . '.pdf');
    }
}
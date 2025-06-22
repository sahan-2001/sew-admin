<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseOrderInvoice;
use App\Models\Company;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class PurchaseOrderFinalPdfController extends Controller
{
    public function show(PurchaseOrderInvoice $purchase_order_invoice)
    {
        $purchase_order_invoice->load([
            'items.inventoryItem',
            'items.location',
            'additionalCosts',
            'discounts',
            'advanceInvoiceDeductions.advanceInvoice',
            'payments.paidByUser'
        ]);

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

        // Get invoice details
        $invoiceDetails = [
            'id' => $purchase_order_invoice->id,
            'purchase_order_id' => $purchase_order_invoice->purchase_order_id,
            'register_arrival_id' => $purchase_order_invoice->register_arrival_id,
            'provider_type' => $purchase_order_invoice->provider_type,
            'provider_name' => $purchase_order_invoice->provider_name,
            'status' => $purchase_order_invoice->status,
            'created_at' => $purchase_order_invoice->created_at->format('Y-m-d H:i:s'),
            'grand_total' => $purchase_order_invoice->grand_total,
            'adv_paid' => $purchase_order_invoice->adv_paid,
            'additional_cost' => $purchase_order_invoice->additional_cost,
            'discount' => $purchase_order_invoice->discount,
            'due_payment' => $purchase_order_invoice->due_payment,
        ];

        // Get provider details
        $providerDetails = [
            'name' => $purchase_order_invoice->provider_name,
            'type' => $purchase_order_invoice->provider_type,
            'id' => $purchase_order_invoice->provider_id,
        ];

        // Get invoice items - with null check
        $invoiceItems = $purchase_order_invoice->items ? $purchase_order_invoice->items->map(function ($item) {
            return [
                'item_id' => $item->item_id,
                'item_code' => $item->inventoryItem->item_code ?? 'N/A',
                'item_name' => $item->inventoryItem->name ?? 'N/A',
                'quantity' => $item->stored_quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->stored_quantity * $item->unit_price,
                'location' => $item->location->name ?? 'N/A',
            ];
        })->toArray() : [];

        $additionalCosts = $purchase_order_invoice->additionalCosts ? $purchase_order_invoice->additionalCosts->map(function ($cost) {
            return [
                'description' => $cost->description,
                'unit_rate' => $cost->unit_rate,
                'quantity' => $cost->quantity,
                'uom' => $cost->uom,
                'total' => $cost->total,
                'date' => $cost->date,
                'remarks' => $cost->remarks,
            ];
        })->toArray() : [];

        $discountsDeductions = $purchase_order_invoice->discounts ? $purchase_order_invoice->discounts->map(function ($discount) {
            return [
                'description' => $discount->description,
                'unit_rate' => $discount->unit_rate,
                'quantity' => $discount->quantity,
                'uom' => $discount->uom,
                'total' => $discount->total,
                'date' => $discount->date,
                'remarks' => $discount->remarks,
            ];
        })->toArray() : [];

        $advanceInvoices = $purchase_order_invoice->advanceInvoiceDeductions ? $purchase_order_invoice->advanceInvoiceDeductions->map(function ($deduction) {
            return [
                'advance_invoice_id' => $deduction->advance_invoice_id,
                'amount' => $deduction->deduction_amount,
                'type' => $deduction->advanceInvoice->payment_type,
                'paid_date' => $deduction->advanceInvoice->paid_date,
            ];
        })->toArray() : [];

        $invoicePayments = $purchase_order_invoice->payments ? $purchase_order_invoice->payments->map(function ($payment) {
            return [
                'amount' => $payment->payment_amount,
                'remaining_before' => $payment->remaining_amount_before,
                'remaining_after' => $payment->remaining_amount_after,
                'method' => $payment->payment_method,
                'reference' => $payment->payment_reference,
                'notes' => $payment->notes,
                'paid_by' => $payment->paidByUser?->name ?? 'System',
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray() : [];


        // Generate QR Code URL
        $qrCodeData = url('/purchase-order-invoice/' . $purchase_order_invoice->id);

        // Create QR Code
        $qrCode = new QrCode($qrCodeData);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // Save QR Code as SVG
        $qrCodeFilename = 'qrcode_poi_' . $purchase_order_invoice->id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Generate and return the PDF
        return Pdf::loadView('purchase_order_final_invoice.pdf', [
            'companyDetails' => $companyDetails,
            'invoiceDetails' => $invoiceDetails,
            'providerDetails' => $providerDetails,
            'invoiceItems' => $invoiceItems,
            'additionalCosts' => $additionalCosts,
            'discountsDeductions' => $discountsDeductions,
            'advanceInvoices' => $advanceInvoices,
            'invoicePayments' => $invoicePayments,
            'qrCodePath' => storage_path('app/public/qrcodes/' . $qrCodeFilename),
            'qrCodeData' => $qrCodeData
        ])->setPaper('a4')->stream('purchase-order-final-invoice-' . $purchase_order_invoice->id . '.pdf');
    }
}
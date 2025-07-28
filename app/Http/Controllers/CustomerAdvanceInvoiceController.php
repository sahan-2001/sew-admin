<?php

namespace App\Http\Controllers;

use App\Models\CustomerAdvanceInvoice;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class CustomerAdvanceInvoiceController extends Controller
{
    public function show(CustomerAdvanceInvoice $invoice)
    {
        // Eager load customer to avoid N+1 issue
        $invoice->load('customer');

        // Get company info
        $company = Company::first();
        if (!$company) {
            abort(500, 'Company details not found.');
        }

        // Prepare company details
        $companyDetails = [
            'name' => $company->name,
            'address' => implode(', ', array_filter([
                $company->address_line_1,
                $company->address_line_2,
                $company->address_line_3,
                $company->city,
                $company->country,
                $company->postal_code,
            ])),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Prepare invoice details
        $invoiceDetails = [
            'id' => $invoice->id,
            'cus_invoice_number' => $invoice->cus_invoice_number ?? 'N/A',
            'customer_id' => $invoice->customer_id ?? 'N/A',
            'customer_name' => optional($invoice->customer)->name ?? 'N/A',
            'order_type' => $invoice->order_type,
            'order_id' => $invoice->order_id,
            'grand_total' => $invoice->grand_total,
            'amount' => $invoice->amount,
            'paid_date' => $invoice->paid_date,
            'paid_via' => $invoice->paid_via,
            'payment_reference' => $invoice->payment_reference,
            'status' => ucfirst($invoice->status),
            'created_at' => optional($invoice->created_at)->format('Y-m-d H:i:s'),
        ];


        // Generate PDF and stream
        return Pdf::loadView('customer-advance-invoices.pdf', [
            'companyDetails' => $companyDetails,
            'invoiceDetails' => $invoiceDetails,
        ])->setPaper('a4')->stream("advance-invoice-{$invoice->id}.pdf");
    }
}

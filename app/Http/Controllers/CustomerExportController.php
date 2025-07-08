<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Company;
use App\Models\CustomerAdvanceInvoice; 
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CustomerExportController extends Controller
{
    public function exportPdf(Customer $customer)
    {
        $company = Company::first();
        if (!$company) {
            abort(500, 'Company information not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        $customerDetails = [
            'id' => str_pad($customer->customer_id, 5, '0', STR_PAD_LEFT),
            'name' => $customer->name ?? 'N/A',
            'shop_name' => $customer->shop_name ?? 'N/A',
            'address_line_1' => $customer->address_line_1 ?? 'N/A',
            'address_line_2' => $customer->address_line_2 ?? 'N/A',
            'city' => $customer->city ?? 'N/A',
            'phone_1' => $customer->phone_1 ?? 'N/A',
            'phone_2' => $customer->phone_2 ?? 'N/A',
            'email' => $customer->email ?? 'N/A',
            'outstanding_balance' => $customer->outstanding_balance ?? 0,
            'created_at' => $customer->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
        ];

        $advanceInvoices = $customer->supplierAdvanceInvoices()->latest()->get();
        $poInvoices = $customer->purchaseOrderInvoices()->latest()->get();
        $customerInvoices = $customer->customerAdvanceInvoices()->latest()->get();

        return Pdf::loadView('exports.customer-pdf', [
            'companyDetails' => $companyDetails,
            'customerDetails' => $customerDetails,
            'advanceInvoices' => $advanceInvoices,
            'poInvoices' => $poInvoices,
            'customerInvoices' => $customerInvoices,
        ])->setPaper('a4')->stream('customer-' . $customerDetails['id'] . '.pdf');
    }
}

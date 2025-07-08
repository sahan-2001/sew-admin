<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Company;
use App\Models\SupplierAdvanceInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SupplierExportController extends Controller
{

    public function exportPdf(Supplier $supplier)
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

        $supplierDetails = [
            'id' => str_pad($supplier->supplier_id, 5, '0', STR_PAD_LEFT),
            'name' => $supplier->name ?? 'N/A',
            'shop_name' => $supplier->shop_name ?? 'N/A',
            'address_line_1' => $supplier->address_line_1 ?? 'N/A',
            'address_line_2' => $supplier->address_line_2 ?? 'N/A',
            'city' => $supplier->city ?? 'N/A',
            'phone_1' => $supplier->phone_1 ?? 'N/A',
            'phone_2' => $supplier->phone_2 ?? 'N/A',
            'email' => $supplier->email ?? 'N/A',
            'created_at' => $supplier->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
        ];

        $advanceInvoices = $supplier->supplierAdvanceInvoices()->latest()->get();
        $poInvoices = $supplier->purchaseOrderInvoices()->latest()->get();
        $thirdPartyServices = $supplier->thirdPartyServices()->latest()->get();

        return Pdf::loadView('exports.supplier-pdf', [
            'companyDetails' => $companyDetails,
            'supplierDetails' => $supplierDetails,
            'advanceInvoices' => $advanceInvoices,
            'poInvoices' => $poInvoices,
            'thirdPartyServices' => $thirdPartyServices,  
        ])->setPaper('a4')->stream('supplier-' . $supplierDetails['id'] . '.pdf');
    }
}

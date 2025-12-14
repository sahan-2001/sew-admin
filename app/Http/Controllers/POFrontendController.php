<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Http\Request;

class POFrontendController extends Controller
{
    public function showPurchaseOrder($id, $random_code)
    {
        // Fetch the purchase order with items and supplier relationship
        $purchaseOrder = PurchaseOrder::with(['items.inventoryItem', 'supplier'])->find($id);

        if (!$purchaseOrder || $purchaseOrder->random_code !== $random_code) {
            return abort(404, 'Purchase Order not found or invalid code.');
        }

        // Company details
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
        $supplier = $purchaseOrder->supplier ?? Supplier::find($purchaseOrder->provider_id);
        if (!$supplier) {
            return abort(404, 'Supplier not found.');
        }

        $supplierIdPadded = str_pad($supplier->supplier_id ?? $supplier->id ?? 0, 5, '0', STR_PAD_LEFT);

        $supplierDetails = [
            'supplier_id' => $supplierIdPadded,
            'name' => $supplier->name ?? 'N/A',
            'address' => trim("{$supplier->address_line_1}, {$supplier->address_line_2}, {$supplier->city}, {$supplier->country}, {$supplier->postal_code}", ', '),
            'phone' => $supplier->phone ?? $supplier->phone_1 ?? 'N/A',
            'email' => $supplier->email ?? 'N/A',
        ];

        // Pass everything to the frontend view
        return view('frontend.purchase_order', [
            'purchaseOrder' => $purchaseOrder,
            'companyDetails' => $companyDetails,
            'supplierDetails' => $supplierDetails,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Company;
use Illuminate\Http\Request;

class POFrontendController extends Controller
{
    public function showPurchaseOrder($id, $random_code)
    {
        // Fetch the purchase order
        $purchaseOrder = PurchaseOrder::with(['items.inventoryItem'])->find($id);

        if (!$purchaseOrder || $purchaseOrder->random_code !== $random_code) {
            return abort(404, 'Purchase Order not found or invalid code');
        }

        // Fetch the first company details
        $company = Company::first();

        if (!$company) {
            return abort(500, 'Company details not found.');
        }

        // Structure company details
        $companyDetails = [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Return the view
        return view('frontend.purchase_order', compact('purchaseOrder', 'companyDetails'));
    }
}

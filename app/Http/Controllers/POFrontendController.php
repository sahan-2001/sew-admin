<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class POFrontendController extends Controller
{
    public function showPurchaseOrder($id)
    {
        // Fetch the purchase order with related items
        $purchaseOrder = PurchaseOrder::with(['items.inventoryItem'])->find($id);

        if (!$purchaseOrder) {
            return abort(404, 'Purchase Order not found');
        }

        // Define company details
        $companyDetails = [
            'name' => 'Your Company Name',
            'address' => 'Company Address Here',
            'phone' => 'Company Phone',
            'email' => 'company@example.com',
        ];

        // Return the view with the purchaseOrder and companyDetails
        return view('frontend.purchase_order', compact('purchaseOrder', 'companyDetails'));
    }
}

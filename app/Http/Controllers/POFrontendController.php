<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;

class POFrontendController extends Controller
{
    public function showPurchaseOrder($id, $random_code)
    {
        // Fetch the purchase order with items
        $purchaseOrder = PurchaseOrder::with(['items.inventoryItem'])->find($id);

        if (!$purchaseOrder || $purchaseOrder->random_code !== $random_code) {
            return abort(404, 'Purchase Order not found or invalid code');
        }

        // Default company details (header/footer info)
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

        // 🔹 Fetch provider details (customer OR supplier)
        if ($purchaseOrder->provider_type === 'customer') {
            $provider = Customer::find($purchaseOrder->provider_id);
        } else {
            $provider = Supplier::find($purchaseOrder->provider_id);
        }

        if (!$provider) {
            return abort(404, ucfirst($purchaseOrder->provider_type) . ' not found.');
        }

        $providerDetails = [
            'name' => $provider->name ?? 'N/A',
            'address' => trim("{$provider->address_line_1}, {$provider->address_line_2}, {$provider->city}, {$provider->country}, {$provider->postal_code}", ', '),
            'phone' => $provider->phone ?? 'N/A',
            'email' => $provider->email ?? 'N/A',
        ];

        // Pass everything to the view
        return view('frontend.purchase_order', compact('purchaseOrder', 'companyDetails', 'providerDetails'));
    }
}

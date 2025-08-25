<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\Company;
use Illuminate\Http\Request;

class CustomerOrderFrontendController extends Controller
{
    public function showCustomerOrder($order_id, $random_code)
    {
        // Fetch the order with nested relationships
        $customerOrder = CustomerOrder::with([
            'orderItems' => function($query) {
                $query->orderBy('created_at');
            },
            'orderItems.variationItems' => function($query) {
                $query->orderBy('created_at');
            },
            'customer'
        ])->find($order_id);

        if (!$customerOrder || $customerOrder->random_code !== $random_code) {
            return abort(404, 'Customer Order not found or invalid access code');
        }

        // Fetch company details
        $company = Company::first();
        if (!$company) {
            return abort(500, 'Company details not configured');
        }

        // Calculate totals for each order item
        $orderTotal = 0;
        foreach ($customerOrder->orderItems as $item) {
            if ($item->is_variation) {
                $item->calculated_total = $item->variationItems->sum('total');
            } else {
                $item->calculated_total = $item->total;
            }
            $orderTotal += $item->calculated_total;
        }

        // Structure company details
        $companyDetails = [
            'name' => $company->name,
            'address' => implode(', ', array_filter([
                $company->address_line_1,
                $company->address_line_2,
                $company->address_line_3,
                $company->city,
                $company->country,
                $company->postal_code
            ])),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Map order status to progress percentage
        $progressValue = match($customerOrder->status) {
            'rejected' => 0,
            'pending' => 25,
            'accepted' => 50,
            'in_production' => 75,
            'completed' => 100,
            default => 0
        };

        return view('frontend.customer_order', compact(
            'customerOrder',
            'companyDetails',
            'progressValue',
            'orderTotal'
        ));
    }
}

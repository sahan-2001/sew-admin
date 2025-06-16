<?php

namespace App\Http\Controllers;

use App\Models\SampleOrder;
use App\Models\Company;
use Illuminate\Http\Request;

class SOFrontendController extends Controller
{
    public function showSampleOrder($id, $random_code)
    {
        // Fetch the sample order with nested relationships
        $sampleOrder = SampleOrder::with([
            'items' => function($query) {
                $query->orderBy('created_at');
            },
            'items.variations' => function($query) {
                $query->orderBy('created_at');
            },
            'customer',
            'addedBy'
        ])->find($id);

        if (!$sampleOrder || $sampleOrder->random_code !== $random_code) {
            return abort(404, 'Sample Order not found or invalid access code');
        }

        // Fetch company details
        $company = Company::first();
        if (!$company) {
            return abort(500, 'Company details not configured');
        }

        // Calculate totals
        $orderTotal = 0;
        foreach ($sampleOrder->items as $item) {
            if ($item->is_variation) {
                $item->calculated_total = $item->variations->sum('total');
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

        // Calculate status progress
        $progressValue = match($sampleOrder->status) {
            'rejected' => 0,
            'pending' => 25,
            'accepted' => 50,
            'in_production' => 75,
            'completed' => 100,
            default => 0
        };

        return view('frontend.sample_order', compact(
            'sampleOrder',
            'companyDetails',
            'progressValue',
            'orderTotal'
        ));
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\ReleaseMaterial;
use App\Models\ReleaseMaterialLine;
use App\Models\Company;
use App\Models\CuttingStation;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReleaseMaterialPrintController extends Controller
{
    public function print(ReleaseMaterial $releaseMaterial)
    {
        // Fetch company details
        $company = Company::first();
        if (!$company) {
            abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];
        
        // Fetch all related data
        $lines = ReleaseMaterialLine::with(['item', 'location', 'stock'])
            ->where('release_material_id', $releaseMaterial->id)
            ->get();
            
        $cuttingStation = CuttingStation::find($releaseMaterial->cutting_station_id);
        
        // Determine order details based on order type
        $orderDetails = [];
        if ($releaseMaterial->order_type === 'customer_order') {
            $order = \App\Models\CustomerOrder::with('customer')->find($releaseMaterial->order_id);
            if ($order) {
                $orderDetails = [
                    'type' => 'Customer Order',
                    'name' => $order->name,
                    'customer' => $order->customer->name ?? 'Unknown',
                    'wanted_date' => $order->wanted_delivery_date,
                ];
            }
        } elseif ($releaseMaterial->order_type === 'sample_order') {
            $order = \App\Models\SampleOrder::with('customer')->find($releaseMaterial->order_id);
            if ($order) {
                $orderDetails = [
                    'type' => 'Sample Order',
                    'name' => $order->name,
                    'customer' => $order->customer->name ?? 'Unknown',
                    'wanted_date' => $order->wanted_delivery_date,
                ];
            }
        }

        $pdf = Pdf::loadView('pdf.release-material-report', [
            'releaseMaterial' => $releaseMaterial,
            'companyDetails' => $companyDetails,
            'lines' => $lines,
            'cuttingStation' => $cuttingStation,
            'orderDetails' => $orderDetails,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("material-release-{$releaseMaterial->id}.pdf");
    }
}
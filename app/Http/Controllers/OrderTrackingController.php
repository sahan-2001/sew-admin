<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\PurchaseOrder;

class OrderTrackingController extends Controller
{
    // Show the tracking form
    public function index()
    {
        return view('track-order'); // your Blade view
    }

    // Handle form submission
    public function track(Request $request)
{
    $request->validate([
        'order_type' => 'required|in:customer_order,sample_order,purchase_order',
        'order_id' => 'required',
        'random_code' => 'required',
    ]);

    $orderId = ltrim($request->order_id, '0'); // remove leading zeros
    $securityCode = $request->random_code;

    $error = null;

    switch ($request->order_type) {
        case 'customer_order':
            $order = CustomerOrder::where('order_id', $orderId)
                        ->where('random_code', $securityCode)
                        ->first();
            if (!$order) {
                $error = 'Customer Order not found or invalid Security Code.';
            } else {
                return redirect()->route('customer-orders.show', [
                    'id' => $order->id,
                    'random_code' => $order->random_code
                ]);
            }
            break;

        case 'sample_order':
            $order = SampleOrder::where('order_id', $orderId)
                        ->where('random_code', $securityCode)
                        ->first();
            if (!$order) {
                $error = 'Sample Order not found or invalid Security Code.';
            } else {
                return redirect()->route('tracking.sample-orders.show', [
                    'id' => $order->id,
                    'random_code' => $order->random_code
                ]);
            }
            break;

        case 'purchase_order':
            $order = PurchaseOrder::where('id', $orderId)
                        ->where('random_code', $securityCode)
                        ->first();
            if (!$order) {
                $error = 'Purchase Order not found or invalid Security Code.';
            } else {
                return redirect()->route('purchase-order.show', [
                    'id' => $order->id,
                    'random_code' => $order->random_code
                ]);
            }
            break;
    }

    // If order not found, stay on the same page and show error
    return view('track-order', ['error' => $error]);
}

}

<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function show($orderId)
    {
        // Load the order along with its customer, order items, and variation items
        $order = CustomerOrder::with('customer', 'orderItems', 'orderItems.variationItems')->findOrFail($orderId);

        // Calculate the grand total by summing up the total of all order items and variations
        $grandTotal = $order->orderItems->sum(function ($item) {
            return $item->total + $item->variationItems->sum('total');
        });

        // Pass the data to the view
        return view('customer-order.show', [
            'order' => $order,
            'orderDescriptions' => $order->orderItems,
            'grandTotal' => $grandTotal,
            'printedBy' => auth()->user()->id,
        ]);
    }
}

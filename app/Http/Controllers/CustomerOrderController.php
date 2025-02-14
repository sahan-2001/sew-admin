<?php

use App\Models\CustomerOrder;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function store(Request $request)
    {
        // The logic you posted here
        $data = $request->all(); // or custom validation if needed
        $customerOrder = CustomerOrder::create($data);

        // Handle order descriptions and variations (same as in your code)
        foreach ($data['order_items'] as $itemData) {
            // Add logic to save order items and variations as needed
        }

        return redirect()->route('customer_orders.index');
    }
}

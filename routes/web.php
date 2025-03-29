<?php

use Illuminate\Support\Facades\Route;
<<<<<<< Updated upstream
=======
use App\Http\Controllers\PurchaseOrderPdfController;
use App\Filament\Resources\ActivityLogResource;
use Filament\Facades\Filament; 
use App\Models\CustomerOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\SampleOrderController;
use App\Http\Controllers\PurchaseOrderController;

>>>>>>> Stashed changes

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
<<<<<<< Updated upstream
=======

Route::get('/purchase-order/{purchase_order}/pdf', [PurchaseOrderPdfController::class, 'show'])->name('purchase-order.pdf');

Route::middleware(['auth', 'verified'])->group(function () {
    ActivityLogResource::routes(Filament::getCurrentPanel());
});

Route::get('/customer-orders/{order}/pdf', function (CustomerOrder $order) {
    // Load the order along with its order items and variations
    $orderDescriptions = $order->orderItems()->with('variationItems')->get();

    // Calculate the grand total by summing up the total of all order items and variations
    $grandTotal = $order->orderItems->sum(function ($item) {
        return $item->total + $item->variationItems->sum('total');
    });

    // Create the PDF and pass the data to the view
    $pdf = Pdf::loadView('pdf.customer_order', [
        'order' => $order,
        'orderDescriptions' => $orderDescriptions,
        'grandTotal' => $grandTotal,
        'printedBy' => auth()->user()->id,
    ]);

    // Return the PDF as a response to stream it
    return $pdf->stream('customer_order.pdf');
})->name('customer-orders.pdf');


Route::get('/sample-orders/{sampleOrder}/pdf', function (SampleOrder $sampleOrder) {
    $orderDescriptions = $sampleOrder->items()->with('variations')->get();
    $grandTotal = $sampleOrder->items->sum(function ($item) {
        return $item->total + $item->variations->sum('total');
    });

    $pdf = Pdf::loadView('pdf.sample_order', [
        'sampleOrder' => $sampleOrder,
        'orderDescriptions' => $orderDescriptions,
        'grandTotal' => $grandTotal,
        'printedBy' => auth()->user()->email,
    ]);

    return $pdf->stream('sample_order.pdf');
})->name('sample-orders.pdf');

// Generate and display the QR code
Route::get('purchase-order/{purchase_order}/qr-code', [PurchaseOrderController::class, 'generateQrCode'])
    ->name('generate.qr');

>>>>>>> Stashed changes

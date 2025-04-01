<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseOrderPdfController;
use App\Filament\Resources\ActivityLogResource;
use Filament\Facades\Filament; 
use App\Models\CustomerOrder;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\SampleOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PoFrontendController;
use App\Http\Controllers\PageController;
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

Route::get('/welcome', [PageController::class, 'index'])->name('welcome');

Route::get('/purchase-order/{purchase_order}/pdf', [PurchaseOrderPdfController::class, 'show'])->name('purchase-order.pdf');

Route::middleware(['auth', 'verified'])->group(function () {
    ActivityLogResource::routes(Filament::getCurrentPanel());
});

// Download the QR code
Route::get('/purchase-order/{purchase_order}/qr-code/download', [PurchaseOrderController::class, 'downloadQrCode'])
    ->name('purchase-order.qr-code.download');

Route::get('/purchase-orders/{purchase_order}', [PurchaseOrderPdfController::class, 'show'])
    ->name('purchase-orders.show');


// Frontend route
Route::get('/purchase-order/{id}', [POFrontendController::class, 'showPurchaseOrder'])->name('purchase-order.show');

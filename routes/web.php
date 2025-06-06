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
use App\Http\Controllers\SampleOrderPdfController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PoFrontendController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CuttingRecordPrintController;


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
Route::get('/sample-orders/{sample_order}/pdf', [SampleOrderPdfController::class, 'show'])->name('sample-orders.pdf');
Route::get('/cutting-records/{cutting_record}/print', [CuttingRecordPrintController::class, 'print'])->name('cutting-records.print');

Route::middleware(['auth', 'verified'])->group(function () {
    ActivityLogResource::routes(Filament::getCurrentPanel());
});


// Frontend route
Route::get('/purchase-order/{id}', [POFrontendController::class, 'showPurchaseOrder'])->name('purchase-order.show');


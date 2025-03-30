<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; 
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use App\Models\PurchaseOrder;

class PurchaseOrderController extends Controller
{
    public function downloadQrCode(PurchaseOrder $purchase_order)
    {
        $path = 'public/qrcodes/' . $purchase_order->qr_code;
    
        if (!Storage::exists($path)) {
            abort(404, 'QR Code not found.');
        }
    
        return Storage::download($path, $purchase_order->qr_code, [
            'Content-Type' => 'image/png',
        ]);
    }

}

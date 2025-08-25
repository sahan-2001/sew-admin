<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class PurchaseOrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $companyDetails;
    public $items;
    public $qrCodePath;
    public $qrCodeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseOrder $order)
    {
        $this->order = $order->load([
            'items.inventoryItem', 
            'items',           
            'invoice',          
            'supplierAdvanceInvoices',
        ]);

        // Purchase order items
        $this->items = $this->order->items;

        // Fetch company details (first record in company table)
        $company = Company::first();
        $this->companyDetails = $company ? [
            'name'    => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone'   => $company->primary_phone ?? 'N/A',
            'email'   => $company->email ?? 'N/A',
        ] : [];

        // Generate QR code URL
        $this->qrCodeUrl = url('/purchase-order/' . $this->order->id . '/' . $this->order->random_code);

        // Generate and save QR code as SVG in storage
        $qrCode = new QrCode($this->qrCodeUrl);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        $qrCodeFilename = 'purchase_qrcode_' . $this->order->id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        \Storage::makeDirectory('public/qrcodes');
        \Storage::put($path, $result->getString());

        $this->qrCodePath = storage_path('app/' . $path);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Purchase Order Confirmation')
                    ->view('emails.purchase-orders.created')
                    ->with([
                        'order'        => $this->order,
                        'companyDetails' => $this->companyDetails,
                        'items'        => $this->items,
                        'qrCodeUrl'    => $this->qrCodeUrl,
                        'qrCodePath'   => $this->qrCodePath,
                    ]);
    }
}

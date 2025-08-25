<?php

namespace App\Mail;

use App\Models\CustomerOrder;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class CustomerOrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $companyDetails;
    public $customer;
    public $items;
    public $qrCodePath;
    public $qrCodeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerOrder $order)
    {
        $this->order = $order->load([
            'customer',
            'orderItems.variationItems', // eager-load items and variations
        ]);

        // Related customer
        $this->customer = $this->order->customer;

        // Order items with variations
        $this->items = $this->order->orderItems;

        // Fetch company details (first record)
        $company = Company::first();
        $this->companyDetails = $company ? [
            'name'    => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone'   => $company->primary_phone ?? 'N/A',
            'email'   => $company->email ?? 'N/A',
        ] : [];

        // Generate QR code URL (order ID + random code)
        $this->qrCodeUrl = url('/customer-orders/' . $this->order->order_id . '/' . $this->order->random_code);

        // Generate and save QR code as SVG
        $qrCode = new QrCode($this->qrCodeUrl);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        $qrCodeFilename = 'qrcode_customer_' . $this->order->order_id . '.svg';
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
        return $this->subject('New Customer Order Confirmation')
                    ->view('emails.customer-orders.created')
                    ->with([
                        'qrCodeUrl' => $this->qrCodeUrl,
                        'qrCodePath' => $this->qrCodePath,
                    ]);
    }
}

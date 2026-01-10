<?php

namespace App\Mail;

use App\Models\RequestForQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestForQuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rfq;

    public function __construct(RequestForQuotation $rfq)
    {
        $this->rfq = $rfq->load('items.inventoryItem', 'supplier');
    }

    public function build()
    {
        return $this->subject('New Request for Quotation')
                    ->view('emails.request-for-quotation.rfqs-sent')
                    ->with([
                        'rfq' => $this->rfq,
                    ]);
    }
}

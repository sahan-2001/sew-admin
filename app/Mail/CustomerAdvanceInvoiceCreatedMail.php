<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\CustomerAdvanceInvoice;
use App\Models\Company;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\Customer;

class CustomerAdvanceInvoiceCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $customer;
    public $order;          
    public $companyDetails;

    public function __construct(CustomerAdvanceInvoice $invoice)
    {
        $this->invoice = $invoice;

        // Load the related order depending on type
        if ($invoice->order_type === 'customer') {
            $this->order = CustomerOrder::with(['orderItems', 'customer'])->find($invoice->order_id);
        } elseif ($invoice->order_type === 'sample') {
            $this->order = SampleOrder::with(['items', 'customer'])->find($invoice->order_id);
        } else {
            $this->order = null;
        }

        // Load customer either from order or fallback to invoice relation
        $this->customer = $this->order
            ? $this->order->customer
            : Customer::find($invoice->customer_id);

        // Company details
        $company = Company::first();
        $this->companyDetails = $company ? [
            'name' => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ] : [];
    }

    public function build()
    {
        return $this->subject('Customer Advance Invoice Confirmation')
                    ->view('emails.customer-advance-invoice.created')
                    ->with([
                        'invoice' => $this->invoice,
                        'order' => $this->order,
                        'customer' => $this->customer,
                        'companyDetails' => $this->companyDetails,
                    ]);
    }
}

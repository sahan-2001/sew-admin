<?php

namespace App\Mail;

use App\Models\Supplier;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplierCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $supplier;
    public $companyDetails;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;

        // Fetch company details here
        $company = Company::first();
        $this->companyDetails = $company ? [
            'name'    => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone'   => $company->primary_phone ?? 'N/A',
            'email'   => $company->email ?? 'N/A',
        ] : [];
    }

    public function build()
    {
        return $this->subject('Welcome to Tailor Trail ERP')
                    ->view('emails.suppliers.created');
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $companyDetails;

    public function __construct(User $user)
    {
        $this->user = $user;

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
        return $this->subject('Welcome to '.$this->companyDetails['name'] ?? 'Our Company')
                    ->view('emails.users.created');
    }
}

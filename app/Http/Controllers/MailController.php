<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    /**
     * Get company details
     */
    private function getCompanyDetails(): array
    {
        $company = Company::first();
        if (!$company) {
            abort(500, 'Company details not found.');
        }

        return [
            'name'    => $company->name,
            'address' => "{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}",
            'phone'   => $company->primary_phone ?? 'N/A',
            'email'   => $company->email ?? 'N/A',
        ];
    }

    /**
     * Send any email with automatic company details
     *
     * @param object $recipient - model with 'email' and 'name'
     * @param string $template  - Blade template path (e.g., 'emails.supplier-welcome')
     * @param string $subject   - Email subject
     * @param array  $data      - Any other dynamic data required by template
     */
    public function sendMail(object $recipient, string $template, string $subject, array $data = [])
    {
        // Add company details automatically
        $data['companyDetails'] = $this->getCompanyDetails();

        Mail::send($template, $data, function ($message) use ($recipient, $subject) {
            $message->to($recipient->email, $recipient->name)
                    ->subject($subject);
        });

        return response()->json([
            'message' => "Email sent successfully to {$recipient->email}"
        ]);
    }
}

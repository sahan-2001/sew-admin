<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class EmployeeOfferLetterController extends Controller
{
    public function generate(Employee $employee)
    {
        // Fetch company details
        $company = Company::first();
        if (!$company) {
            return abort(500, 'Company details not found.');
        }

        $companyDetails = [
            'name' => $company->name,
            'address' => trim("{$company->address_line_1}, {$company->address_line_2}, {$company->address_line_3}, {$company->city}, {$company->country}, {$company->postal_code}", ', '),
            'phone' => $company->primary_phone ?? 'N/A',
            'email' => $company->email ?? 'N/A',
        ];

        // Employee details
        $employeeDetails = [
            'id' => $employee->id,
            'code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'designation' => $employee->designation,
            'department' => $employee->department,
            'joined_date' => $employee->joined_date,
            'basic_salary' => $employee->basic_salary,
        ];

        // Generate QR Code (e.g., link to employee profile)
        $qrCodeData = url('/employees/' . $employee->id);
        $qrCode = new QrCode($qrCodeData);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        $qrCodeFilename = 'employee_qrcode_' . $employee->id . '.svg';
        $path = 'public/qrcodes/' . $qrCodeFilename;

        Storage::makeDirectory('public/qrcodes');
        Storage::put($path, $result->getString());

        // Generate and return the PDF
        return Pdf::loadView('pdf.employee-offer-letter', [
            'companyDetails' => $companyDetails,
            'employeeDetails' => $employeeDetails,
            'qrCodePath' => storage_path('app/public/qrcodes/' . $qrCodeFilename),
            'qrCodeData' => $qrCodeData,
        ])
        ->setPaper('a4')
        ->stream('offer-letter-' . $employee->employee_code . '.pdf');
    }
}

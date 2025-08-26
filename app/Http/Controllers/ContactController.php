<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Mail::to('sahandilshan1021@gmail.com')->send(
            new ContactFormMail(
                $request->name, 
                $request->email, 
                $request->subject, 
                $request->message
            )
        );

        return redirect()->back()->with([
            'success' => 'Your message has been sent!',
            'emailDetails' => [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
            ]
        ]);
    }
}

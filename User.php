<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Http\Request;

class ContactMessage extends Mailable
{
    public $data;

    public function __construct(Request $request)
    {
        $this->data = $request;
    }

    public function build()
    {
        return $this->subject($this->data->subject)
                    ->view('emails.contact');
    }
}
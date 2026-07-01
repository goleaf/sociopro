<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;

    public $email;

    public $subject;

    public $details;

    /**
     * @param  string  $name
     * @param  string  $email
     * @param  string  $subject
     * @param  string  $details
     */
    public function __construct($name, $email, $subject, $details)
    {
        $this->name = $name;
        $this->email = $email;
        $this->subject = $subject;
        $this->details = $details;
    }

    /**
     * @return $this
     */
    public function build()
    {
        return $this->from($this->email)->subject($this->subject)->markdown('emails.contact');
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $email;

    /**
     * @var string
     */
    public $subject;

    public string $details;

    public function __construct(string $name, string $email, string $subject, string $details)
    {
        $this->name = $name;
        $this->email = $email;
        $this->subject = $subject;
        $this->details = $details;
    }

    public function build(): static
    {
        return $this
            ->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->replyTo($this->email, $this->name)
            ->subject($this->subject)
            ->markdown('emails.contact');
    }
}

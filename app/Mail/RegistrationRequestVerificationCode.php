<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationRequestVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $verificationCode,
        public readonly string $verificationUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('Verify your BrakeSafe access request')
            ->view('emails.registration-request-verification');
    }
}

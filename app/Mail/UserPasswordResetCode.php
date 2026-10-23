<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserPasswordResetCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $verificationCode,
    ) {}

    public function build(): self
    {
        return $this->subject('Your BrakeSafe password reset code')
            ->view('emails.user-password-reset-code');
    }
}

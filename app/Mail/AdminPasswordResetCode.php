<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminPasswordResetCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $adminName,
    ) {}

    public function build(): self
    {
        return $this->subject('Your BrakeSafe password reset code')
            ->view('emails.admin-password-reset-code');
    }
}

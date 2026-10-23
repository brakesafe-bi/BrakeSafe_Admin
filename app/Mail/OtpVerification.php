<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerification extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $full_name;

    public function __construct(string $otp, string $full_name)
    {
        $this->otp = $otp;
        $this->full_name = $full_name;
    }

    public function build()
    {
        return $this->subject('BrakeSafe Admin OTP Verification')
            ->view('emails.otp-verification')
            ->with([
                'otp' => $this->otp,
                'full_name' => $this->full_name,
            ]);
    }
}

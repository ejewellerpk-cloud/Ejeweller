<?php

namespace App\Mail;

use App\Mail\Concerns\UsesProfessionalMailHeaders;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels, UsesProfessionalMailHeaders;

    public $pin;

    public function __construct($pin)
    {
        $this->pin = $pin;
    }

    public function build()
    {
        $appName = config('app.name');

        return $this
            ->subject("Email verification code | {$appName}")
            ->view('emails.verifyEmail')
            ->text('emails.text.verifyEmail')
            ->with(['pin' => $this->pin])
            ->applyProfessionalHeaders();
    }
}

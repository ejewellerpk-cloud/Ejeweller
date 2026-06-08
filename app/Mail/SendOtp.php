<?php

namespace App\Mail;

use App\Mail\Concerns\UsesProfessionalMailHeaders;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtp extends Mailable
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
            ->subject("Password reset verification code | {$appName}")
            ->view('emails.password')
            ->text('emails.text.password')
            ->with(['pin' => $this->pin])
            ->applyProfessionalHeaders();
    }
}

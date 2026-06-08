<?php

namespace App\Mail;

use App\Mail\Concerns\UsesProfessionalMailHeaders;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels, UsesProfessionalMailHeaders;

    public string $name;
    public string|int $orderId;
    public mixed $message;

    public function __construct($name, $orderId, $message)
    {
        $this->name    = $name;
        $this->orderId = $orderId;
        $this->message = $message;
    }

    public function build()
    {
        $appName = config('app.name');

        return $this
            ->subject("Order update for #{$this->orderId} | {$appName}")
            ->view('emails.order')
            ->text('emails.text.order')
            ->with([
                'name' => $this->name,
                'orderId' => $this->orderId,
                'message' => $this->message,
            ])
            ->applyProfessionalHeaders();
    }
}

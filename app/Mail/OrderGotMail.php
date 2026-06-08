<?php

namespace App\Mail;

use App\Mail\Concerns\UsesProfessionalMailHeaders;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderGotMail extends Mailable
{
    use Queueable, SerializesModels, UsesProfessionalMailHeaders;

    public object $order;
    public mixed $message;

    public function __construct($order, $message)
    {
        $this->order = $order;
        $this->message = $message;
    }

    public function build()
    {
        $appName = config('app.name');

        return $this
            ->subject("New order #{$this->order->order_serial_no} received | {$appName}")
            ->view('emails.orderGot')
            ->text('emails.text.orderGot')
            ->with([
                'order' => $this->order,
                'message' => $this->message,
            ])
            ->applyProfessionalHeaders();
    }
}

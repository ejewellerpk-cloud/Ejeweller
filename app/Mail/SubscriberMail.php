<?php

namespace App\Mail;

use App\Mail\Concerns\UsesProfessionalMailHeaders;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriberMail extends Mailable
{
    use Queueable, SerializesModels, UsesProfessionalMailHeaders;

    public string $title;
    public mixed $message;

    public function __construct($title, $message)
    {
        $this->title = $title;
        $this->message = $message;
    }

    public function build()
    {
        $appName = config('app.name');
        $subject = trim($this->title) !== '' ? $this->title : "Update from {$appName}";

        return $this
            ->subject("{$subject} | {$appName}")
            ->view('emails.subscriber')
            ->text('emails.text.subscriber')
            ->with([
                'title' => $this->title,
                'message' => $this->message,
            ])
            ->applyProfessionalHeaders();
    }
}

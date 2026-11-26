<?php

namespace App\Mail;

use App\Mail\Concerns\UsesProfessionalMailHeaders;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CartRecoveryMail extends Mailable
{
    use Queueable, SerializesModels, UsesProfessionalMailHeaders;

    public function __construct(
        public string $title,
        public string $mailBody,
        public string $restoreUrl,
        public float $discountPercent = 0,
    ) {}

    public function build()
    {
        $appName = config('app.name');
        $subject = trim($this->title) !== '' ? $this->title : "Complete your order | {$appName}";

        $extra = $this->discountPercent > 0
            ? "\n\nUse discount: {$this->discountPercent}% off when you return."
            : '';

        return $this
            ->subject("{$subject} | {$appName}")
            ->view('emails.subscriber')
            ->text('emails.text.subscriber')
            ->with([
                'title' => $this->title,
                'mailBody' => $this->mailBody . $extra . "\n\n" . $this->restoreUrl,
            ])
            ->applyProfessionalHeaders();
    }
}

<?php

namespace App\Jobs;

use App\Services\CartRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCartRecoveryMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $subject,
        public string $body,
        public string $restoreUrl,
        public float $discountPercent = 0,
    ) {}

    public function handle(CartRecoveryService $recovery): void
    {
        $recovery->sendNow(
            $this->email,
            $this->subject,
            $this->body,
            $this->restoreUrl,
            $this->discountPercent
        );
    }
}

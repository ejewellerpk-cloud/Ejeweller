<?php

namespace App\Exceptions;

use Exception;

class PostExApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $statusCode = null,
        public readonly ?string $statusMessage = null,
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }
}

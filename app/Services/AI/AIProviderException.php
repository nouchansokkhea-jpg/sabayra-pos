<?php

namespace App\Services\AI;

use RuntimeException;
use Throwable;

class AIProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}

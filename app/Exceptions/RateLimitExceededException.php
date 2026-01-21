<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds = 60
    ) {
        parent::__construct($message);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}

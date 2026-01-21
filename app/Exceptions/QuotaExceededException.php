<?php

namespace App\Exceptions;

use Exception;

class QuotaExceededException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $quotaType = 'personal'
    ) {
        parent::__construct($message);
    }

    public function getQuotaType(): string
    {
        return $this->quotaType;
    }
}

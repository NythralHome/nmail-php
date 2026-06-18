<?php

declare(strict_types=1);

namespace Nythral\Nmail;

final class NmailException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly string $errorCode = 'request_failed',
        public readonly mixed $details = null,
    ) {
        parent::__construct($message, $status);
    }

    public function retryable(): bool
    {
        return in_array($this->status, [502, 503, 504], true);
    }
}

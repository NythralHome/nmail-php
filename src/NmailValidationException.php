<?php

declare(strict_types=1);

namespace Nythral\Nmail;

final class NmailValidationException extends \InvalidArgumentException
{
    public readonly string $errorCode;

    public function __construct(
        string $message,
        public readonly string $field,
    ) {
        $this->errorCode = 'validation_failed';
        parent::__construct($message);
    }
}

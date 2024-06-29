<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Collector\Exception;

use RuntimeException;
use Throwable;

final class CompositeException extends RuntimeException
{
    public function __construct(
        /** @var list<Throwable> */
        private readonly array $exceptions,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return list<Throwable> */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}

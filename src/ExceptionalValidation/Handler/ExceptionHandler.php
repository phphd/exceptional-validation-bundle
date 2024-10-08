<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Handler;

use PhPhD\ExceptionalValidation\Handler\Exception\ExceptionalValidationFailedException;
use Throwable;

/** @api */
interface ExceptionHandler
{
    /** @throws ExceptionalValidationFailedException if all the exceptions were matched to the message; returns void otherwise */
    public function capture(object $message, Throwable $exception): void;
}

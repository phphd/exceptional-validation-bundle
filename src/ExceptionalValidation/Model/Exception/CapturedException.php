<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Exception;

use PhPhD\ExceptionalValidation\Model\Rule\CaptureExceptionRule;
use Throwable;

/** @api */
final class CapturedException
{
    public function __construct(
        private readonly Throwable $exception,
        private readonly CaptureExceptionRule $matchedRule,
    ) {
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    /** @internal */
    public function getMatchedRule(): CaptureExceptionRule
    {
        return $this->matchedRule;
    }
}

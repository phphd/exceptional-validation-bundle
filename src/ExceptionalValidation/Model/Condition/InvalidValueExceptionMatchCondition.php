<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Condition;

use PhPhD\ExceptionalValidation\Model\Condition\Exception\InvalidValueException;
use Throwable;

/** @internal */
final class InvalidValueExceptionMatchCondition implements MatchCondition
{
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    public function matches(Throwable $exception): bool
    {
        if (!$exception instanceof InvalidValueException) {
            return false;
        }

        return $exception->getInvalidValue() === $this->value;
    }
}

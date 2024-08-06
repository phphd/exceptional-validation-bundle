<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Condition;

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

/** @internal */
final class MatchByValidatedValueCondition implements MatchCondition
{
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    public function matches(Throwable $exception): bool
    {
        if (!$exception instanceof ValidationFailedException) {
            return false;
        }

        return $exception->getValue() === $this->value;
    }
}

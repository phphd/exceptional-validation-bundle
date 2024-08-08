<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Condition;

use Closure;
use Throwable;

/** @internal */
final class ClosureMatchCondition implements MatchCondition
{
    public function __construct(
        private readonly Closure $condition,
    ) {
    }

    public function matches(Throwable $exception): bool
    {
        return ($this->condition)($exception);
    }
}

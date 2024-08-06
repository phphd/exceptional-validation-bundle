<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\ConditionFactory;

use LogicException;
use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Model\Condition\MatchByExceptionClassCondition;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;
use Throwable;

/** @internal */
final class MatchByExceptionClassConditionFactory implements MatchConditionFactory
{
    public function getCondition(Capture $capture, CaptureRule $parent): MatchCondition
    {
        $exceptionClass = $capture->getExceptionClass();

        if (!is_a($exceptionClass, Throwable::class, true)) {
            throw new LogicException('Exception class condition could only be used for exception class that implements Throwable');
        }

        return new MatchByExceptionClassCondition($exceptionClass);
    }
}

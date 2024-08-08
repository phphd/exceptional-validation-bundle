<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\ConditionFactory;

use LogicException;
use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Model\Condition\ExceptionClassMatchCondition;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;
use Throwable;

use function is_a;

/** @internal */
final class ExceptionClassMatchConditionFactory implements MatchConditionFactory
{
    public function getCondition(Capture $capture, CaptureRule $parent): MatchCondition
    {
        $exceptionClass = $capture->getExceptionClass();

        if (!is_a($exceptionClass, Throwable::class, true)) {
            throw new LogicException('Exception class condition should only be used for exception classes that implements Throwable');
        }

        return new ExceptionClassMatchCondition($exceptionClass);
    }
}

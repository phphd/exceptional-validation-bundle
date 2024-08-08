<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\ConditionFactory;

use LogicException;
use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Model\Condition\Exception\InvalidValueException;
use PhPhD\ExceptionalValidation\Model\Condition\InvalidValueExceptionMatchCondition;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;

use function is_a;

/** @internal */
final class InvalidValueExceptionMatchConditionFactory implements MatchConditionFactory
{
    public function getCondition(Capture $capture, CaptureRule $parent): MatchCondition
    {
        $exceptionClass = $capture->getExceptionClass();

        if (!is_a($exceptionClass, InvalidValueException::class, true)) {
            throw new LogicException('Invalid value condition could only be used for exception class that implements InvalidValueException');
        }

        $value = $parent->getValue();

        return new InvalidValueExceptionMatchCondition($value);
    }
}

<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\ConditionFactory;

use LogicException;
use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Model\Condition\MatchByValidatedValueCondition;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/** @internal */
final class MatchByValidatedValueConditionConditionFactory implements MatchConditionFactory
{
    public function getCondition(Capture $capture, CaptureRule $parent): ?MatchCondition
    {
        $exceptionClass = $capture->getExceptionClass();

        if (!is_a($exceptionClass, ValidationFailedException::class, true)) {
            throw new LogicException('Validated value condition could only be used for exception class that implements ValidationFailedException');
        }

        $value = $parent->getValue();

        return new MatchByValidatedValueCondition($value);
    }
}

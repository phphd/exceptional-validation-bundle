<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\ConditionFactory;

use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Model\Condition\ClosureMatchCondition;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;

/** @internal */
final class ClosureMatchConditionFactory implements MatchConditionFactory
{
    public function getCondition(Capture $capture, CaptureRule $parent): ?MatchCondition
    {
        $when = $capture->getWhen();

        if (null === $when) {
            return null;
        }

        $object = $parent->getEnclosingObject();

        if ($when[0] === $object::class) {
            $when = [$object, $when[1]];
        }

        /** @phpstan-ignore-next-line */
        return new ClosureMatchCondition($when(...));
    }
}

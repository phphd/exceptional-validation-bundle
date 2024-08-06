<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\ConditionFactory;

use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;

/** @internal */
interface MatchConditionFactory
{
    public function getCondition(Capture $capture, CaptureRule $parent): ?MatchCondition;
}

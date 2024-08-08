<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules;

use ArrayIterator;
use PhPhD\ExceptionalValidation\Assembler\CaptureRuleSetAssembler;
use PhPhD\ExceptionalValidation\Assembler\CaptureRuleSetAssemblerEnvelope;
use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\ConditionFactory\MatchConditionFactory;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureExceptionRule;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;
use PhPhD\ExceptionalValidation\Model\Rule\CompositeRuleSet;
use Webmozart\Assert\Assert;

/**
 * @internal
 *
 * @implements CaptureRuleSetAssembler<PropertyRulesAssemblerEnvelope>
 */
final class PropertyCaptureRulesAssembler implements CaptureRuleSetAssembler
{
    public function __construct(
        private readonly MatchConditionFactory $conditionFactory,
    ) {
    }

    /** @param PropertyRulesAssemblerEnvelope $envelope */
    public function assemble(CaptureRule $parent, CaptureRuleSetAssemblerEnvelope $envelope): ?CompositeRuleSet
    {
        $rules = new ArrayIterator();
        $ruleSet = new CompositeRuleSet($parent, $rules);

        $captureAttributes = $envelope
            ->getReflectionProperty()
            ->getAttributes(Capture::class)
        ;

        foreach ($captureAttributes as $captureAttribute) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             *
             * @var Capture $capture
             */
            $capture = $captureAttribute->newInstance();

            $rules->append(new CaptureExceptionRule(
                $ruleSet,
                $this->getCondition($capture, $parent),
                $capture->getMessage(),
                $capture->getFormatter(),
            ));
        }

        if (0 === $rules->count()) {
            return null;
        }

        return $ruleSet;
    }

    private function getCondition(Capture $capture, CaptureRule $parent): MatchCondition
    {
        $matchCondition = $this->conditionFactory->getCondition($capture, $parent);

        Assert::notNull($matchCondition);

        return $matchCondition;
    }
}

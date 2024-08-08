<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\ConditionFactory;

use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Model\Condition\CompositeMatchCondition;
use PhPhD\ExceptionalValidation\Model\Condition\MatchCondition;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;
use Psr\Container\ContainerInterface;

use function array_filter;
use function array_values;

/**
 * @internal
 *
 * @api
 */
final class CaptureMatchConditionFactory implements MatchConditionFactory
{
    public function __construct(
        private readonly ContainerInterface $conditionFactoryRegistry,
        private readonly MatchConditionFactory $matchByExceptionClassConditionFactory = new ExceptionClassMatchConditionFactory(),
        private readonly MatchConditionFactory $matchWithClosureConditionFactory = new ClosureMatchConditionFactory(),
    ) {
    }

    public function getCondition(Capture $capture, CaptureRule $parent): MatchCondition
    {
        $conditions = [];

        $conditions[] = $this->matchByExceptionClassConditionFactory->getCondition($capture, $parent);
        $conditions[] = $this->getConditionFromRegistry($capture, $parent);
        $conditions[] = $this->matchWithClosureConditionFactory->getCondition($capture, $parent);

        return (new CompositeMatchCondition(array_values(array_filter($conditions))))->compile();
    }

    private function getConditionFromRegistry(Capture $capture, CaptureRule $parent): ?MatchCondition
    {
        $conditionFactoryId = $capture->getCondition();

        if (null === $conditionFactoryId) {
            return null;
        }

        /** @var MatchConditionFactory $conditionFactory */
        $conditionFactory = $this->conditionFactoryRegistry->get($conditionFactoryId);

        return $conditionFactory->getCondition($capture, $parent);
    }
}

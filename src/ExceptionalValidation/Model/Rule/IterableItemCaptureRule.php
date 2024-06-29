<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Rule;

use LogicException;
use PhPhD\ExceptionalValidation\Model\Exception\ExceptionPackage;
use PhPhD\ExceptionalValidation\Model\ValueObject\PropertyPath;

use function is_object;

final class IterableItemCaptureRule implements CaptureRule
{
    public function __construct(
        private readonly int|string $key,
        private readonly CaptureRule $parent,
        private readonly CaptureRule $objectRuleSet,
    ) {
    }

    public function process(ExceptionPackage $exceptions): bool
    {
        return $this->objectRuleSet->process($exceptions);
    }

    public function getPropertyPath(): PropertyPath
    {
        return $this->parent->getPropertyPath()->at($this->key);
    }

    public function getEnclosingObject(): object
    {
        return $this->parent->getEnclosingObject();
    }

    public function getRoot(): object
    {
        return $this->parent->getRoot();
    }

    public function getValue(): object
    {
        $object = $this->objectRuleSet->getValue();

        if (!is_object($object)) {
            throw new LogicException('Object rule set must have returned an object as the value.');
        }

        return $object;
    }
}

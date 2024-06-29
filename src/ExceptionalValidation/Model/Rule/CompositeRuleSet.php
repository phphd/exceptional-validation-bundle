<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Rule;

use PhPhD\ExceptionalValidation\Model\Exception\ExceptionPackage;
use PhPhD\ExceptionalValidation\Model\ValueObject\PropertyPath;

/** @internal */
final class CompositeRuleSet implements CaptureRule
{
    public function __construct(
        private readonly CaptureRule $parent,
        /** @var iterable<CaptureRule> $rules */
        private readonly iterable $rules,
    ) {
    }

    public function process(ExceptionPackage $exceptions): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->process($exceptions)) {
                return true;
            }
        }

        return false;
    }

    public function getPropertyPath(): PropertyPath
    {
        return $this->parent->getPropertyPath();
    }

    public function getEnclosingObject(): object
    {
        return $this->parent->getEnclosingObject();
    }

    public function getRoot(): object
    {
        return $this->parent->getRoot();
    }

    public function getValue(): mixed
    {
        return $this->parent->getValue();
    }
}

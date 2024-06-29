<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Rule;

use PhPhD\ExceptionalValidation\Model\Exception\ExceptionPackage;
use PhPhD\ExceptionalValidation\Model\ValueObject\PropertyPath;

interface CaptureRule
{
    public function process(ExceptionPackage $exceptions): bool;

    public function getPropertyPath(): PropertyPath;

    public function getEnclosingObject(): object;

    public function getRoot(): object;

    public function getValue(): mixed;
}

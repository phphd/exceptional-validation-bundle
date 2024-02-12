<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model;

use PhPhD\ExceptionalValidation\Model\ValueObject\CaughtException;
use PhPhD\ExceptionalValidation\Model\ValueObject\PropertyPath;
use Throwable;

interface CaptureRule
{
    /** @return list<CaughtException> */
    public function capture(Throwable $exception): array;

    public function getPropertyPath(): PropertyPath;

    public function getRoot(): object;

    public function getValue(): mixed;
}

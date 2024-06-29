<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Formatter;

use PhPhD\ExceptionalValidation\Model\Exception\ProcessedException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ExceptionViolationListFormatter
{
    /** @param non-empty-list<ProcessedException> $processedExceptions */
    public function formatViolations(array $processedExceptions): ConstraintViolationListInterface;
}

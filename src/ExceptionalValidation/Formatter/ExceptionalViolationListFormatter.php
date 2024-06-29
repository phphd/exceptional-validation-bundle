<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Formatter;

use PhPhD\ExceptionalValidation\Model\Exception\ProcessedException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/** @internal */
final class ExceptionalViolationListFormatter implements ExceptionViolationListFormatter
{
    public function __construct(
        private readonly ExceptionViolationFormatter $violationFormatter,
    ) {
    }

    /** @param non-empty-list<ProcessedException> $processedExceptions */
    public function formatViolations(array $processedExceptions): ConstraintViolationListInterface
    {
        $violations = new ConstraintViolationList();

        foreach ($processedExceptions as $processedException) {
            $violation = $this->violationFormatter->formatViolation($processedException);

            $violations->add($violation);
        }

        return $violations;
    }
}

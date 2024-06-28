<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Formatter;

use PhPhD\ExceptionalValidation\Model\Exception\CaughtException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/** @internal */
final class ExceptionalViolationListFormatter implements ExceptionViolationListFormatter
{
    public function __construct(
        private readonly ExceptionViolationFormatter $violationFormatter,
    ) {
    }

    /** @param non-empty-list<CaughtException> $caughtExceptions */
    public function formatViolations(array $caughtExceptions): ConstraintViolationListInterface
    {
        $violations = new ConstraintViolationList();

        foreach ($caughtExceptions as $caughtException) {
            $violation = $this->violationFormatter->formatViolation($caughtException);

            $violations->add($violation);
        }

        return $violations;
    }
}

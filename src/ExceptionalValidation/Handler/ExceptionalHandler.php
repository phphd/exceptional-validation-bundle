<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Handler;

use PhPhD\ExceptionalValidation\Assembler\Object\ObjectRuleSetAssembler;
use PhPhD\ExceptionalValidation\Collector\ExceptionPackageCollector;
use PhPhD\ExceptionalValidation\Formatter\ExceptionViolationListFormatter;
use PhPhD\ExceptionalValidation\Handler\Exception\ExceptionalValidationFailedException;
use Throwable;

/** @internal */
final class ExceptionalHandler implements ExceptionHandler
{
    public function __construct(
        private readonly ObjectRuleSetAssembler $ruleSetAssembler,
        private readonly ExceptionPackageCollector $exceptionPackageCollector,
        private readonly ExceptionViolationListFormatter $violationsFormatter,
    ) {
    }

    public function capture(object $message, Throwable $exception): never
    {
        $ruleSet = $this->ruleSetAssembler->assemble($message);

        if (null === $ruleSet) {
            throw $exception;
        }

        $exceptionPackage = $this->exceptionPackageCollector->collect($exception);

        if (!$ruleSet->process($exceptionPackage)) {
            throw $exception;
        }

        $processedExceptions = $exceptionPackage->getProcessedExceptions();

        $violationList = $this->violationsFormatter->formatViolations($processedExceptions);

        throw new ExceptionalValidationFailedException($message, $violationList, $exception);
    }
}

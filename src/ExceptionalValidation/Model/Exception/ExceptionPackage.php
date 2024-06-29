<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Model\Exception;

use PhPhD\ExceptionalValidation\Model\Rule\CaptureExceptionRule;
use Throwable;
use Webmozart\Assert\Assert;

final class ExceptionPackage
{
    /** @var array<int,Throwable> */
    private array $remainingExceptions;

    /** @var list<ProcessedException> */
    private array $processedExceptions = [];

    /** @param list<Throwable> $thrownExceptions */
    public function __construct(array $thrownExceptions)
    {
        $this->remainingExceptions = $thrownExceptions;
    }

    /** @return non-empty-list<ProcessedException> */
    public function getProcessedExceptions(): array
    {
        Assert::notEmpty($this->processedExceptions);

        return $this->processedExceptions;
    }

    public function isProcessed(): bool
    {
        return [] === $this->remainingExceptions;
    }

    public function processRule(CaptureExceptionRule $rule): void
    {
        foreach ($this->remainingExceptions as $index => $exception) {
            if ($rule->matches($exception)) {
                $this->processException($index, $exception, $rule);

                return;
            }
        }
    }

    private function processException(int $index, Throwable $exception, CaptureExceptionRule $rule): void
    {
        unset($this->remainingExceptions[$index]);

        $this->processedExceptions[] = new ProcessedException($exception, $rule);
    }
}

<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Collector;

use PhPhD\ExceptionalValidation\Collector\Exception\CompositeException;
use PhPhD\ExceptionalValidation\Model\Exception\ExceptionPackage;
use Throwable;

/** @internal */
final class ExceptionalPackageCollector implements ExceptionPackageCollector
{
    public function collect(Throwable $exception): ExceptionPackage
    {
        if ($exception instanceof CompositeException) {
            return new ExceptionPackage($exception->getExceptions());
        }

        return new ExceptionPackage([$exception]);
    }
}

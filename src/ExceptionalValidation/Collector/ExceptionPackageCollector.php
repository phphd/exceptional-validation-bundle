<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Collector;

use PhPhD\ExceptionalValidation\Model\Exception\ExceptionPackage;
use Throwable;

interface ExceptionPackageCollector
{
    public function collect(Throwable $exception): ExceptionPackage;
}

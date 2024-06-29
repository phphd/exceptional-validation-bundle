<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Formatter;

use PhPhD\ExceptionalValidation\Model\Exception\ProcessedException;
use Symfony\Component\Validator\ConstraintViolationInterface;

interface ExceptionViolationFormatter
{
    public function formatViolation(ProcessedException $processedException): ConstraintViolationInterface;
}

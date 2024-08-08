<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation;

use Attribute;
use Exception;
use Throwable;
use Webmozart\Assert\Assert;

/** @api */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class Capture
{
    public function __construct(
        /** @var class-string<Exception> */
        private readonly string $exception,
        private readonly ?string $message = null,
        /** @var ?non-empty-string */
        private readonly ?string $condition = null,
        /** @var ?array{0:object|class-string,1:string} */
        private readonly ?array $when = null,
        private readonly string $formatter = 'default',
    ) {
        if (null !== $this->when) {
            Assert::count($this->when, 2);
        }
    }

    /** @return class-string<Throwable> */
    public function getExceptionClass(): string
    {
        return $this->exception;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    /** @return ?array{0:object|class-string,1:string} */
    public function getWhen(): ?array
    {
        return $this->when;
    }

    public function getFormatter(): string
    {
        return $this->formatter;
    }
}

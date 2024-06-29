<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Formatter;

use PhPhD\ExceptionalValidation\Model\Exception\ProcessedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @internal */
final class ExceptionalViolationFormatter implements ExceptionViolationFormatter
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $translationDomain,
    ) {
    }

    public function formatViolation(ProcessedException $processedException): ConstraintViolationInterface
    {
        $rule = $processedException->getMatchedRule();

        $message = $rule->getMessage();
        $root = $rule->getRoot();
        $propertyPath = $rule->getPropertyPath();
        $value = $rule->getValue();

        $translatedMessage = $this->translator->trans($message, domain: $this->translationDomain);

        return new ConstraintViolation(
            $translatedMessage,
            $message,
            [],
            $root,
            $propertyPath->join('.'),
            $value,
        );
    }
}

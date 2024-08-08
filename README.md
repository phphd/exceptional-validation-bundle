# PhdExceptionalValidationBundle

🧰 Provides exception-to-violation mapper bundled as [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
middleware. It captures thrown exceptions, mapping them
into [Symfony Validator](https://symfony.com/doc/current/validation.html)
violations format based on message mapping attributes.

[![Build Status](https://img.shields.io/github/actions/workflow/status/phphd/exceptional-validation-bundle/ci.yaml?branch=main)](https://github.com/phphd/exceptional-validation-bundle/actions?query=branch%3Amain)
[![Codecov](https://codecov.io/gh/phphd/exceptional-validation-bundle/graph/badge.svg?token=GZRXWYT55Z)](https://codecov.io/gh/phphd/exceptional-validation-bundle)
[![Psalm coverage](https://shepherd.dev/github/phphd/exceptional-validation-bundle/coverage.svg)](https://shepherd.dev/github/phphd/exceptional-validation-bundle)
[![Psalm level](https://shepherd.dev/github/phphd/exceptional-validation-bundle/level.svg)](https://shepherd.dev/github/phphd/exceptional-validation-bundle)
[![Packagist Downloads](https://img.shields.io/packagist/dt/phphd/exceptional-validation-bundle.svg)](https://packagist.org/packages/phphd/exceptional-validation-bundle)
[![Licence](https://img.shields.io/github/license/phphd/exceptional-validation-bundle.svg)](https://github.com/phphd/exceptional-validation-bundle/blob/main/LICENSE)

## Installation 📥

1. Install via composer

    ```sh
    composer require phphd/exceptional-validation-bundle
    ```

2. Enable the bundle in the `bundles.php`

    ```php
    PhPhD\ExceptionalValidationBundle\PhdExceptionalValidationBundle::class => ['all' => true],
    ```

## Configuration ⚒️

The recommended way to use this package is via Symfony Messenger.

To leverage features of this bundle, you should add `phd_exceptional_validation` middleware to the list:

```diff
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - validation
+                   - phd_exceptional_validation
                    - doctrine_transaction
```

## Usage 🚀

The first thing necessary is to mark your message with `#[ExceptionalValidation]` attribute. It is used to include the
message for processing by the middleware.

Then you define `#[Capture]` attributes on the properties of the message. These attributes are used to specify mapping
for the thrown exceptions to the corresponding properties of the class with the respective error message translation.

```php
use PhPhD\ExceptionalValidation;
use PhPhD\ExceptionalValidation\Capture;

#[ExceptionalValidation]
final class RegisterUserCommand
{
    #[Capture(LoginAlreadyTakenException::class, 'auth.login.already_taken')]
    private string $login;

    #[Capture(WeakPasswordException::class, 'auth.password.weak')]
    private string $password;
}
```

In this example, whenever `LoginAlreadyTakenException` or `WeakPasswordException` is thrown, it will be captured and
mapped to the `login` or `password` property.

Eventually when `phd_exceptional_validation` middleware has processed the exception, it will
throw `ExceptionalValidationFailedException` so that it can be caught and processed as needed:

```php
$command = new RegisterUserCommand($login, $password);

try {
    $this->commandBus->dispatch($command);
} catch (ExceptionalValidationFailedException $exception) {
    $constraintViolationList = $exception->getViolations();

    return $this->render('registrationForm.html.twig', ['errors' => $constraintViolationList]);
} 
```

The `$exception` object enfolds constraint violations with respectively mapped error messages. This
violation list can be used for example to render errors into html-form or to serialize them for a json-response.

## Advanced usage ⚙️

`#[ExceptionalValidation]` and `#[Capture]` attributes allow you to implement very flexible mappings.
Here are just few examples of how you can use them.

### Capturing exceptions on nested objects

`#[ExceptionalValidation]` attribute works side-by-side with Symfony Validator `#[Valid]` attribute. Once you have
defined these, the `#[Capture]` attribute can be defined on the nested objects.

```php
use PhPhD\ExceptionalValidation;
use PhPhD\ExceptionalValidation\Capture;
use Symfony\Component\Validator\Constraints as Assert;

#[ExceptionalValidation]
final class OrderProductCommand
{
    #[Assert\Valid]
    private ProductDetails $product;
}

#[ExceptionalValidation]
final class ProductDetails
{
    private int $id;

    #[Capture(InsufficientStockException::class, 'order.insufficient_stock')]
    private string $quantity;

    // ...
}
```

In this example, whenever `InsufficientStockException` is thrown, it will be captured and mapped to the
`product.quantity` property with the corresponding message translation.

### Capture Closure Conditions

`#[Capture]` attribute accepts the callback function to determine whether particular exception instance should
be captured for the given property or not. It allows more dynamic exception handling scenarios:

```php
use PhPhD\ExceptionalValidation;
use PhPhD\ExceptionalValidation\Capture;

#[ExceptionalValidation]
final class TransferMoneyCommand
{
    #[Capture(
        BlockedCardException::class,
        'wallet.blocked_card',
        when: [self::class, 'isWithdrawalCardBlocked'],
    )]
    private int $withdrawalCardId;

    #[Capture(
        BlockedCardException::class,
        'wallet.blocked_card',
        when: [self::class, 'isDepositCardBlocked'],
    )]
    private int $depositCardId;

    #[Capture(
        InsufficientFundsException::class, 
        'wallet.insufficient_funds',
    )]
    private int $unitAmount;

    public function isWithdrawalCardBlocked(BlockedCardException $exception): bool
    {
        return $exception->getCardId() === $this->withdrawalCardId;
    }

    public function isDepositCardBlocked(BlockedCardException $exception): bool
    {
        return $exception->getCardId() === $this->depositCardId;
    }
}
```

In this example, `when: ` option of the `#[Capture]` attribute is used to specify the callback functions that are called
when exception is processed. If `isWithdrawalCardBlocked` callback returns `true`, then exception is captured for
`withdrawalCardId` property; if `isDepositCardBlocked` callback returns `true`, then exception is captured for
`depositCardId` property. If neither of the callbacks return `true`, then exception is re-thrown upper in the stack.

### Simple Capture Conditions

Since in most cases capture conditions come down to the simple value comparison, it's easier to make your exception
implement `InvalidValueException` interface and specify `condition: 'invalid_value'` rather than implementing `when:`
closure every time. This way you can avoid boilerplate code and make your code more readable.

```php
#[ExceptionalValidation]
final class TransferMoneyCommand
{
    #[Capture(BlockedCardException::class, 'wallet.blocked_card', condition: 'invalid_value')]
    private int $withdrawalCardId;

    #[Capture(BlockedCardException::class, 'wallet.blocked_card', condition: 'invalid_value')]
    private int $depositCardId;
}
```

The `BlockedCardException` should implement `InvalidValueException` interface:

```php
use PhPhD\ExceptionalValidation\Model\Condition\Exception\InvalidValueException;
use RuntimeException;

final class BlockedCardException extends RuntimeException implements InvalidValueException
{
    public function __construct(
        private Card $card,
    ) {
        parent::__construct();
    }

    public function getInvalidValue(): int
    {
        return $this->card->getId();    
    }
}
```

In this example `BlockedCardException` could be captured both for `withdrawalCardId` and `depositCardId` properties
depending on the `cardId` value from the exception.

### Capturing exceptions on nested array items

You are perfectly allowed to map the violations for the nested array items given that you have `#[Valid]` attribute
on the iterable property. For example:

```php
use PhPhD\ExceptionalValidation;
use PhPhD\ExceptionalValidation\Capture;
use Symfony\Component\Validator\Constraints as Assert;

#[ExceptionalValidation]
final class CreateOrderCommand
{
    /** @var ProductDetails[] */
    #[Assert\Valid]
    private array $products;
}

#[ExceptionalValidation]
final class ProductDetails
{
    private int $id;

    #[Capture(
        InsufficientStockException::class, 
        'order.insufficient_stock', 
        when: [self::class, 'isStockExceptionForThisProduct'],
    )]
    private string $quantity;

    public function isStockExceptionForThisProduct(InsufficientStockException $exception): bool
    {
        return $exception->getProductId() === $this->id;
    }
}
```

In this example, when `InsufficientStockException` is captured, it will be mapped to the `products[*].quantity`
property, where `*` stands for the index of the particular `ProductDetails` instance from the `products` array on which
the exception was captured.

### Custom violation formatters

In some cases, you might need to customize the way violations are formatted such as passing additional
parameters to the message translation. You can achieve this by creating your own violation formatter service that
implements `ExceptionViolationFormatter` interface:

```php
use PhPhD\ExceptionalValidation\Formatter\ExceptionViolationFormatter;
use PhPhD\ExceptionalValidation\Model\Exception\CapturedException;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class RegistrationViolationsFormatter implements ExceptionViolationFormatter
{
    public function __construct(
        #[Autowire('@phd_exceptional_validation.violation_formatter.default')]
        private ExceptionViolationFormatter $formatter,
    ) {
    }

    public function formatViolation(CapturedException $capturedException): ConstraintViolationInterface
    {
        // you can format violations with the default formatter
        // and then slightly adjust necessary parts
        $violation = $this->formatter->formatViolation($capturedException);

        $exception = $capturedException->getException();

        if ($exception instanceof LoginAlreadyTakenException) {
            return new ConstraintViolation(
                $violation->getMessage(),
                $violation->getMessageTemplate(),
                ['loginHolder' => $exception->getLoginHolder()],
                // ...
            );
        }

        if ($exception instanceof WeakPasswordException) {
            // ...
        }

        return $violation;
    }
}
```

Then you should register your custom formatter as a service:

```yaml
services:
    App\AuthBundle\ViolationFormatter\RegistrationViolationsFormatter:
        tags: [ 'exceptional_validation.violation_formatter' ]
```

> In order for your custom violation formatter to be recognized by this bundle, its service must be tagged
> with `exceptional_validation.violation_formatter` tag. If you
> use [autoconfiguration](https://symfony.com/doc/current/service_container.html#the-autoconfigure-option), this is done
> automatically by the service container owing to the fact that `ExceptionViolationFormatter` interface is implemented.

Finally, your custom formatter should be specified in the `#[Capture]` attribute:

```php
use PhPhD\ExceptionalValidation;
use PhPhD\ExceptionalValidation\Capture;

#[ExceptionalValidation]
final class RegisterUserCommand
{
    #[Capture(
        LoginAlreadyTakenException::class, 
        'auth.login.already_taken', 
        formatter: RegistrationViolationsFormatter::class,
    )]
    private string $login;

    #[Capture(
        WeakPasswordException::class, 
        'auth.password.weak', 
        formatter: RegistrationViolationsFormatter::class,
    )]
    private string $password;
}
```

In this example, `RegistrationViolationsFormatter` is used to format constraint violations for
both `LoginAlreadyTakenException` and `WeakPasswordException` (though you are perfectly fine to use separate
formatters), enriching them with additional context.

## Limitations

### Capturing multiple exceptions at once

Typically, validation process is expected to capture all errors at once and return them as a list of violations.
However, the whole concept of exceptional processing in PHP is based on the idea that only one exception could be thrown
at a time, since only one logical instruction is executed at a time.

In case of Symfony Messenger, this is somewhat overcome by the fact that `HandlerFailedException` can wrap multiple
exceptions collected from the underlying handlers. Though, currently there's no way to collect more than one
exception from the same handler because of the limitations of sequential computing model.

We are currently thinking about the issue and trying to anticipate the solution that will allow capturing multiple
exceptions. Most likely the solution will be based on some ideas from the interaction combinators computing model, where
code is no longer considered as a mere sequence of instructions, but rather as a graph of interactions that are combined
and reduced on each step of evaluation.

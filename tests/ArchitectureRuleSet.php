<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Tests;

use PHPat\Selector\ClassNamespace;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use PhPhD\ExceptionalValidation;
use PhPhD\ExceptionalValidation\Assembler\CaptureRuleSetAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\ObjectRuleSetAssembler;
use PhPhD\ExceptionalValidation\Capture;
use PhPhD\ExceptionalValidation\Collector\ExceptionPackageCollector;
use PhPhD\ExceptionalValidation\Formatter\ExceptionViolationListFormatter;
use PhPhD\ExceptionalValidation\Handler\ExceptionHandler;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;
use PhPhD\ExceptionalValidationBundle\Messenger\ExceptionalValidationMiddleware;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Webmozart\Assert\Assert;

use function array_slice;
use function explode;
use function implode;

/**
 * @internal
 *
 * @api
 */
final class ArchitectureRuleSet
{
    #[TestRule]
    public function testMiddlewareDependencies(): Rule
    {
        return $this->layerRule('middleware');
    }

    #[TestRule]
    public function testExceptionHandlerDependencies(): Rule
    {
        return $this->layerRule('exceptionHandler');
    }

    #[TestRule]
    public function testExceptionPackageCollectorDependencies(): Rule
    {
        return $this->layerRule('exceptionPackageCollector');
    }

    #[TestRule]
    public function testViolationsFormatterDependencies(): Rule
    {
        return $this->layerRule('violationsFormatter');
    }

    #[TestRule]
    public function testCaptureTreeAssemblerDependencies(): Rule
    {
        return $this->layerRule('captureRuleSetAssembler');
    }

    #[TestRule]
    public function testCaptureTreeDependencies(): Rule
    {
        return $this->layerRule('model');
    }

    public function layerRule(string $name): BuildStep
    {
        $layer = $this->layers()[$name];

        $layerClasses = $this->{$name}();

        return PHPat::rule()
            ->classes($layerClasses)
            ->canOnlyDependOn()
            ->classes($layerClasses, ...$layer['deps'])
            ->because($layer['description'] ?? 'It has clearly defined dependency rules in '.self::class.'::layers()')
        ;
    }

    /** @return array<string,array{deps:list<SelectorInterface>,description?: string}> */
    public function layers(): array
    {
        return [
            'middleware' => [
                'deps' => [
                    Selector::AND(
                        Selector::isInterface(),
                        $this->exceptionHandler(),
                    ),
                    Selector::inNamespace('Symfony\Component\Messenger'),
                ],
            ],
            'exceptionHandler' => [
                'deps' => [
                    Selector::classname(ObjectRuleSetAssembler::class),
                    $this->model(),
                    Selector::AND(
                        Selector::isInterface(),
                        $this->violationsFormatter(),
                    ),
                    Selector::AND(
                        Selector::isInterface(),
                        $this->exceptionPackageCollector(),
                    ),
                    Selector::classname(ConstraintViolationListInterface::class),
                ],
            ],
            'violationsFormatter' => [
                'deps' => [
                    $this->model(),
                    Selector::inNamespace(class_namespace(ConstraintViolationListInterface::class)),
                    Selector::classname(TranslatorInterface::class),
                ],
            ],
            'exceptionPackageCollector' => [
                'deps' => [
                    $this->model(),
                ],
            ],
            'captureRuleSetAssembler' => [
                'deps' => [
                    $this->model(),
                    Selector::classname(ExceptionalValidation::class),
                    Selector::classname(Capture::class),
                    Selector::classname(Valid::class),
                ],
            ],
            'model' => [
                'deps' => [
                    Selector::classname(Assert::class),
                ],
                'description' => 'Model classes must not depend on anything else',
            ],
        ];
    }

    /** @psalm-suppress UnusedMethod */
    public function middleware(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(ExceptionalValidationMiddleware::class));
    }

    public function exceptionHandler(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(ExceptionHandler::class));
    }

    public function exceptionPackageCollector(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(ExceptionPackageCollector::class));
    }

    public function violationsFormatter(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(ExceptionViolationListFormatter::class));
    }

    public function captureRuleSetAssembler(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(CaptureRuleSetAssembler::class));
    }

    public function model(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(class_namespace(CaptureRule::class)));
    }
}

/**
 * @param non-empty-string $class
 *
 * @return non-empty-string
 */
function class_namespace(string $class): string
{
    /** @var non-empty-list<string> $namespaceParts */
    $namespaceParts = array_slice(explode('\\', $class), 0, -1);

    return implode('\\', $namespaceParts);
}

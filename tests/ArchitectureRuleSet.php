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
use PhPhD\ExceptionalValidation\ConditionFactory\MatchConditionFactory;
use PhPhD\ExceptionalValidation\Formatter\ExceptionViolationListFormatter;
use PhPhD\ExceptionalValidation\Handler\ExceptionHandler;
use PhPhD\ExceptionalValidation\Model\Exception\Adapter\ThrownException;
use PhPhD\ExceptionalValidation\Model\Rule\CaptureRule;
use PhPhD\ExceptionalValidationBundle\Messenger\ExceptionalValidationMiddleware;
use Psr\Container\ContainerInterface;
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
    public function testViolationFormatterDependencies(): Rule
    {
        return $this->layerRule('formatter');
    }

    #[TestRule]
    public function testMatchConditionFactoryDependencies(): Rule
    {
        return $this->layerRule('matchConditionFactory');
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
                    Selector::inNamespace(class_namespace(ThrownException::class)),
                    Selector::inNamespace('Symfony\Component\Messenger'),
                ],
            ],
            'exceptionHandler' => [
                'deps' => [
                    Selector::classname(ObjectRuleSetAssembler::class),
                    $this->model(),
                    Selector::AND(
                        Selector::isInterface(),
                        $this->formatter(),
                    ),
                    Selector::classname(ConstraintViolationListInterface::class),
                ],
            ],
            'formatter' => [
                'deps' => [
                    $this->model(),
                    Selector::inNamespace(class_namespace(ConstraintViolationListInterface::class)),
                    Selector::classname(TranslatorInterface::class),
                    Selector::inNamespace(class_namespace(ContainerInterface::class)),
                ],
            ],
            'captureRuleSetAssembler' => [
                'deps' => [
                    $this->model(),
                    Selector::classname(ExceptionalValidation::class),
                    Selector::classname(Capture::class),
                    Selector::classname(Valid::class),
                    Selector::classname(MatchConditionFactory::class),
                    Selector::classname(Assert::class),
                ],
            ],
            'matchConditionFactory' => [
                'deps' => [
                    $this->model(),
                    Selector::classname(Capture::class),
                    Selector::inNamespace(class_namespace(ContainerInterface::class)),
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

    public function formatter(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(ExceptionViolationListFormatter::class));
    }

    public function captureRuleSetAssembler(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(CaptureRuleSetAssembler::class));
    }

    public function matchConditionFactory(): ClassNamespace
    {
        return Selector::inNamespace(class_namespace(MatchConditionFactory::class));
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

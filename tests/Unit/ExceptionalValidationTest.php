<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Tests;

use ArrayIterator;
use ArrayObject;
use LogicException;
use PhPhD\ExceptionalValidation\Assembler\CaptureRuleSetAssembler;
use PhPhD\ExceptionalValidation\Assembler\CompositeRuleSetAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\IterableOfObjectsRuleSetAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\ObjectRuleSetAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\Rules\ObjectRulesAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\PropertyRuleSetAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyCaptureRulesAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyNestedValidIterableRulesAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyNestedValidObjectRuleAssembler;
use PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyRulesAssemblerEnvelope;
use PhPhD\ExceptionalValidation\Collector\Exception\CompositeException;
use PhPhD\ExceptionalValidation\Collector\ExceptionalPackageCollector;
use PhPhD\ExceptionalValidation\Formatter\ExceptionalViolationFormatter;
use PhPhD\ExceptionalValidation\Formatter\ExceptionalViolationListFormatter;
use PhPhD\ExceptionalValidation\Handler\Exception\ExceptionalValidationFailedException;
use PhPhD\ExceptionalValidation\Handler\ExceptionalHandler;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\ConditionallyCapturedException;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\NestedItemCapturedException;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\NestedPropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\ObjectPropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\PropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\StaticPropertyCapturedException;
use PhPhD\ExceptionalValidation\Tests\Stub\HandleableMessageStub;
use PhPhD\ExceptionalValidation\Tests\Stub\NestedHandleableMessage;
use PhPhD\ExceptionalValidation\Tests\Stub\NestedItem;
use PhPhD\ExceptionalValidation\Tests\Stub\NotHandleableMessageStub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \PhPhD\ExceptionalValidation
 * @covers \PhPhD\ExceptionalValidation\Capture
 * @covers \PhPhD\ExceptionalValidation\Handler\ExceptionalHandler
 * @covers \PhPhD\ExceptionalValidation\Handler\Exception\ExceptionalValidationFailedException
 * @covers \PhPhD\ExceptionalValidation\Formatter\ExceptionalViolationListFormatter
 * @covers \PhPhD\ExceptionalValidation\Formatter\ExceptionalViolationFormatter
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\ObjectRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\IterableItemCaptureRule
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\PropertyRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\CompositeRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\LazyRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\CaptureExceptionRule
 * @covers \PhPhD\ExceptionalValidation\Model\Condition\MatchByExceptionClassCondition
 * @covers \PhPhD\ExceptionalValidation\Model\Condition\MatchWithClosureCondition
 * @covers \PhPhD\ExceptionalValidation\Model\Condition\CompositeMatchCondition
 * @covers \PhPhD\ExceptionalValidation\Model\Exception\ProcessedException
 * @covers \PhPhD\ExceptionalValidation\Model\ValueObject\PropertyPath
 * @covers \PhPhD\ExceptionalValidation\Model\Exception\ExceptionPackage
 * @covers \PhPhD\ExceptionalValidation\Assembler\CompositeRuleSetAssembler
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\ObjectRuleSetAssembler
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\ObjectRulesAssemblerEnvelope
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\ObjectRulesAssembler
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\PropertyRuleSetAssemblerEnvelope
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\PropertyRuleSetAssembler
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyRulesAssemblerEnvelope
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyCaptureRulesAssembler
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyNestedValidObjectRuleAssembler
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\Rules\Property\Rules\PropertyNestedValidIterableRulesAssembler
 * @covers \PhPhD\ExceptionalValidation\Assembler\Object\IterableOfObjectsRuleSetAssembler
 *
 * @internal
 */
final class ExceptionalValidationTest extends TestCase
{
    private ExceptionalHandler $exceptionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnMap([
                ['oops', [], 'domain', null, 'oops - translated'],
                ['object.oops', [], 'domain', null, 'object.oops - translated'],
                ['nested.message', [], 'domain', null, 'nested.message - translated'],
            ])
        ;

        /** @var ArrayIterator<array-key,CaptureRuleSetAssembler<PropertyRulesAssemblerEnvelope>> $captureListAssemblers */
        $captureListAssemblers = new ArrayIterator();
        $propertyRulesAssembler = new CompositeRuleSetAssembler($captureListAssemblers);
        $propertyRuleSetAssembler = new PropertyRuleSetAssembler($propertyRulesAssembler);

        $objectRulesAssembler = new ObjectRulesAssembler($propertyRuleSetAssembler);
        $objectRuleSetAssembler = new ObjectRuleSetAssembler($objectRulesAssembler);

        $captureListAssemblers->append(new PropertyCaptureRulesAssembler());
        $captureListAssemblers->append(new PropertyNestedValidObjectRuleAssembler($objectRuleSetAssembler));
        $captureListAssemblers->append(new PropertyNestedValidIterableRulesAssembler(new IterableOfObjectsRuleSetAssembler($objectRuleSetAssembler)));

        $exceptionPackageCollector = new ExceptionalPackageCollector();

        $violationFormatter = new ExceptionalViolationFormatter($translator, 'domain');
        $violationListFormatter = new ExceptionalViolationListFormatter($violationFormatter);
        $this->exceptionHandler = new ExceptionalHandler($objectRuleSetAssembler, $exceptionPackageCollector, $violationListFormatter);
    }

    public function testDoesNotCaptureExceptionForMessageNotHavingExceptionalValidationAttribute(): never
    {
        $message = new NotHandleableMessageStub(123);

        $this->expectExceptionObject($exception = new PropertyCapturableException());

        $this->exceptionHandler->capture($message, $exception);
    }

    public function testCapturesExceptionMappedToProperty(): void
    {
        $message = HandleableMessageStub::create();
        $rootException = new PropertyCapturableException();

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $rootException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame(
                'Message of type "PhPhD\ExceptionalValidation\Tests\Stub\HandleableMessageStub" has failed exceptional validation.',
                $e->getMessage(),
            );
            self::assertSame($rootException, $e->getPrevious());
            self::assertSame($message, $e->getViolatingMessage());

            $violationList = $e->getViolations();
            self::assertCount(1, $violationList);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violationList[0];
            self::assertSame('property', $violation->getPropertyPath());
            self::assertSame('oops - translated', $violation->getMessage());
            self::assertSame('oops', $violation->getMessageTemplate());
            self::assertSame($message, $violation->getRoot());
            self::assertSame([], $violation->getParameters());
            self::assertNull($violation->getInvalidValue());

            throw $e;
        }
    }

    public function testCollectsInitializedPropertyValue(): void
    {
        $message = HandleableMessageStub::create()->withMessageText('invalid text value');

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, new LogicException());
        } catch (ExceptionalValidationFailedException $e) {
            /** @var ConstraintViolationInterface $violation */
            [$violation] = $e->getViolations();

            self::assertSame('invalid text value', $violation->getInvalidValue());

            throw $e;
        }
    }

    public function testCollectsObjectInvalidValue(): void
    {
        $message = HandleableMessageStub::create()->withObjectProperty($object = new stdClass());

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, new ObjectPropertyCapturableException());
        } catch (ExceptionalValidationFailedException $e) {
            /** @var ConstraintViolationInterface $violation */
            [$violation] = $e->getViolations();

            self::assertSame('object.oops - translated', $violation->getMessage());
            self::assertSame('object.oops', $violation->getMessageTemplate());
            self::assertSame($object, $violation->getInvalidValue());

            throw $e;
        }
    }

    public function testCapturesExceptionsMappedToStaticProperties(): void
    {
        $message = HandleableMessageStub::create();

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, new StaticPropertyCapturedException());
        } catch (ExceptionalValidationFailedException $e) {
            /** @var ConstraintViolationInterface $violation */
            [$violation] = $e->getViolations();

            self::assertSame('staticProperty', $violation->getPropertyPath());
            self::assertSame('foo', $violation->getInvalidValue());

            throw $e;
        }
    }

    public function testDoesNotCaptureNestedObjectWithoutValidPropertyAttribute(): never
    {
        $message = HandleableMessageStub::create()->withOrdinaryObject(new NestedHandleableMessage());

        $this->expectExceptionObject($exception = new NestedPropertyCapturableException());

        $this->exceptionHandler->capture($message, $exception);
    }

    public function testDoesNotCaptureNotInitializedValidNestedObjectProperty(): never
    {
        $message = HandleableMessageStub::create();

        $this->expectExceptionObject($exception = new NestedPropertyCapturableException());

        $this->exceptionHandler->capture($message, $exception);
    }

    public function testCapturesNestedObjectPropertyException(): void
    {
        $message = HandleableMessageStub::create()->withNestedObject(new NestedHandleableMessage());

        $rootException = new NestedPropertyCapturableException();

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $rootException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($rootException, $e->getPrevious());

            $violations = $e->getViolations();
            self::assertCount(1, $violations);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violations[0];
            self::assertSame('nested.message - translated', $violation->getMessage());
            self::assertSame('nested.message', $violation->getMessageTemplate());
            self::assertSame('nestedObject.nestedProperty', $violation->getPropertyPath());
            self::assertNull($violation->getInvalidValue());

            throw $e;
        }
    }

    public function testDoesntCaptureAnyExceptionWhenConditionIsNotMet(): never
    {
        $message = HandleableMessageStub::create()->withConditionalMessage(11, 41);

        $rootException = new ConditionallyCapturedException(12);

        $this->expectExceptionObject($rootException);

        $this->exceptionHandler->capture($message, $rootException);
    }

    public function testCapturesExceptionWithGivenCondition(): void
    {
        $message = HandleableMessageStub::create()->withConditionalMessage(11, 41);

        $rootException = new ConditionallyCapturedException(41);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $rootException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($rootException, $e->getPrevious());

            $violations = $e->getViolations();
            self::assertCount(1, $violations);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violations[0];
            self::assertSame('nestedObject.conditionalMessage.secondProperty', $violation->getPropertyPath());
            self::assertSame(41, $violation->getInvalidValue());

            throw $e;
        }
    }

    public function testDoesntCaptureNestedItemsNotHavingValidAttribute(): never
    {
        $message = HandleableMessageStub::create()->withJustArray([
            new NestedItem(1),
            new NestedItem(2),
            new NestedItem(3),
        ]);

        $rootException = new NestedItemCapturedException(code: 2);

        $this->expectExceptionObject($rootException);

        $this->exceptionHandler->capture($message, $rootException);
    }

    public function testCapturesExceptionOnNestedArrayItem(): void
    {
        $message = HandleableMessageStub::create()->withNestedArrayItems([
            new NestedItem(41),
            new NestedItem(57),
            new NestedItem(32),
        ]);

        $rootException = new NestedItemCapturedException(code: 57);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $rootException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($rootException, $e->getPrevious());

            $violations = $e->getViolations();
            self::assertCount(1, $violations);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violations[0];
            self::assertSame('nestedArrayItems[1].property', $violation->getPropertyPath());

            throw $e;
        }
    }

    public function testCapturesExceptionOnNestedIterableItem(): void
    {
        $message = HandleableMessageStub::create()->withNestedIterableItems(new ArrayObject([
            'first' => new NestedItem(1),
            'second' => new NestedItem(2),
            'third' => new NestedItem(3),
            4 => new NestedItem(2),
        ]));

        $rootException = new NestedItemCapturedException(code: 2);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $rootException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($rootException, $e->getPrevious());

            $violations = $e->getViolations();
            self::assertCount(1, $violations);

            /** @var ConstraintViolationInterface $firstViolation */
            $firstViolation = $violations[0];
            self::assertSame('nestedIterableItems[second].property', $firstViolation->getPropertyPath());

            throw $e;
        }
    }

    public function testRethrowsCompositeExceptionInCaseOfUnhandledItem(): never
    {
        $message = HandleableMessageStub::create()
            ->withNestedArrayItems([
                'first' => new NestedItem(1),
                'second' => new NestedItem(2),
            ])
        ;

        $rootException = new CompositeException([
            new NestedItemCapturedException(code: 1),
            new NestedItemCapturedException(code: 3),
        ]);

        $this->expectExceptionObject($rootException);

        $this->exceptionHandler->capture($message, $rootException);
    }

    public function testCapturesMultipleExceptions(): void
    {
        $message = HandleableMessageStub::create()
            ->withNestedArrayItems([
                'first' => new NestedItem(2),
            ])
            ->withNestedIterableItems(new ArrayObject([
                'second' => new NestedItem(1),
            ]))
        ;

        $rootException = new CompositeException([
            new NestedItemCapturedException(code: 1),
            new PropertyCapturableException(),
            new ObjectPropertyCapturableException(),
            new NestedItemCapturedException(code: 2),
        ]);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $rootException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($rootException, $e->getPrevious());

            $violations = $e->getViolations();
            self::assertCount(4, $violations);

            /** @var ConstraintViolationInterface $firstViolation */
            $firstViolation = $violations[0];
            self::assertSame('property', $firstViolation->getPropertyPath());

            /** @var ConstraintViolationInterface $secondViolation */
            $secondViolation = $violations[1];
            self::assertSame('objectProperty', $secondViolation->getPropertyPath());

            /** @var ConstraintViolationInterface $thirdViolation */
            $thirdViolation = $violations[2];
            self::assertSame('nestedArrayItems[first].property', $thirdViolation->getPropertyPath());

            /** @var ConstraintViolationInterface $fourthViolation */
            $fourthViolation = $violations[3];
            self::assertSame('nestedIterableItems[second].property', $fourthViolation->getPropertyPath());

            throw $e;
        }
    }
}

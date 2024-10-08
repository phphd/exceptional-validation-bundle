<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Tests\Unit;

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
use PhPhD\ExceptionalValidation\ConditionFactory\CaptureMatchConditionFactory;
use PhPhD\ExceptionalValidation\ConditionFactory\ValueExceptionMatchConditionFactory;
use PhPhD\ExceptionalValidation\Formatter\DefaultExceptionListViolationFormatter;
use PhPhD\ExceptionalValidation\Formatter\DefaultExceptionViolationFormatter;
use PhPhD\ExceptionalValidation\Formatter\DelegatingExceptionViolationFormatter;
use PhPhD\ExceptionalValidation\Formatter\ExceptionViolationFormatter;
use PhPhD\ExceptionalValidation\Formatter\ViolationListExceptionFormatter;
use PhPhD\ExceptionalValidation\Handler\DefaultExceptionHandler;
use PhPhD\ExceptionalValidation\Handler\Exception\ExceptionalValidationFailedException;
use PhPhD\ExceptionalValidation\Model\Condition\ValueExceptionMatchCondition;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\CustomExceptionViolationFormatter;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\CompositeException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\CompositeExceptionUnwrapper;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\ConditionallyCapturedException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\CustomFormattedException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\MessageContainingException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\NestedItemCapturedException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\NestedPropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\ObjectPropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\PropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\SomeValueException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\StaticPropertyCapturedException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\Exception\ViolationListExampleException;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\HandleableMessageStub;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\NestedHandleableMessage;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\NestedItem;
use PhPhD\ExceptionalValidation\Tests\Unit\Stub\NotHandleableMessageStub;
use PhPhD\ExceptionToolkit\Unwrapper\PassThroughExceptionUnwrapper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_flip;
use function array_intersect_key;

/**
 * @covers \PhPhD\ExceptionalValidation
 * @covers \PhPhD\ExceptionalValidation\Capture
 * @covers \PhPhD\ExceptionalValidation\Handler\DefaultExceptionHandler
 * @covers \PhPhD\ExceptionalValidation\Handler\Exception\ExceptionalValidationFailedException
 * @covers \PhPhD\ExceptionalValidation\Formatter\DefaultExceptionListViolationFormatter
 * @covers \PhPhD\ExceptionalValidation\Formatter\DelegatingExceptionViolationFormatter
 * @covers \PhPhD\ExceptionalValidation\Formatter\DefaultExceptionViolationFormatter
 * @covers \PhPhD\ExceptionalValidation\Formatter\ViolationListExceptionFormatter
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\ObjectRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\IterableItemCaptureRule
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\PropertyRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\CompositeRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\LazyRuleSet
 * @covers \PhPhD\ExceptionalValidation\Model\Rule\CaptureExceptionRule
 * @covers \PhPhD\ExceptionalValidation\Model\Condition\ExceptionClassMatchCondition
 * @covers \PhPhD\ExceptionalValidation\Model\Condition\ValueExceptionMatchCondition
 * @covers \PhPhD\ExceptionalValidation\Model\Condition\ClosureMatchCondition
 * @covers \PhPhD\ExceptionalValidation\Model\Condition\CompositeMatchCondition
 * @covers \PhPhD\ExceptionalValidation\Model\ValueObject\PropertyPath
 * @covers \PhPhD\ExceptionalValidation\Model\Exception\ExceptionPackage
 * @covers \PhPhD\ExceptionalValidation\Model\Exception\CapturedException
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
 * @covers \PhPhD\ExceptionalValidation\ConditionFactory\CaptureMatchConditionFactory
 * @covers \PhPhD\ExceptionalValidation\ConditionFactory\ExceptionClassMatchConditionFactory
 * @covers \PhPhD\ExceptionalValidation\ConditionFactory\ValueExceptionMatchConditionFactory
 * @covers \PhPhD\ExceptionalValidation\ConditionFactory\ClosureMatchConditionFactory
 *
 * @internal
 */
final class ExceptionalValidationUnitTest extends TestCase
{
    private DefaultExceptionHandler $exceptionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnMap([
                ['', [], 'domain', null, ''],
                ['oops', [], 'domain', null, 'oops - translated'],
                ['object.oops', [], 'domain', null, 'object.oops - translated'],
                ['nested.message', [], 'domain', null, 'nested.message - translated'],
                ['This is the message to be used', [], 'domain', null, 'This is the message to be used'],
            ])
        ;

        /** @var ArrayIterator<array-key,CaptureRuleSetAssembler<PropertyRulesAssemblerEnvelope>> $captureListAssemblers */
        $captureListAssemblers = new ArrayIterator();
        $propertyRulesAssembler = new CompositeRuleSetAssembler($captureListAssemblers);
        $propertyRuleSetAssembler = new PropertyRuleSetAssembler($propertyRulesAssembler);

        $objectRulesAssembler = new ObjectRulesAssembler($propertyRuleSetAssembler);
        $objectRuleSetAssembler = new ObjectRuleSetAssembler($objectRulesAssembler);

        $conditionFactoryRegistry = $this->createMock(ContainerInterface::class);
        $conditionFactoryRegistry->method('get')->willReturnMap([
            [ValueExceptionMatchCondition::class, new ValueExceptionMatchConditionFactory()],
        ]);
        $captureMatchConditionFactory = new CaptureMatchConditionFactory($conditionFactoryRegistry);

        $captureListAssemblers->append(new PropertyCaptureRulesAssembler($captureMatchConditionFactory));
        $captureListAssemblers->append(new PropertyNestedValidObjectRuleAssembler($objectRuleSetAssembler));
        $captureListAssemblers->append(new PropertyNestedValidIterableRulesAssembler(new IterableOfObjectsRuleSetAssembler($objectRuleSetAssembler)));

        $defaultViolationFormatter = new DefaultExceptionViolationFormatter($translator, 'domain');
        $violationListExceptionFormatter = new ViolationListExceptionFormatter();
        $customViolationFormatter = new CustomExceptionViolationFormatter($defaultViolationFormatter);

        $formatterRegistry = $this->createMock(ContainerInterface::class);
        $formatters = [
            'default' => $defaultViolationFormatter,
            ViolationListExceptionFormatter::class => $violationListExceptionFormatter,
            CustomExceptionViolationFormatter::class => $customViolationFormatter,
        ];
        $formatterRegistry->method('has')
            ->willReturnCallback(static fn (string $id): bool => isset($formatters[$id]))
        ;
        $formatterRegistry->method('get')
            ->willReturnCallback(static fn (string $id): ExceptionViolationFormatter => $formatters[$id])
        ;

        $violationFormatter = new DelegatingExceptionViolationFormatter($formatterRegistry);
        $exceptionUnwrapper = new CompositeExceptionUnwrapper(new PassThroughExceptionUnwrapper());
        $violationListFormatter = new DefaultExceptionListViolationFormatter($violationFormatter);
        $this->exceptionHandler = new DefaultExceptionHandler($objectRuleSetAssembler, $exceptionUnwrapper, $violationListFormatter);
    }

    public function testDoesNotCaptureExceptionForMessageNotHavingExceptionalValidationAttribute(): void
    {
        $message = new NotHandleableMessageStub(123);

        $this->expectNotToPerformAssertions();

        $this->exceptionHandler->capture($message, new PropertyCapturableException());
    }

    public function testCapturesExceptionMappedToProperty(): void
    {
        $message = HandleableMessageStub::create();
        $originalException = new PropertyCapturableException();

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame(
                'Message of type "PhPhD\ExceptionalValidation\Tests\Unit\Stub\HandleableMessageStub" has failed exceptional validation.',
                $e->getMessage(),
            );
            self::assertSame($originalException, $e->getPrevious());
            self::assertSame($message, $e->getViolatingMessage());

            $violationList = $e->getViolationList();
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
            [$violation] = $e->getViolationList();

            self::assertSame('invalid text value', $violation->getInvalidValue());

            throw $e;
        }
    }

    public function testCollectsObjectInvalidValue(): void
    {
        $message = HandleableMessageStub::create()->withObjectProperty($object = new stdClass());

        $this->expectException(ExceptionalValidationFailedException::class);

        $originalException = new ObjectPropertyCapturableException();

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            /** @var ConstraintViolationInterface $violation */
            [$violation] = $e->getViolationList();

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

        $originalException = new StaticPropertyCapturedException();

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            /** @var ConstraintViolationInterface $violation */
            [$violation] = $e->getViolationList();

            self::assertSame('staticProperty', $violation->getPropertyPath());
            self::assertSame('foo', $violation->getInvalidValue());

            throw $e;
        }
    }

    public function testDoesNotCaptureNestedObjectWithoutValidPropertyAttribute(): void
    {
        $message = HandleableMessageStub::create()->withOrdinaryObject(new NestedHandleableMessage());

        $exception = new NestedPropertyCapturableException();

        $this->expectNotToPerformAssertions();

        $this->exceptionHandler->capture($message, $exception);
    }

    public function testDoesNotCaptureNotInitializedValidNestedObjectProperty(): void
    {
        $message = HandleableMessageStub::create();

        $exception = new NestedPropertyCapturableException();

        $this->expectNotToPerformAssertions();

        $this->exceptionHandler->capture($message, $exception);
    }

    public function testCapturesNestedObjectPropertyException(): void
    {
        $message = HandleableMessageStub::create()->withNestedObject(new NestedHandleableMessage());

        $originalException = new NestedPropertyCapturableException();

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($originalException, $e->getPrevious());

            $violationList = $e->getViolationList();
            self::assertCount(1, $violationList);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violationList[0];
            self::assertSame('nested.message - translated', $violation->getMessage());
            self::assertSame('nested.message', $violation->getMessageTemplate());
            self::assertSame('nestedObject.nestedProperty', $violation->getPropertyPath());
            self::assertNull($violation->getInvalidValue());

            throw $e;
        }
    }

    public function testDoesntCaptureAnyExceptionWhenConditionIsNotMet(): void
    {
        $message = HandleableMessageStub::create()->withConditionalMessage(11, 41);

        $originalException = new ConditionallyCapturedException(12);

        $this->expectNotToPerformAssertions();

        $this->exceptionHandler->capture($message, $originalException);
    }

    public function testCapturesExceptionWithGivenCondition(): void
    {
        $message = HandleableMessageStub::create()->withConditionalMessage(11, 41);

        $originalException = new ConditionallyCapturedException(41);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($originalException, $e->getPrevious());

            $violationList = $e->getViolationList();
            self::assertCount(1, $violationList);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violationList[0];
            self::assertSame('nestedObject.conditionalMessage.secondProperty', $violation->getPropertyPath());
            self::assertSame(41, $violation->getInvalidValue());

            throw $e;
        }
    }

    public function testDoesntCaptureNestedItemsForPropertyWithoutValidAttribute(): void
    {
        $message = HandleableMessageStub::create()->withJustArray([
            new NestedItem(1),
            new NestedItem(2),
            new NestedItem(3),
        ]);

        $originalException = new NestedItemCapturedException(code: 2);

        $this->expectNotToPerformAssertions();

        $this->exceptionHandler->capture($message, $originalException);
    }

    public function testCapturesExceptionOnNestedArrayItem(): void
    {
        $message = HandleableMessageStub::create()->withNestedArrayItems([
            new NestedItem(41),
            new NestedItem(57),
            new NestedItem(32),
        ]);

        $originalException = new NestedItemCapturedException(code: 57);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($originalException, $e->getPrevious());

            $violationList = $e->getViolationList();
            self::assertCount(1, $violationList);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violationList[0];
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

        $originalException = new NestedItemCapturedException(code: 2);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            self::assertSame($originalException, $e->getPrevious());

            $violationList = $e->getViolationList();
            self::assertCount(1, $violationList);

            /** @var ConstraintViolationInterface $firstViolation */
            $firstViolation = $violationList[0];
            self::assertSame('nestedIterableItems[second].property', $firstViolation->getPropertyPath());

            throw $e;
        }
    }

    public function testDoesNotAllowASingleUnhandledException(): void
    {
        $message = HandleableMessageStub::create()
            ->withNestedArrayItems([
                'first' => new NestedItem(1),
                'second' => new NestedItem(2),
            ])
        ;

        $exceptionAdapter = new CompositeException([
            new NestedItemCapturedException(code: 1),
            new NestedItemCapturedException(code: 3),
        ]);

        $this->expectNotToPerformAssertions();

        $this->exceptionHandler->capture($message, $exceptionAdapter);
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

        $exceptionAdapter = new CompositeException([
            new NestedItemCapturedException(code: 1),
            new PropertyCapturableException(),
            new ObjectPropertyCapturableException(),
            new NestedItemCapturedException(code: 2),
        ]);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $exceptionAdapter);
        } catch (ExceptionalValidationFailedException $e) {
            $violationList = $e->getViolationList();
            self::assertCount(4, $violationList);

            /** @var ConstraintViolationInterface $firstViolation */
            $firstViolation = $violationList[0];
            self::assertSame('property', $firstViolation->getPropertyPath());

            /** @var ConstraintViolationInterface $secondViolation */
            $secondViolation = $violationList[1];
            self::assertSame('objectProperty', $secondViolation->getPropertyPath());

            /** @var ConstraintViolationInterface $thirdViolation */
            $thirdViolation = $violationList[2];
            self::assertSame('nestedArrayItems[first].property', $thirdViolation->getPropertyPath());

            /** @var ConstraintViolationInterface $fourthViolation */
            $fourthViolation = $violationList[3];
            self::assertSame('nestedIterableItems[second].property', $fourthViolation->getPropertyPath());

            throw $e;
        }
    }

    public function testCustomViolationFormatter(): void
    {
        $message = HandleableMessageStub::create();

        $this->expectException(ExceptionalValidationFailedException::class);

        $originalException = new CustomFormattedException();

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            $violationList = $e->getViolationList();
            self::assertCount(1, $violationList);

            /** @var ConstraintViolationInterface $violation */
            $violation = $violationList[0];
            self::assertSame('custom - oops - translated', $violation->getMessage());
            self::assertSame('custom.oops', $violation->getMessageTemplate());
            self::assertSame([
                'custom' => 'param',
            ], $violation->getParameters());
            self::assertSame('customFormatted', $violation->getPropertyPath());

            throw $e;
        }
    }

    public function testValueException(): void
    {
        $message = HandleableMessageStub::create();

        $exceptionAdapter = new CompositeException([
            new SomeValueException('matched!'),
            new SomeValueException('whatever'),
        ]);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $exceptionAdapter);
        } catch (ExceptionalValidationFailedException $e) {
            $violationList = $e->getViolationList();
            self::assertCount(2, $violationList);

            /** @var ConstraintViolationInterface $violation1 */
            $violation1 = $violationList[0];

            self::assertSame('matchedCondition', $violation1->getPropertyPath());

            /** @var ConstraintViolationInterface $violation2 */
            $violation2 = $violationList[1];

            self::assertSame('anotherMatchedAsNoCondition', $violation2->getPropertyPath());

            throw $e;
        }
    }

    public function testViolationMessageFallsBackToExceptionMessage(): void
    {
        $message = HandleableMessageStub::create();
        $exceptionAdapter = new CompositeException([
            new MessageContainingException(),
            new MessageContainingException(),
        ]);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $exceptionAdapter);
        } catch (ExceptionalValidationFailedException $e) {
            $violationList = $e->getViolationList();
            self::assertCount(2, $violationList);

            /** @var ConstraintViolationInterface $violation1 */
            $violation1 = $violationList[0];

            self::assertSame('fallBackToExceptionMessage', $violation1->getPropertyPath());
            self::assertSame('This is the message to be used', $violation1->getMessage());

            /** @var ConstraintViolationInterface $violation2 */
            $violation2 = $violationList[1];

            // When the message is specified as an empty string, empty message is used (w/o fallback)
            self::assertSame('emptyTranslationMessage', $violation2->getPropertyPath());
            self::assertSame('', $violation2->getMessage());

            throw $e;
        }
    }

    public function testValidatorViolationListMapping(): void
    {
        $message = HandleableMessageStub::create()->withNestedObject(new NestedHandleableMessage());

        $violationList = Validation::createValidator()->validate('123', [$constraint = new Length(max: 2)]);

        $originalException = new ViolationListExampleException($violationList);

        $this->expectException(ExceptionalValidationFailedException::class);

        try {
            $this->exceptionHandler->capture($message, $originalException);
        } catch (ExceptionalValidationFailedException $e) {
            $violationList = $e->getViolationList();
            self::assertCount(1, $violationList);

            $violation = $violationList[0];
            self::assertInstanceOf(ConstraintViolation::class, $violation);
            self::assertSame(
                'This value is too long. It should have 2 characters or less.',
                $violation->getMessage(),
            );
            self::assertSame(
                'This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.',
                $violation->getMessageTemplate(),
            );

            $parameters = array_intersect_key(
                $violation->getParameters(),
                array_flip(['{{ value }}', '{{ limit }}']),
            );
            self::assertSame([
                '{{ value }}' => '"123"',
                '{{ limit }}' => '2',
            ], $parameters);

            self::assertSame(2, $violation->getPlural());
            self::assertSame($message, $violation->getRoot());
            self::assertSame('nestedObject.violationListCapturedProperty', $violation->getPropertyPath());
            self::assertSame('123', $violation->getInvalidValue());
            self::assertSame(Length::TOO_LONG_ERROR, $violation->getCode());
            self::assertSame($constraint, $violation->getConstraint());
            self::assertNull($violation->getCause());

            throw $e;
        }
    }
}

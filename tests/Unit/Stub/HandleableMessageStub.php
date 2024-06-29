<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidation\Tests\Stub;

use ArrayObject;
use LogicException;
use PhPhD\ExceptionalValidation;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\ObjectPropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\PropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Stub\Exception\StaticPropertyCapturedException;
use Symfony\Component\Validator\Constraints\Valid;

#[ExceptionalValidation]
final class HandleableMessageStub
{
    #[ExceptionalValidation\Capture(LogicException::class, 'oops')]
    private string $messageText;

    #[ExceptionalValidation\Capture(PropertyCapturableException::class, 'oops')]
    private int $property;

    #[ExceptionalValidation\Capture(ObjectPropertyCapturableException::class, 'object.oops')]
    private object $objectProperty;

    #[ExceptionalValidation\Capture(StaticPropertyCapturedException::class, 'oops')]
    private static string $staticProperty = 'foo';

    private NestedHandleableMessage $ordinaryObject;

    #[Valid]
    private NestedHandleableMessage $nestedObject;

    /** @var array<array-key,NestedItem> */
    #[Valid]
    private array $nestedArrayItems;

    /** @var ArrayObject<array-key,NestedItem> */
    #[Valid]
    private ArrayObject $nestedIterableItems;

    private array $justArray;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function withMessageText(string $messageText): self
    {
        $message = clone $this;
        $message->messageText = $messageText;

        return $message;
    }

    public function withObjectProperty(object $objectProperty): self
    {
        $message = clone $this;
        $message->objectProperty = $objectProperty;

        return $message;
    }

    public function withOrdinaryObject(NestedHandleableMessage $ordinaryObject): self
    {
        $message = clone $this;
        $message->ordinaryObject = $ordinaryObject;

        return $message;
    }

    public function withNestedObject(NestedHandleableMessage $nestedObject): self
    {
        $message = clone $this;
        $message->nestedObject = $nestedObject;

        return $message;
    }

    public function withConditionalMessage(int $firstConditionalProperty, int $secondConditionalProperty): self
    {
        return $this->withNestedObject(NestedHandleableMessage::createWithConditionalMessage(
            ConditionalMessage::createWithConditionalProperties($firstConditionalProperty, $secondConditionalProperty),
        ));
    }

    /** @param array<array-key,NestedItem> $items */
    public function withNestedArrayItems(array $items): self
    {
        $message = clone $this;
        $message->nestedArrayItems = $items;

        return $message;
    }

    /** @param ArrayObject<array-key,NestedItem> $items */
    public function withNestedIterableItems(ArrayObject $items): self
    {
        $message = clone $this;
        $message->nestedIterableItems = $items;

        return $message;
    }

    /** @param array<array-key,NestedItem> $justArray */
    public function withJustArray(array $justArray): self
    {
        $message = clone $this;
        $message->justArray = $justArray;

        return $message;
    }
}

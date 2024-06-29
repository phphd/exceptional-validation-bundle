<?php

declare(strict_types=1);

namespace PhPhD\ExceptionalValidationBundle\Tests;

use PhPhD\ExceptionalValidation\Tests\Stub\Exception\PropertyCapturableException;
use PhPhD\ExceptionalValidation\Tests\Stub\HandleableMessageStub;
use PhPhD\ExceptionalValidationBundle\Messenger\ExceptionalValidationMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Throwable;

/**
 * @covers \PhPhD\ExceptionalValidationBundle\Messenger\ExceptionalValidationMiddleware
 *
 * @internal
 */
final class ExceptionalValidationMiddlewareTest extends TestCase
{
    private ExceptionalValidationMiddleware $middleware;

    private MockObject $nextMiddleware;

    private StackMiddleware $stack;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        /** @var ExceptionalValidationMiddleware $middleware */
        $middleware = $container->get('phd_exceptional_validation');

        $this->middleware = $middleware;

        $this->nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $this->stack = new StackMiddleware([$this->middleware, $this->nextMiddleware]);
    }

    public function testReturnsResultEnvelopeWhenNoException(): void
    {
        $envelope = Envelope::wrap(HandleableMessageStub::create());
        $resultEnvelope = Envelope::wrap(new stdClass());

        $this->nextMiddleware
            ->method('handle')
            ->willReturnMap([[$envelope, $this->stack, $resultEnvelope]])
        ;

        $result = $this->middleware->handle($envelope, $this->stack);

        self::assertSame($resultEnvelope, $result);
    }

    public function testRethrowsHandlerFailedExceptionWhenNotCaught(): void
    {
        $envelope = Envelope::wrap(HandleableMessageStub::create());

        $previous = new PropertyCapturableException();
        $this->willThrow($exception = new HandlerFailedException($envelope, [$previous]));

        $this->expectExceptionObject($exception);

        $this->middleware->handle($envelope, $this->stack);
    }

    private function willThrow(Throwable $exception): void
    {
        $this->nextMiddleware
            ->method('handle')
            ->willThrowException($exception)
        ;
    }
}

<?php

namespace Tests\Actions\Events;

use DomainException;
use Exception;
use PHPUnit\Framework\TestCase;
use App\Actions\Events\HandlerChain;
use App\Actions\Events\EventHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

class HandlerChainTest extends TestCase
{
    private EventHandlerInterface $depositHandler;
    private HandlerChain $action;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->depositHandler = new class implements EventHandlerInterface {
            public function handle(RequestInterface $request, ResponseInterface $response): ResponseInterface
            {
                return $response;
            }
        };

        $handlers = [
            'deposit' => $this->depositHandler
        ];

        $this->action = new HandlerChain($handlers);
    }

    /**
     * @test
     * @throws DomainException
     */
    public function testGetHandlerReturnsHandlerWhenKeyExists(): void
    {
        $handler = $this->action->getHandler('deposit');

        $this->assertSame($this->depositHandler, $handler);
    }

    /**
     * @test
     */
    public function testGetHandlerThrowsExceptionWhenKeyDoesNotExist(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Operation type is not valid');

        $this->action->getHandler('invalid_operation');
    }
}
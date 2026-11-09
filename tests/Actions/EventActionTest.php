<?php

namespace Tests\Actions;

use App\Actions\EventAction;
use App\Actions\Events\EventHandlerInterface;
use App\Actions\Events\HandlerChain;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class EventActionTest extends TestCase
{
    private HandlerChain $handlerChainMock;
    private EventAction $action;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->handlerChainMock = $this->createMock(HandlerChain::class);
        $this->action = new EventAction($this->handlerChainMock);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    /**
     * @test
     */
    public function testInvokeDispatchesToResolvedHandler(): void
    {
        $payload = ['type' => 'deposit', 'destination' => '100', 'amount' => 10];

        $request = $this->requestFactory
            ->createServerRequest('POST', '/event')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($payload);

        $response = $this->responseFactory->createResponse();

        $expectedResponse = $this->responseFactory->createResponse(201);

        $handlerMock = $this->createMock(EventHandlerInterface::class);
        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($request, $response)
            ->willReturn($expectedResponse);

        $this->handlerChainMock
            ->expects($this->once())
            ->method('getHandler')
            ->with('deposit')
            ->willReturn($handlerMock);

        $result = ($this->action)($request, $response, []);

        $this->assertSame(201, $result->getStatusCode());
    }

    /**
     * @test
     */
    public function testInvokeUsesEmptyKeyWhenParsedBodyIsNull(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/event');
        $response = $this->responseFactory->createResponse();

        $expectedResponse = $this->responseFactory->createResponse(400);

        $fallbackHandlerMock = $this->createMock(EventHandlerInterface::class);
        $fallbackHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($request, $response)
            ->willReturn($expectedResponse);

        $this->handlerChainMock
            ->expects($this->once())
            ->method('getHandler')
            ->with(' ')
            ->willReturn($fallbackHandlerMock);

        $result = ($this->action)($request, $response, []);

        $this->assertSame(400, $result->getStatusCode());
    }
}
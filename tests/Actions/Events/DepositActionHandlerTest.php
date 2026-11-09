<?php

namespace Tests\Actions\Events;

use App\Actions\Events\DepositActionHandler;
use App\Models\Account;
use App\Repositories\AccountRepository;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class DepositActionHandlerTest extends TestCase
{
    private AccountRepository $accountRepositoryMock;
    private DepositActionHandler $handler;
    private ResponseFactory $responseFactory;
    private RequestFactory $requestFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepositoryMock = $this->createMock(AccountRepository::class);
        $this->responseFactory = new ResponseFactory();
        $this->requestFactory = new RequestFactory();

        $this->handler = new DepositActionHandler($this->accountRepositoryMock);
    }

    /**
     * @test
     */
    public function testHandleDepositsAmountAndReturns201WithDestinationPayload(): void
    {
        $account = new Account('100', 10);

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('deposit')
            ->with('100', 10)
            ->willReturn($account);

        $initialRequest = $this->requestFactory->createRequest("POST", "/event")
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody(['destination' => '100', 'amount' => 10]);

        $initialResponse = $this->responseFactory->createResponse();

        $response = $this->handler->handle($initialRequest, $initialResponse);

        $this->assertSame(201, $response->getStatusCode());

        $expectedBody = json_encode([
            'destination' => [
                'id' => '100',
                'balance' => 10,
            ],
        ]);

        $this->assertJsonStringEqualsJsonString($expectedBody, (string) $response->getBody());
    }

    /**
     * @test
     */
    public function testHandleReturns400WhenPayloadIsInvalid(): void
    {
        $this->accountRepositoryMock
            ->expects($this->never())
            ->method("deposit");

        $initialResponse = $this->responseFactory->createResponse();

        $initialRequest = $this->requestFactory->createRequest("POST", "/event")
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody(['amount' => 10]);

        $response = $this->handler->handle($initialRequest, $initialResponse);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $expectedBody = json_encode(['message' => 'Payload is invalid, please check and try again']);
        $this->assertJsonStringEqualsJsonString($expectedBody, (string) $response->getBody());
    }
}
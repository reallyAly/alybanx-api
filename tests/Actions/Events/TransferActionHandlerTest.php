<?php

declare(strict_types=1);

namespace Tests\Actions\Events;

use App\Actions\Events\TransferActionHandler;
use App\Models\Account;
use App\Repositories\AccountRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class TransferActionHandlerTest extends TestCase
{
    private AccountRepository $accountRepositoryMock;
    private TransferActionHandler $handler;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepositoryMock = $this->createMock(AccountRepository::class);
        $this->handler = new TransferActionHandler($this->accountRepositoryMock);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    /**
     * @test
     */
    public function testHandleTransfersAmountAndReturns201WithOriginAndDestination(): void
    {
        $payload = [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 15,
        ];

        $originAccount = new Account('100', 0);
        $destinationAccount = new Account('300', 15);

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('transfer')
            ->with('100', '300', 15)
            ->willReturn([
                'origin' => [
                    'id' => $originAccount->getId(),
                    'balance' => $originAccount->getBalance(),
                ],
                'destination' => [
                    'id' => $destinationAccount->getId(),
                    'balance' => $destinationAccount->getBalance(),
                ],
            ]);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/event')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($payload);

        $response = $this->responseFactory->createResponse();

        $result = $this->handler->handle($request, $response);

        $this->assertSame(201, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));

        $expectedBody = json_encode([
            'origin' => [
                'id' => '100',
                'balance' => 0,
            ],
            'destination' => [
                'id' => '300',
                'balance' => 15,
            ],
        ]);

        $this->assertJsonStringEqualsJsonString((string) $expectedBody, (string) $result->getBody());
    }

    /**
     * @test
     */
    public function testHandleReturns404WithZeroWhenAccountDoesNotExist(): void
    {
        $payload = [
            'type' => 'transfer',
            'origin' => '200',
            'destination' => '300',
            'amount' => 15,
        ];

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('transfer')
            ->with('200', '300', 15)
            ->willReturn(null);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/event')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($payload);

        $response = $this->responseFactory->createResponse();

        $result = $this->handler->handle($request, $response);

        $this->assertSame(404, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));
        $this->assertSame('0', (string) $result->getBody());
    }

    /**
     * @test
     */
    public function testHandleReturns422WhenOriginHasInsufficientFunds(): void
    {
        $payload = [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 1000,
        ];

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('transfer')
            ->with('100', '300', 1000)
            ->willThrowException(new \DomainException('Insufficient funds.'));

        $request = $this->requestFactory
            ->createServerRequest('POST', '/event')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($payload);

        $response = $this->responseFactory->createResponse();

        $result = $this->handler->handle($request, $response);

        $this->assertSame(422, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));

        $expectedBody = json_encode(['message' => 'Insufficient funds on origin account']);
        $this->assertJsonStringEqualsJsonString((string) $expectedBody, (string) $result->getBody());
    }

    #[DataProvider('invalidPayloadProvider')]
    public function testHandleReturns400WhenPayloadIsInvalid(array $payload): void
    {
        $this->accountRepositoryMock
            ->expects($this->never())
            ->method('transfer');

        $request = $this->requestFactory
            ->createServerRequest('POST', '/event')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($payload);

        $response = $this->responseFactory->createResponse();

        $result = $this->handler->handle($request, $response);

        $this->assertSame(400, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));

        $expectedBody = json_encode(['message' => 'Payload is invalid, please check and try again']);
        $this->assertJsonStringEqualsJsonString((string) $expectedBody, (string) $result->getBody());
    }

    /**
     * @return array<string, array{payload: array<string, mixed>}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'missing origin' => [
                'payload' => ['destination' => '300', 'amount' => 10],
            ],
            'missing destination' => [
                'payload' => ['origin' => '100', 'amount' => 10],
            ],
            'empty string origin' => [
                'payload' => ['origin' => '', 'destination' => '300', 'amount' => 10],
            ],
            'whitespaces origin' => [
                'payload' => ['origin' => '   ', 'destination' => '300', 'amount' => 10],
            ],
            'empty string destination' => [
                'payload' => ['origin' => '100', 'destination' => '', 'amount' => 10],
            ],
            'whitespaces destination' => [
                'payload' => ['origin' => '100', 'destination' => '   ', 'amount' => 10],
            ],
            'same origin and destination' => [
                'payload' => ['origin' => '100', 'destination' => '100', 'amount' => 10],
            ],
            'zero amount' => [
                'payload' => ['origin' => '100', 'destination' => '300', 'amount' => 0],
            ],
            'negative amount' => [
                'payload' => ['origin' => '100', 'destination' => '300', 'amount' => -10],
            ],
        ];
    }
}
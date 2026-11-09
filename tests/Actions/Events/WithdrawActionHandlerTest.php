<?php

declare(strict_types=1);

namespace Tests\Actions\Events;

use App\Actions\Events\WithdrawActionHandler;
use App\Models\Account;
use App\Repositories\AccountRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class WithdrawActionHandlerTest extends TestCase
{
    private AccountRepository $accountRepositoryMock;
    private WithdrawActionHandler $handler;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepositoryMock = $this->createMock(AccountRepository::class);
        $this->handler = new WithdrawActionHandler($this->accountRepositoryMock);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    /**
     * @test
     */
    public function testHandleWithdrawsAmountAndReturns201WithOriginPayload(): void
    {
        $payload = [
            'type' => 'withdraw',
            'origin' => '100',
            'amount' => 10,
        ];

        $account = new Account('100', 15);

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('withdraw')
            ->with('100', 10)
            ->willReturn($account);

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
            'type' => 'withdraw',
            'origin' => '200',
            'amount' => 10,
        ];

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('withdraw')
            ->with('200', 10)
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
    public function testHandleReturns422WhenInsufficientFunds(): void
    {
        $payload = [
            'type' => 'withdraw',
            'origin' => '100',
            'amount' => 50,
        ];

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('withdraw')
            ->with('100', 50)
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
            ->method('withdraw');

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
                'payload' => ['amount' => 10],
            ],
            'empty string origin' => [
                'payload' => ['origin' => '', 'amount' => 10],
            ],
            'single space origin' => [
                'payload' => ['origin' => ' ', 'amount' => 10],
            ],
            'zero amount' => [
                'payload' => ['origin' => '100', 'amount' => 0],
            ],
            'negative amount' => [
                'payload' => ['origin' => '100', 'amount' => -10],
            ],
            'completely empty payload' => [
                'payload' => [],
            ],
        ];
    }
}
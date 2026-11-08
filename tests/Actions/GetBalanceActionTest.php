<?php

declare(strict_types=1);

namespace Tests\Actions;

use App\Actions\GetBalanceAction;
use App\Models\Account;
use App\Repositories\AccountRepository;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class GetBalanceActionTest extends TestCase
{
    private AccountRepository $repository;
    private GetBalanceAction  $action;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(AccountRepository::class);
        $this->action = new GetBalanceAction($this->repository);
    }

    /**
     * @test
     */
    public function testReturns404WhenAccountIdQueryParamIsMissing(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/balance');
        $response = (new ResponseFactory())->createResponse();

        $this->repository->expects($this->never())->method('findById');

        $result = ($this->action)($request, $response, []);

        $this->assertSame(404, $result->getStatusCode());
        $this->assertSame('0', (string) $result->getBody());
    }

    /**
     * @test
     */
    public function testReturns404WhenAccountNotFound(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/balance')
            ->withQueryParams(['account_id' => '1234']);

        $response = (new ResponseFactory())->createResponse();

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1234)
            ->willReturn(null);

        $result = ($this->action)($request, $response, []);

        $this->assertSame(404, $result->getStatusCode());
        $this->assertSame('0', (string) $result->getBody());
    }

    /**
     * @test
     */
    public function testReturns200WithBalanceWhenAccountExists(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/balance')
            ->withQueryParams(['account_id' => '1234']);

        $response = (new ResponseFactory())->createResponse();

        $account = new Account(1234, 20);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1234)
            ->willReturn($account);

        $result = ($this->action)($request, $response, []);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('20', (string) $result->getBody());
    }
}
<?php

namespace Tests\Actions;

use App\Actions\ResetAction;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use App\Repositories\AccountRepository;
use Slim\Psr7\Factory\ServerRequestFactory;

class ResetActionTest extends TestCase
{
    /**
     * @test
     */
    public function testResetActionAndReturnsOk(): void
    {
        $repositoryMock = $this->createMock(AccountRepository::class);
        $repositoryMock->expects($this->once())
            ->method('reset');

        $action = new ResetAction($repositoryMock);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/reset');
        $response = (new ResponseFactory())->createResponse();

        $result = $action($request, $response, []);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('OK', (string) $result->getBody());
    }

    /**
     * @test
     */
    public function testResetActionReturnsIsNotOk(): void
    {
        $repositoryMock = $this->createMock(AccountRepository::class);
        $repositoryMock->expects($this->once())
            ->method('reset')
            ->willThrowException(new \PDOException("Error trying to connect"));

        $action = new ResetAction($repositoryMock);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/reset');
        $response = (new ResponseFactory())->createResponse();

        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('Error trying to connect');

        $action($request, $response, []);
    }
}
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\AccountRepository;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GetBalanceAction
{
    /**
     * GetBalanceAction constructor
     *
     * @param AccountRepository $accountRepository
     */
    public function __construct(
        private readonly AccountRepository $accountRepository
    ) {
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $params = $request->getQueryParams();

        if (!isset($params["account_id"])) {
            $response->getBody()->write("0");
            return $response->withStatus(404);
        }

        $account = $this->accountRepository->findById($params["account_id"]);

        if ($account === null) {
            $response->getBody()->write("0");
            return $response->withStatus(404);
        }

        $response->getBody()->write((string)$account->getBalance());

        return $response->withStatus(200);
    }
}
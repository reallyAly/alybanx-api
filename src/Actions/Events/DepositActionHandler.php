<?php

namespace App\Actions\Events;

use App\Repositories\AccountRepository;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Models\Account;

class DepositActionHandler implements EventHandlerInterface
{
    /**
     * DepositActionHandler constructor
     *
     * @param AccountRepository $accountRepository
     */
    public function __construct(
        private readonly AccountRepository $accountRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(
        RequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $payload = $request->getParsedBody();

        $destinationId = (string) ($payload['destination'] ?? '');
        $amount = (int) ($payload['amount'] ?? 0);

        if (strlen($destinationId) === 0 || $amount <= 0) {
            $response->getBody()->write(
                json_encode(["message" => "Payload is invalid, please check and try again"])
            );

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $account = $this->accountRepository->deposit($destinationId, $amount);

        $response->getBody()->write(
            json_encode([
                "destination" => [
                    Account::ID_KEY => $account->getId(),
                    Account::BALANCE_KEY => $account->getBalance()
                ]
             ])
        );

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
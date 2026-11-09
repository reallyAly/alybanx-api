<?php

namespace App\Actions\Events;

use App\Models\Account;
use App\Repositories\AccountRepository;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class WithdrawActionHandler implements EventHandlerInterface
{
    /**
     * WithdrawActionHandler constructor
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
    public function handle(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $request->getParsedBody();

        $destinationId = (string) ($payload['origin'] ?? '');
        $amount = (int) ($payload['amount'] ?? 0);

        if (strlen($destinationId) === 0 || $destinationId === " " || $amount <= 0) {
            $response->getBody()->write(
                json_encode(["message" => "Payload is invalid, please check and try again"])
            );

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $account = $this->accountRepository->withdraw($destinationId, $amount);
        } catch (\DomainException) {
            $response->getBody()->write(
                json_encode(["message" => "Insufficient funds on origin account"])
            );

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        if ($account === null) {
            $response->getBody()->write("0");

            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(
            json_encode([
                "origin" => [
                    Account::ID_KEY => $account->getId(),
                    Account::BALANCE_KEY => $account->getBalance()
                ]
            ])
        );

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
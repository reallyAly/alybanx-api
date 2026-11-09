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

        if (!$this->isValidPayload($payload)) {
            $response->getBody()->write(
                json_encode(["message" => "Payload is invalid, please check and try again"])
            );

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $account = $this->accountRepository->deposit(
            $payload["destination"],
            $payload["amount"]
        );

        $response->getBody()->write(
            json_encode([
                "destination" => $account
             ])
        );

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Validate payload
     *
     * @param array $payload
     * @return bool
     */
    protected function isValidPayload(array $payload): bool
    {
        $destination = trim((string) ($payload['destination'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);

        return $destination !== '' && $amount > 0;
    }
}
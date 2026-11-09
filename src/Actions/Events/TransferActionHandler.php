<?php

namespace App\Actions\Events;

use App\Models\Account;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Repositories\AccountRepository;

class TransferActionHandler implements EventHandlerInterface
{
    /**
     * DepositActionHandler constructor
     *
     * @param AccountRepository $accountRepository
     */
    public function __construct(
        private readonly AccountRepository $accountRepository
    ) {}

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

        try {
            $accounts = $this->accountRepository->transfer(
                $payload["origin"],
                $payload["destination"],
                $payload["amount"]
            );
        } catch (\DomainException) {
            $response->getBody()->write(
                json_encode(["message" => "Insufficient funds on origin account"])
            );

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        if ($accounts === null) {
            $response->getBody()->write("0");

            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(
            json_encode([
                "origin" => $accounts["origin"],
                "destination" => $accounts["destination"]
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
        $origin = trim((string) ($payload['origin'] ?? ''));
        $destination = trim((string) ($payload['destination'] ?? ''));
        $amount = $payload['amount'] ?? 0;

        return $origin !== '' && $destination !== '' && $origin !== $destination && $amount > 0 && is_integer($amount);
    }
}
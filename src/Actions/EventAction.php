<?php

namespace App\Actions;

use App\Actions\Events\HandlerChain;
use DomainException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class EventAction
{
    /** @var string */
    public const EMPTY_KEY = " ";

    /**
     * EventAction constructor
     *
     * @param HandlerChain $handlerChain
     */
    public function __construct(
        private readonly HandlerChain $handlerChain
    ) {
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $payload = $request->getParsedBody();

        $handlerKey = $payload["type"] ?? self::EMPTY_KEY;

        try {
            $handler = $this->handlerChain->getHandler($handlerKey);
        } catch (DomainException $e) {
            $response->getBody()->write(
                json_encode(["message" => $e->getMessage()])
            );

            return $response->withStatus(400);
        }

        return $handler->handle($request, $response);
    }
}
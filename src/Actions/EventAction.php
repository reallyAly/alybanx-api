<?php

namespace App\Actions;

use App\Actions\Events\HandlerChain;
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

        $handler = $this->handlerChain->getHandler($handlerKey);

        return $handler->handle($request, $response);
    }
}
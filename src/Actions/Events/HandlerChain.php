<?php

namespace App\Actions\Events;

use Exception;

class HandlerChain
{
    /**
     * HandlerChain constructor
     *
     * @param EventHandlerInterface[] $handlers
     */
    public function __construct(
        private readonly array $handlers = []
    ) {}

    /**
     * Get a handler by specific key
     *
     * @param string $handlerKey
     * @return EventHandlerInterface
     *
     * @throws Exception
     */
    public function getHandler(string $handlerKey): EventHandlerInterface
    {
        if (!isset($this->handlers[$handlerKey])) {
            throw new Exception("Operation is not valid");
        }

        return $this->handlers[$handlerKey];
    }
}
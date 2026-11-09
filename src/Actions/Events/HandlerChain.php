<?php

declare(strict_types=1);

namespace App\Actions\Events;

use DomainException;

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
     * @throws DomainException
     */
    public function getHandler(string $handlerKey): EventHandlerInterface
    {
        if (!isset($this->handlers[$handlerKey])) {
            throw new DomainException("Operation type is not valid");
        }

        return $this->handlers[$handlerKey];
    }
}
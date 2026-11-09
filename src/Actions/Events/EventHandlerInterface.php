<?php

namespace App\Actions\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface EventHandlerInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(
        RequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface;
}
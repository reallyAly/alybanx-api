<?php

namespace App\Actions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HelloWorldAction
{
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
        $response->getBody()->write(json_encode(["message" => "Hello World"]));
        return $response->withStatus(200);
    }
}
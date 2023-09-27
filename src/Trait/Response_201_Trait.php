<?php

declare(strict_types=1);

namespace src\Trait;

use Fig\Http\Message\StatusCodeInterface;
use Slim\Psr7\Response as Response;

trait Response_201_Trait
{
    public function response_201(string $status, array|string $message, array|string|bool|null $data): Response
    {
        $status = [
            'status' =>   $status,
            'message' => $message,
            'data' => $data
        ];

        $response = new Response(StatusCodeInterface::STATUS_CREATED);

        $response->getBody()->write(json_encode($status, JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }
}

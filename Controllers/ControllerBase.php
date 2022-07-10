<?php

namespace Controllers;

use PDO;
use System\HttpRequest;
use System\HttpResponse;

class ControllerBase {

    private HttpResponse $response;
    private PDO $db;

    public function __construct(HttpRequest $http)
    {
        $this->response = new HttpResponse($http);
        $this->db = $http->db;
    }

    protected function Ok($body = "", ?int $code = 200): HttpResponse
    {
        if (!($body instanceof string)) {
            $body = json_encode($body);
        }
        return $this->Response('HTTP/1.1 200 OK', $code, $body);
    }

    protected function NoContent(?int $code = 204): HttpResponse
    {
        return $this->Response('HTTP/1.1 204 No Content', $code);
    }

    protected function BadRequest(?int $code = 400): HttpResponse
    {
        return $this->Response('HTTP/1.1 400 Bad Request', $code);
    }

    protected function Unauthorized(?int $code = 401): HttpResponse
    {
        return $this->Response('HTTP/1.1 401 Unauthorized', $code);
    }

    protected function Forbidden(?int $code = 403): HttpResponse
    {
        return $this->Response('HTTP/1.1 403 Forbidden', $code);
    }

    protected function NotFound(?int $code = 404): HttpResponse
    {
        return $this->Response('HTTP/1.1 404 Not Found', $code);
    }

    private function Response(string $header, int $code, ?string $body = NULL): HttpResponse
    {
        $this->response->SetHeader($header);
        $this->response->SetBody($body);
        $this->response->SetCode($code);
        return $this->response;
    }

}
<?php

namespace System;

class HttpResponse {

    private HttpRequest $request;

    private string $header = "HTTP/1.1 200 OK";
    private int $code = 1;
    private ?string $body = NULL;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public function Print(): void
    {
        $this->SetHeaders();

        $response = [
            "uri" => $this->request->uri,
            "code" => $this->code,
            "body" => $this->body
        ];
        print json_encode($response, JSON_PRETTY_PRINT);
    }

    private function SetHeaders(): void
    {
        header($this->header, true);
        header('Content-Type: text/html; charset=utf-8');
    }

    public function SetCode(int $code): void
    {
        $this->code = $code;
    }

    public function SetBody(?string $body = NULL): void
    {
        $this->body = $body;
    }

    public function SetHeader(string $header): void
    {
        $this->header = $header;
    }

}
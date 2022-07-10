<?php

namespace System;

use PDO;

class HttpRequest {

    public string $uri;

    public ?string $controller = null;
    public ?string $action = null;
    public array $payload;

    public PDO $db;

    public function __construct()
    {
        $in = file_get_contents('php://input');
        $decoded = json_decode($in, true);

        $this->uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $query = isset($_GET['url']) && !empty($_GET['url']) ? explode('/', $_GET['url']) : null;
        if (!is_null($query)) {
            $this->controller = $query[0];
            if (count($query) > 1) {
                $this->action = $query[1];
            }
            unset($_GET['url']);
        }
        $this->payload = array_merge($_GET, $_POST, $decoded ?? array());
        $this->db = Database::Init();
        
        $_GET = $_POST = array();
    }

}
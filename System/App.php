<?php

namespace System;

use Controllers\ControllerBase;
use DateTime;
use ReflectionMethod;
use ReflectionParameter;
use System\Exceptions\WebException;

class App {

    private HttpRequest $http;
    private ControllerBase $controller;

    public function __construct()
    {
        $this->http = new HttpRequest();

        set_exception_handler(function($e) {
            $this->HandleException($e);
        });
    }

    /**
     * Process the api request
     *
     * @return void
     */
    public function ProcessRoute(): void
    {
        if (is_null($this->http->controller)) {
            throw new WebException('La petición no es válida.');
        }

        if (is_null($this->http->action)) {
            throw new WebException('La petición no es válida.');
        }

        $fileName = "Controllers/{$this->http->controller}Controller.php";
        if (!file_exists($fileName)) {
            throw new WebException('El controlador ingresado no existe.');
        }

        $file = "Controllers\\{$this->http->controller}Controller";
        $this->controller = new $file($this->http);

        if (!method_exists($this->controller, $this->http->action)) {
            throw new WebException('La acción ingresada no existe.');
        }

        $payload = $this->GetPayload();
        $response = call_user_func_array(array($this->controller, $this->http->action), $payload);
        $response->Print();
    }
    
    /**
     * Iterate over the request action parameters and parse the input
     *
     * @return array A collection of parameters
     */
    private function GetPayload(): array
    {
        $response = array();
        $method = new ReflectionMethod($this->controller, $this->http->action);

        foreach ($method->getParameters() as $param) {
            $idx = array_search($param->name, array_keys($this->http->payload));
            $refParam = new ReflectionParameter(array($this->controller, $this->http->action), $param->name);
            $isOptional = $refParam->isOptional();
            if ($idx === FALSE) {
                if (!$isOptional) {
                    throw new WebException("El parámetro {$param->name} es obligatorio.");
                }
                $response[$param->name] = $refParam->getDefaultValue();
            } else {
                $val = $this->http->payload[$param->name];
                if (!$this->TryParseProp($refParam->getType()->getName(), $val)) {
                    throw new WebException("El parámetro {$param->name} no es válido.");
                }
                $response[$param->name] = $val;
            }
        }

        return $response;
    }

    private function TryParseProp(string $type, string &$value): bool
    {
        switch($type) {
            case "string":
                $value = trim($value);
                return TRUE;

            case "int":
                if (!is_numeric($value)) {
                    return FALSE;
                }
                $value = intval($value);
                return TRUE;

            case "DateTime":
                $aux = FALSE;
                if (is_numeric($value)) {
                    $aux = DateTime::createFromFormat('U', $value);
                } else {
                    $aux = DateTime::createFromFormat("Y-m-d H:i:s", urldecode($value));
                }
                if ($aux === FALSE) {
                    return FALSE;
                }
                $value = $aux;
                return TRUE;

            case "bool":
                if (is_bool($value)) {
                    return TRUE;
                } else if (is_numeric($value)) {
                    if ($value != 0 && $value != 1)
                        return FALSE;
                } else {
                    if (strcasecmp($value, "true") !== 0 && strcasecmp($value, "false"))
                        return FALSE;
                }

                $value = $value == 1 || strcasecmp($value, "true") === 0 ? TRUE : FALSE;
                return TRUE;

            default:
                return FALSE;
        }
    }

    private function HandleException($e): void
    {
        $errorMessage = strlen(trim($e->getMessage())) > 0 ?
            trim($e->getMessage()) :
            'Ha ocurrido un error inesperado.';

        $response = new HttpResponse($this->http);
        $response->SetBody($errorMessage);
        $response->SetCode(-1);
        $response->Print();
    }

}
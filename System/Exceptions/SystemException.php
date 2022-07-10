<?php

namespace System\Exceptions;

use Exception;

class SystemException extends Exception {

    public function __construct()
    {
        parent::__construct('Ha ocurrido un error en el sistema.');
    }
    
}
<?php

use System\App;

spl_autoload_register(function ($class_name) {
    $a = str_replace("\\", "/", "{$class_name}.php");
    if (file_exists($a)) {
        require_once $a;
    }
});

$app = new App();
$app->ProcessRoute();
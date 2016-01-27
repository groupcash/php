<?php

use groupcash\php\Application;
use groupcash\php\impl\EccKeyService;
use groupcash\php\impl\McryptCryptoService;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\reflection\MethodActionGenerator;

require_once __DIR__ . '/vendor/autoload.php';

CliApplication::run(CliApplication::init(function (CliApplication $app) {
    (new MethodActionGenerator($app->actions, $app->types, $app->parser))
        ->fromObject(new Application(new EccKeyService(), new McryptCryptoService()));
}));
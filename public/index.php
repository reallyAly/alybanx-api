<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require '../vendor/autoload.php';
$dependencies = require __DIR__.'/../config/dependencies.php';

$containerBuilder = new ContainerBuilder();

$dependencies($containerBuilder);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(displayErrorDetails: true, logErrors: false, logErrorDetails: false);
$app->addBodyParsingMiddleware();

(require __DIR__ . '/../config/routes.php')($app);

$app->run();
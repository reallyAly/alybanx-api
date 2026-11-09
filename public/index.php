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

$displayErrors = ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production') === 'development';

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(displayErrorDetails: $displayErrors, logErrors: true, logErrorDetails: true);

(require __DIR__ . '/../config/routes.php')($app);

$app->run();
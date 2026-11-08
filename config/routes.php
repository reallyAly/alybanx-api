<?php

declare(strict_types=1);

use Slim\App;
use App\Actions\HelloWorldAction;

return function (App $app): void {
    $app->get('/', HelloWorldAction::class);
};
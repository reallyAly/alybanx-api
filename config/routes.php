<?php

declare(strict_types=1);

use Slim\App;
use App\Actions\ResetAction;

return function (App $app): void {
    $app->post('/V1/reset', ResetAction::class);
};
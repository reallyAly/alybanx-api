<?php

declare(strict_types=1);

use Slim\App;
use App\Actions\{ResetAction, GetBalanceAction, EventAction};

return function (App $app): void {
    $app->post('/reset', ResetAction::class);
    $app->get('/balance', GetBalanceAction::class);
    $app->post('/event', EventAction::class);
};
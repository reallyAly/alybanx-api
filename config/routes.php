<?php

declare(strict_types=1);

use Slim\App;
use App\Actions\{ResetAction, GetBalanceAction};

return function (App $app): void {
    $app->post('/reset', ResetAction::class);
    $app->get('/balance', GetBalanceAction::class);
};
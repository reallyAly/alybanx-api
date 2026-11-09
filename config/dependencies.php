<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use App\Actions\Events\HandlerChain;
use Psr\Container\ContainerInterface;
use App\Actions\Events\DepositActionHandler;
use App\Actions\Events\WithdrawActionHandler;
use App\Actions\Events\TransferActionHandler;

return function (ContainerBuilder $containerBuilder): void {
    $containerBuilder->addDefinitions([
        PDO::class => function (ContainerInterface $c): PDO {
            $databasePath = $_ENV['DB_PATH'] ?? __DIR__ . '/../storage/database.sqlite';

            $dir = dirname($databasePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $pdo = new PDO("sqlite:{$databasePath}");

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            $pdo->exec('PRAGMA foreign_keys = ON;');

            return $pdo;
        },
        HandlerChain::class => function (ContainerInterface $c): HandlerChain {
            return new HandlerChain(
                [
                    'deposit' => $c->get(DepositActionHandler::class),
                    'withdraw' => $c->get(WithdrawActionHandler::class),
                    'transfer' => $c->get(TransferActionHandler::class)
                ]
            );
        },
    ]);
};
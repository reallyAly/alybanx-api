<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
(require __DIR__ . '/../config/dependencies.php')($containerBuilder);
$container = $containerBuilder->build();

/** @var PDO $pdo */
$pdo = $container->get(PDO::class);

$pdo->exec('
    CREATE TABLE IF NOT EXISTS accounts (
        id bigint PRIMARY KEY,
        balance INTEGER NOT NULL DEFAULT 0
    );
');
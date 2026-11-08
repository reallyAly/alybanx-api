<?php

namespace App\Repositories;

use \PDO;

class AccountRepository
{
    /**
     * AccountRepository constructor
     *
     * @param PDO $pdo
     */
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Reset database data
     *
     * @return void
     */
    public function reset(): void
    {
        $this->pdo->exec("DELETE FROM accounts");
    }
}
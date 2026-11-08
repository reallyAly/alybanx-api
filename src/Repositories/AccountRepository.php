<?php

namespace App\Repositories;

use \PDO;
use App\Models\Account;

class AccountRepository
{
    /**
     * AccountRepository constructor
     *
     * @param PDO $pdo
     */
    public function __construct(
        private readonly PDO $pdo
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

    /**
     * Get account data by id
     *
     * @param string $accountId
     * @return Account|null
     */
    public function findById(string $accountId): ?Account
    {
        $stmt = $this->pdo->prepare("SELECT * FROM accounts WHERE id = :id");
        $stmt->bindParam(":".Account::ID_KEY, $accountId);

        $stmt->execute();
        $data = $stmt->fetch();

        return empty($data) ? null: Account::create($data);
    }
}
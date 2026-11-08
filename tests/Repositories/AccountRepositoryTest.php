<?php

namespace Tests\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Repositories\AccountRepository;

class AccountRepositoryTest extends TestCase
{
    private PDO $pdo;
    private AccountRepository $repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('
            CREATE TABLE accounts (
                id TEXT PRIMARY KEY,
                balance INTEGER NOT NULL DEFAULT 0
            );
        ');

        $this->repository = new AccountRepository($this->pdo);
    }

    /**
     * @test
     */
    public function testReset(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO accounts (id, balance) VALUES (:id, :balance)');
        $stmt->execute(['id' => 100, 'balance' => 20]);
        $stmt->execute(['id' => 200, 'balance' => 50]);

        $this->repository->reset();

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
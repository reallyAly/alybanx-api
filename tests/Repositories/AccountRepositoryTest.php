<?php

namespace Tests\Repositories;

use App\Models\Account;
use PDO;
use PDOException;
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
        $stmt->execute(['id' => "100", 'balance' => 20]);
        $stmt->execute(['id' => "200", 'balance' => 50]);

        $this->repository->reset();

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
        $this->assertSame(0, $count);
    }

    /**
     * @test
     */
    public function testFindByIdWhenAccountExists(): void
    {
        $accountIdMock = "1";
        $accountBalance = 10;

        $stmt = $this->pdo->prepare('INSERT INTO accounts (id, balance) VALUES (:id, :balance)');
        $stmt->execute(['id' => "1", 'balance' => 10]);

        $account = $this->repository->findById($accountIdMock);

        $this->assertSame($accountBalance, $account->getBalance());
    }

    /**
     * @test
     */
    public function testFindByIdWhenAccountNotExists(): void
    {
        $accountIdMock = "2";

        $account = $this->repository->findById($accountIdMock);

        $this->assertNull($account);
    }

    /**
     * @test
     */
    public function testSavePersistsAccountAndReturnsInstance(): void
    {
        $account = new Account('100', 50);

        $savedAccount = $this->repository->save($account);

        $this->assertInstanceOf(Account::class, $savedAccount);
        $this->assertSame('100', $savedAccount->getId());
        $this->assertSame(50, $savedAccount->getBalance());

        $stmt = $this->pdo->query("SELECT id, balance FROM accounts WHERE id = '100'");
        $row = $stmt->fetch();

        $this->assertIsArray($row);
        $this->assertSame('100', $row['id']);
        $this->assertSame(50, (int) $row['balance']);
    }

    /**
     * @test
     */
    public function testSaveReturnsNullWhenAccountCannotBeFoundAfterInsert(): void
    {
        $repoWithNullFind = new class($this->pdo) extends AccountRepository {
            public function findById(string $accountId): ?Account
            {
                return null;
            }
        };

        $account = new Account('100', 50);

        $savedAccount = $repoWithNullFind->save($account);
        $this->assertNull($savedAccount);
    }

    /**
     * @test
     */
    public function testUpdateModifiesExistingAccountBalanceAndReturnsUpdatedInstance(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO accounts (id, balance) VALUES (:id, :balance)');
        $stmt->execute([':id' => '100', ':balance' => 100]);

        $accountToUpdate = new Account('100', 250);
        $updatedAccount = $this->repository->update($accountToUpdate);

        $this->assertNotNull($updatedAccount);
        $this->assertSame('100', $updatedAccount->getId());
        $this->assertSame(250, $updatedAccount->getBalance());

        $checkStmt = $this->pdo->query("SELECT balance FROM accounts WHERE id = '100'");
        $persistedBalance = (int) $checkStmt->fetchColumn();
        $this->assertSame(250, $persistedBalance);
    }

    /**
     * @test
     */
    public function testUpdateReturnsNullWhenAccountDoesNotExist(): void
    {
        $nonExistentAccount = new Account('999', 50);
        $result = $this->repository->update($nonExistentAccount);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function testUpdateReturnsNullWhenFindByIdFailsAfterExecution(): void
    {
        $repoWithNullFind = new class($this->pdo) extends AccountRepository {
            public function findById(string $accountId): ?Account
            {
                return null;
            }
        };

        $account = new Account('100', 50);
        $result = $repoWithNullFind->update($account);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function testDepositCreatesAccountWhenAccountDoesNotExist(): void
    {
        $account = $this->repository->deposit('100', 10);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame('100', $account->getId());
        $this->assertSame(10, $account->getBalance());

        $persisted = $this->repository->findById('100');
        $this->assertNotNull($persisted);
        $this->assertSame(10, $persisted->getBalance());
    }

    /**
     * @test
     */
    public function testDepositIncrementsBalanceWhenAccountAlreadyExists(): void
    {
        $this->repository->deposit('100', 10);

        $updatedAccount = $this->repository->deposit('100', 20);

        $this->assertInstanceOf(Account::class, $updatedAccount);
        $this->assertSame('100', $updatedAccount->getId());
        $this->assertSame(30, $updatedAccount->getBalance());

        $persisted = $this->repository->findById('100');
        $this->assertNotNull($persisted);
        $this->assertSame(30, $persisted->getBalance());
    }

    /**
     * @test
     */
    public function testDepositRollsBackTransactionOnPdoException(): void
    {
        // Removing table to force PDOException
        $this->pdo->exec('DROP TABLE accounts');

        $this->expectException(PDOException::class);

        try {
            $this->repository->deposit('100', 50);
        } finally {
            $this->assertFalse($this->pdo->inTransaction());
        }
    }
}
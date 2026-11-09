<?php

namespace App\Repositories;

use \PDO;
use App\Models\Account;
use Throwable;

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

    /**
     * Save method
     *
     * @param Account $account
     * @return Account|null
     */
    public function save(Account $account): ?Account
    {
        $accountId = $account->getId();
        $balance   = $account->getBalance();

        $stmt = $this->pdo->prepare("INSERT INTO accounts (id, balance) VALUES (:id, :balance)");
        $stmt->bindParam(":".Account::ID_KEY, $accountId);
        $stmt->bindParam(":".Account::BALANCE_KEY, $balance);

        $stmt->execute();
        return $this->findById($accountId);
    }

    /**
     * Update method
     *
     * @param Account $account
     * @return Account|null
     */
    public function update(Account $account): ?Account
    {
        $accountId = $account->getId();
        $balance   = $account->getBalance();

        $stmt = $this->pdo->prepare("UPDATE accounts SET balance = :balance WHERE id = :id");
        $stmt->bindParam(":".Account::ID_KEY, $accountId);
        $stmt->bindParam(":".Account::BALANCE_KEY, $balance);

        $stmt->execute();
        return $this->findById($accountId);
    }

    /**
     * Deposit amount
     *
     * @param string $destinationId
     * @param int $amount
     *
     * @return Account|null
     * @throws Throwable
     */
    public function deposit(string $destinationId, int $amount): ?Account
    {
        $this->pdo->beginTransaction();

        try {
            $account = $this->findById($destinationId);

            if ($account === null) {
                $account = Account::create([
                    Account::ID_KEY => $destinationId,
                    Account::BALANCE_KEY => $amount
                ]);

                $account = $this->save($account);
            } else {
                $account->setBalance($amount+$account->getBalance());
                $account = $this->update($account);
            }

            $this->pdo->commit();

            return $account;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Withdraw from a specific account
     *
     * @param string $originId
     * @param int $amount
     *
     * @return Account|null
     * @throws Throwable
     */
    public function withdraw(string $originId, int $amount): ?Account
    {
        $this->pdo->beginTransaction();

        try {
            $account = $this->findById($originId);

            if ($account === null) {
                $this->pdo->rollBack();
                return null;
            }

            if ($amount > $account->getBalance()) {
                $this->pdo->rollBack();
                throw new \DomainException('Insufficient funds.');
            }

            $account->setBalance($account->getBalance() - $amount);
            $updatedAccount = $this->update($account);

            $this->pdo->commit();

            return $updatedAccount;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Transfer amount between accounts
     *
     * @param string $originId
     * @param string $destinationId
     * @param int $amount
     *
     * @return Account[]|null
     * @throws Throwable
     */
    public function transfer(
        string $originId,
        string $destinationId,
        int $amount
    ): ?array {
        $this->pdo->beginTransaction();

        try {
            $origin = $this->findById($originId);
            $destination = $this->findById($destinationId);

            if ($origin === null || $destination === null) {
                $this->pdo->rollBack();
                return null;
            }

            if ($origin->getBalance() < $amount) {
                $this->pdo->rollBack();
                throw new \DomainException('Insufficient funds.');
            }

            $origin->setBalance($origin->getBalance() - $amount);
            $originUpdated = $this->update($origin);

            $destination->setBalance($destination->getBalance() + $amount);
            $destinationUpdated = $this->update($destination);

            $this->pdo->commit();

            return [
                "destination" => $destinationUpdated,
                "origin" => $originUpdated
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
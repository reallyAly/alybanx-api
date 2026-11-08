<?php

namespace App\Models;

class Account
{
    /** @var string */
    public const ID_KEY = "id";

    /** @var string */
    public const BALANCE_KEY = "balance";

    /**
     * @param int $id
     * @param int $balance
     */
    public function __construct(
        private int $id,
        private int $balance = 0
    ) {
    }

    /**
     * Generate account object by data received by param ($data == [id => 0, balance => 0])
     *
     * @param array $data
     * @return Account
     */
    public static function create(array $data): Account
    {
        return new self(
            id: $data[self::ID_KEY],
            balance: $data[self::BALANCE_KEY]
        );
    }

    /**
     * @param int $id
     * @return Account
     */
    public function setId(int $id): Account
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $balance
     * @return Account
     */
    public function setBalance(int $balance): Account
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * @return int
     */
    public function getBalance(): int
    {
        return $this->balance;
    }
}
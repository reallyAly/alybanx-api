<?php

namespace App\Models;

use \JsonSerializable;

class Account implements JsonSerializable
{
    /** @var string */
    public const ID_KEY = "id";

    /** @var string */
    public const BALANCE_KEY = "balance";

    /**
     * @param string $id
     * @param int $balance
     */
    public function __construct(
        private string $id,
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
     * @param string $id
     * @return Account
     */
    public function setId(string $id): Account
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
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

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return [
            self::ID_KEY => $this->id,
            self::BALANCE_KEY => $this->balance
        ];
    }
}
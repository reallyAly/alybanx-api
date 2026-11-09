# Alybanx API

A lightweight, clean, and test-driven RESTful API built with **PHP 8.3**, **Slim Framework 4**, and native **SQLite PDO**. This application handles core account events (deposits, withdrawals, transfers, balance inquiries, and system state resets) with strict transaction isolation and comprehensive unit test coverage.

---

## 🛠️ Architecture & Tech Stack

- **PHP 8.3**
- **Slim Framework 4**
- **PHP-DI** (Dependency injection container)
- **SQLite (Native PDO)**
- **PHPUnit 10/11**
- **Docker & Docker Compose**

---

### Pre-requisites
Before running the project, ensure you have installed:
- Docker Engine 24.0+
- Docker Compose v2.0+ (recommended: `docker compose`)

**Note:** If your local environment is still using the legacy Compose (v1),
replace `docker compose` with `docker-compose` on bin/setup.sh script

---

## 🚀 Getting Started

### 1. Clone the Repository

```bash
git clone https://github.com/reallyAly/alybanx-api.git alybanx-api
cd alybanx-api
```

### 2. Configure Environment Variables

Duplicate the `.env.example` file to create your local `.env`:

```bash
cp .env.example .env
```

### 3. Run the script to set up the environment

```bash
chmod u+x bin/setup.sh
./bin/setup.sh
```

## 🧪 Running Tests

The test suite covers repositories (using fast, isolated SQLite in-memory databases `:memory:`). To run the tests, execute the following command:

### Run All Unit Tests

```bash
docker compose exec app composer test
```

## 🗂️ Project Structure

```text
├── .docker                  # Docker configuration files
├── .bin                     # Setup scripts
├── config/                  # App definitions, DI container, and routing
├── public/                  # Public webroot (index.php)
├── src/
│   ├── Actions/             # HTTP Controllers / Request Entrypoints
│   │   ├── Events/          # Handlers (Deposit, Withdraw, Transfer)
│   │   ├── GetBalanceAction.php
│   │   ├── EventAction.php
│   │   └── ResetAction.php
│   ├── Models/              # Domain models (Account entity)
│   ├── Repositories/        # Repositories 
├── storage/                 # SQLite database file
├── tests/                   # PHPUnit tests
├── composer.*               # Composer configuration files
├── docker-compose.yml       # Docker Compose configuration
├── phpunit.xml              # PHPUnit configuration
├── .env.example             # Example environment variables
└── README.md
```
## 📡 API Endpoints & Contract Reference

### 1. Reset State
Resets the database state

- **URL:** `POST /reset`
- **Response:** `200 OK`
  ```text
  OK
  ```

---

### 2. Get Account Balance
Retrieves the balance for an existing account.

- **URL:** `GET /balance?account_id={id}`
- **Responses:**
  - **Account Exists:** `200 OK`
    ```json
    20
    ```
  - **Account Not Found:** `404 Not Found`
    ```json
    0
    ```

---

### 3. Events
Processes financial operations based on the `type` property in the JSON payload.

- **URL:** `POST /event`
- **Headers:** `Content-Type: application/json`

#### A. Deposit into Account
Creates a new account if it does not exist, or adds to the balance of an existing one.

- **Payload:**
  ```json
  {
    "type": "deposit",
    "destination": "100",
    "amount": 10
  }
  ```
- **Response:** `201 Created`
  ```json
  {
    "destination": {
      "id": "100",
      "balance": 10
    }
  }
  ```

#### B. Withdraw from Account
Withdraws from an existing account.

- **Payload:**
  ```json
  {
    "type": "withdraw",
    "origin": "100",
    "amount": 5
  }
  ```
- **Responses:**
  - **Success:** `201 Created`
    ```json
    {
      "origin": {
        "id": "100",
        "balance": 5
      }
    }
    ```
  - **Account Not Found:** `404 Not Found`
    ```text
    0
    ```
  - **Insufficient Balance:** `422 Unprocessable Entity`
    ```json
    {
      "message": "Insufficient funds on origin account"
    }
    ```

#### C. Transfer Between Accounts
Atomically debits the origin account and credits the destination account.

- **Payload:**
  ```json
  {
    "type": "transfer",
    "origin": "100",
    "destination": "300",
    "amount": 15
  }
  ```
- **Responses:**
  - **Success:** `201 Created`
    ```json
    {
      "origin": {
        "id": "100",
        "balance": 0
      },
      "destination": {
        "id": "300",
        "balance": 15
      }
    }
    ```
  - **Origin Account Not Found:** `404 Not Found`
    ```text
    0
    ```
  - **Insufficient Balance:** `422 Unprocessable Entity`
    ```json
    {
      "message": "Insufficient funds on origin account"
    }
    ```
## Architecture & Design Decisions

- **Framework**: Slim 4 configured with PHP-DI for dependency injection and routing.
- **Persistence**: SQLite with native PDO transactions (`beginTransaction`, `commit`, `rollBack`) to guarantee ACID compliance during ledger operations.
- **Dependency Injection**: PHP-DI container wiring actions, repositories.
- **Error Handling**: Explicit separation of concerns:
  - `400 Bad Request`: Malformed payload or invalid parameters.
  - `404 Not Found`: Target account does not exist (returns body `0` where required by spec).
  - `422 Unprocessable Entity`: Domain rule violations.
- **Testing**: Thorough test suite using PHPUnit with in-memory SQLite (`:memory:`) ensuring zero side-effects and instant execution.
- **Dedicated Handlers**: Instead of consolidating all event operations (`deposit`, `withdraw`, `transfer`) into a monolithic controller with branching conditionals (`switch/case` or chained `if/else`), each transaction type is encapsulated into its own dedicated event handler (e.g., `DepositEventHandler`, `WithdrawEventHandler`, `TransferEventHandler`), injected via the dependency injection container.

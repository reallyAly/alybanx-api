#!/usr/bin/env bash

set -euo pipefail

echo "=========================================="
echo " Starting Application Setup"
echo "=========================================="

# 1. Check if Docker and Docker Compose are installed
command -v docker >/dev/null 2>&1 || { echo >&2 "[ERROR] Docker is not installed or not in PATH. Aborting."; exit 1; }
docker compose version >/dev/null 2>&1 || { echo >&2 "[ERROR] Docker Compose plugin is required. Aborting."; exit 1; }

# 2. Build containers and start services in the background
echo "--> Building and starting Docker containers..."
docker compose up -d --build

# 3. Install Composer dependencies inside the container
echo "--> Installing Composer dependencies..."
docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader

# 4. Run test suite to verify application integrity
echo "--> Running PHPUnit test suite..."
docker compose exec app composer test

# 5. Run migrate script to create database
docker compose exec app php bin/migrate.php

echo "=========================================="
echo " Setup completed successfully!"
echo " Server running at: http://localhost:YOUR_PORT"
echo "=========================================="
#!/usr/bin/env bash
# Local reproduction of CircleCI jobs without CircleCI CLI.
# Runs: php-lint, js-lint, php-tests, js-tests (matching .circleci/config.yml)
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_ROOT"

COMPOSER_CACHE_DIR="${HOME}/.cache/composer-scbd"
NPM_CACHE_DIR="${HOME}/.cache/npm-ci-scbd"
mkdir -p "$COMPOSER_CACHE_DIR" "$NPM_CACHE_DIR"

echo "[php-lint] Installing composer deps & running phpcs";
docker run --rm -v "$PWD":/project -v "$COMPOSER_CACHE_DIR":/tmp/composer-cache -e COMPOSER_CACHE_DIR=/tmp/composer-cache -w /project cimg/php:8.3-node \
  bash -lc "composer install --no-interaction --prefer-dist && composer run lint:php";

echo "[js-lint] Installing npm deps & running eslint";
docker run --rm -v "$PWD":/project -v "$NPM_CACHE_DIR":/home/circleci/.npm -w /project cimg/node:24.1 \
  bash -lc 'if [ -f package-lock.json ]; then npm ci; else npm install; fi && npm run lint:js';

echo "[php-tests] Running phpunit (no coverage)";
docker run --rm -v "$PWD":/project -w /project cimg/php:8.3-node \
  bash -lc "composer install --no-interaction --prefer-dist && composer run test";

echo "[js-tests] Running jest";
docker run --rm -v "$PWD":/project -v "$NPM_CACHE_DIR":/home/circleci/.npm -w /project cimg/node:24.1 \
  bash -lc 'if [ -f package-lock.json ]; then npm ci; else npm install; fi && npm test -- --ci --runInBand';

echo "All CircleCI job equivalents completed successfully.";

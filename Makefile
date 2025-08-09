# Makefile to mirror CircleCI jobs locally via Docker
# Usage examples:
#   make php-lint
#   make js-test
#   make ci        # run all lint + tests
#   make pull      # pre-pull images for speed

PHP_IMAGE = cimg/php:8.3-node
NODE_IMAGE = cimg/node:24.1
NPM_CACHE_DIR ?= $(HOME)/.cache/npm-ci-scbd
COMPOSER_CACHE_DIR ?= $(HOME)/.cache/composer-scbd
PROJECT_DIR := $(shell pwd)
NPM_INSTALL = if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Common docker run wrappers (mount project read/write)
DOCKER_PHP = docker run --rm \
	-v "$(PROJECT_DIR)":/project \
	-v "$(COMPOSER_CACHE_DIR)":/tmp/composer-cache \
	-e COMPOSER_CACHE_DIR=/tmp/composer-cache \
	-w /project $(PHP_IMAGE) bash -lc
DOCKER_NODE = docker run --rm \
	-v "$(PROJECT_DIR)":/project \
	-v "$(NPM_CACHE_DIR)":/home/circleci/.npm \
	-w /project $(NODE_IMAGE) bash -lc

.PHONY: help pull php-lint js-lint php-test js-test ci clean-node clean-php fix fix-php

help:
	@echo "Available targets:"
	@echo "  pull       - docker pull required images"
	@echo "  php-lint   - run phpcs (PSR12)"
	@echo "  js-lint    - run eslint"
	@echo "  php-test   - run phpunit (no coverage)"
	@echo "  js-test    - run jest tests"
	@echo "  ci         - run lint + tests (all)"
	@echo "  fix-php    - run phpcbf for PHP code style fixes"
	@echo "  fix        - alias: fix-php & eslint --fix"
	@echo "  clean-node - remove node_modules"
	@echo "  clean-php  - remove vendor"

pull:
	@mkdir -p "$(NPM_CACHE_DIR)" "$(COMPOSER_CACHE_DIR)"
	@docker pull $(PHP_IMAGE)
	@docker pull $(NODE_IMAGE)

php-lint:
	@echo "[php-lint]" && \
	$(DOCKER_PHP) "composer install --no-interaction --prefer-dist && composer run lint:php"

js-lint:
	@echo "[js-lint]" && \
	$(DOCKER_NODE) "$(NPM_INSTALL) && npm run lint:js"

php-test:
	@echo "[php-test]" && \
	$(DOCKER_PHP) "composer install --no-interaction --prefer-dist && composer run test"

js-test:
	@echo "[js-test]" && \
	$(DOCKER_NODE) "$(NPM_INSTALL) && npm test -- --ci --runInBand"

ci: php-lint js-lint php-test js-test
	@echo "All CI tasks succeeded."

fix-php:
	@echo "[phpcbf]" && \
	$(DOCKER_PHP) "composer install --no-interaction --prefer-dist && composer run lint:php-fix"

fix: fix-php
	@echo "[eslint --fix]" && \
	$(DOCKER_NODE) "$(NPM_INSTALL) && npm run lint:js-fix"

clean-node:
	rm -rf node_modules package-lock.json

clean-php:
	rm -rf vendor composer.lock

install-hooks:
	@bash scripts/install-git-hooks.sh
	@echo "Git hooks installed."

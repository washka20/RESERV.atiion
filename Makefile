SHELL := /bin/bash

COMPOSE_DEV     := docker compose -f docker-compose.yml -f docker-compose.dev.yml
COMPOSE_STAGING := docker compose -f docker-compose.yml -f docker-compose.staging.yml
COMPOSE_PROD    := docker compose -f docker-compose.yml -f docker-compose.prod.yml

.PHONY: help up down restart logs ps shell shell-frontend \
        test test-backend test-frontend test-arch test-e2e \
        lint lint-backend lint-frontend lint-fix \
        migrate seed fresh \
        composer-install npm-install build-frontend \
        staging-up prod-up

help:
	@echo "Targets:"
	@echo "  up / down / restart / logs / ps"
	@echo "  shell / shell-frontend"
	@echo "  test / test-backend / test-frontend / test-arch / test-e2e"
	@echo "  lint / lint-backend / lint-frontend / lint-fix"
	@echo "  migrate / seed / fresh"
	@echo "  composer-install / npm-install / build-frontend"
	@echo "  staging-up / prod-up"

# --- Dev lifecycle ---
up:
	$(COMPOSE_DEV) up -d

down:
	$(COMPOSE_DEV) down

restart: down up

logs:
	$(COMPOSE_DEV) logs -f

ps:
	$(COMPOSE_DEV) ps

shell:
	$(COMPOSE_DEV) exec php sh

shell-frontend:
	$(COMPOSE_DEV) exec frontend sh

# --- Tests ---
test: test-backend test-frontend

test-backend:
	$(COMPOSE_DEV) exec -T php ./vendor/bin/pest

test-frontend:
	$(COMPOSE_DEV) exec -T frontend npm run test:unit

test-arch:
	$(COMPOSE_DEV) exec -T php ./vendor/bin/pest --group=architecture

test-e2e:
	$(COMPOSE_DEV) exec -T frontend npx playwright test

# --- Lint ---
lint: lint-backend lint-frontend

lint-backend:
	$(COMPOSE_DEV) exec -T php ./vendor/bin/pint --test

lint-frontend:
	$(COMPOSE_DEV) exec -T frontend npm run lint

lint-fix:
	$(COMPOSE_DEV) exec -T php ./vendor/bin/pint
	$(COMPOSE_DEV) exec -T frontend npm run lint -- --fix

# --- DB ---
migrate:
	$(COMPOSE_DEV) exec -T php php artisan migrate

seed:
	$(COMPOSE_DEV) exec -T php php artisan db:seed

fresh:
	$(COMPOSE_DEV) exec -T php php artisan migrate:fresh --seed

# --- Package managers ---
composer-install:
	$(COMPOSE_DEV) exec -T php composer install

npm-install:
	$(COMPOSE_DEV) exec -T frontend npm install

build-frontend:
	$(COMPOSE_DEV) exec -T frontend npm run build

# --- Non-dev envs ---
staging-up:
	$(COMPOSE_STAGING) up -d

prod-up:
	$(COMPOSE_PROD) up -d

.PHONY: help test test-unit test-feature test-coverage lint format setup clean

# Docker container name
CONTAINER=addfc01e309b

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

test: ## Run all tests
	docker exec $(CONTAINER) php artisan test --compact

test-unit: ## Run unit tests only
	docker exec $(CONTAINER) php artisan test --compact --testsuite=Unit

test-feature: ## Run feature tests only
	docker exec $(CONTAINER) php artisan test --compact --testsuite=Feature

test-coverage: ## Run tests with coverage report
	docker exec $(CONTAINER) php artisan test --coverage --min=80

test-parallel: ## Run tests in parallel
	docker exec $(CONTAINER) php artisan test --parallel

test-filter: ## Run specific test (usage: make test-filter FILTER=testName)
	docker exec $(CONTAINER) php artisan test --filter=$(FILTER)

test-auth: ## Run authentication tests only
	docker exec $(CONTAINER) php artisan test tests/Feature/Auth/ tests/Unit/Actions/Auth/ tests/Unit/Services/ --compact

test-dusk: ## Run Dusk E2E tests
	docker exec $(CONTAINER) php artisan dusk

test-dusk-auth: ## Run Dusk authentication E2E tests
	docker exec $(CONTAINER) php artisan dusk tests/Browser/Auth/

lint: ## Check code style with Pint
	docker exec $(CONTAINER) vendor/bin/pint --test

format: ## Format code with Pint
	docker exec $(CONTAINER) vendor/bin/pint

fix: ## Alias for format
	docker exec $(CONTAINER) vendor/bin/pint

migrate: ## Run database migrations
	docker exec $(CONTAINER) php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations
	docker exec $(CONTAINER) php artisan migrate:fresh

seed: ## Seed the database
	docker exec $(CONTAINER) php artisan db:seed

migrate-seed: ## Migrate and seed database
	docker exec $(CONTAINER) php artisan migrate:fresh --seed

setup: ## Setup application (install dependencies, migrate, seed)
	docker exec $(CONTAINER) composer install
	docker exec $(CONTAINER) npm install
	docker exec $(CONTAINER) npm run build
	docker exec $(CONTAINER) php artisan key:generate
	docker exec $(CONTAINER) php artisan migrate:fresh --seed

clean: ## Clear all caches
	docker exec $(CONTAINER) php artisan optimize:clear
	docker exec $(CONTAINER) php artisan config:clear
	docker exec $(CONTAINER) php artisan cache:clear
	docker exec $(CONTAINER) php artisan view:clear
	docker exec $(CONTAINER) php artisan route:clear

optimize: ## Optimize application for production
	docker exec $(CONTAINER) php artisan optimize
	docker exec $(CONTAINER) php artisan config:cache
	docker exec $(CONTAINER) php artisan route:cache
	docker exec $(CONTAINER) php artisan view:cache

logs: ## Tail Laravel logs
	docker exec $(CONTAINER) tail -f storage/logs/laravel.log

logs-clear: ## Clear Laravel logs
	docker exec $(CONTAINER) sh -c "echo '' > storage/logs/laravel.log"

shell: ## Access container shell
	docker exec -it $(CONTAINER) bash

tinker: ## Open Laravel Tinker
	docker exec -it $(CONTAINER) php artisan tinker

routes: ## List all routes
	docker exec $(CONTAINER) php artisan route:list

db: ## Open database CLI
	docker exec -it $(CONTAINER) php artisan db

status: ## Show application status
	@echo "=== Container Status ==="
	@docker ps | grep $(CONTAINER) || echo "Container not running"
	@echo ""
	@echo "=== Application Info ==="
	@docker exec $(CONTAINER) php artisan --version
	@echo ""
	@echo "=== Database Status ==="
	@docker exec $(CONTAINER) php artisan migrate:status

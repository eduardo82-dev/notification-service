.PHONY: help build up down restart logs shell composer artisan migrate seed fresh test

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build all Docker images
	docker compose build --no-cache

up: ## Start all containers
	docker compose up -d

down: ## Stop all containers
	docker compose down

restart: ## Restart all containers
	docker compose restart

logs: ## Show logs (all containers)
	docker compose logs -f

logs-php: ## Show PHP-FPM logs
	docker compose logs -f app01-php

logs-worker: ## Show worker logs
	docker compose logs -f app01-worker

shell: ## Open bash shell in PHP container
	docker compose exec app01-php bash

composer-install: ## Install Composer dependencies
	docker compose exec app01-php composer install --no-interaction --prefer-dist

composer-update: ## Update Composer dependencies
	docker compose exec app01-php composer update

artisan: ## Run artisan command (make artisan CMD="migrate")
	docker compose exec app01-php php artisan $(CMD)

key-generate: ## Generate app key
	docker compose exec app01-php php artisan key:generate

migrate: ## Run migrations
	docker compose exec app01-php php artisan migrate --force

seed: ## Run database seeders
	docker compose exec app01-php php artisan db:seed --force

fresh: ## Fresh migration with seed
	docker compose exec app01-php php artisan migrate:fresh --seed --force

swagger: ## Generate Swagger docs
	docker compose exec app01-php php artisan l5-swagger:generate

test: ## Run tests
	docker compose exec app01-php php artisan test

test-unit: ## Run unit tests only
	docker compose exec app01-php ./vendor/bin/pest --group=unit

outbox: ## Run outbox processor once
	docker compose exec app01-php php artisan outbox:process --once

setup: up composer-install key-generate migrate seed swagger ## Full project setup
	@echo "✅ Notification Service is ready at http://localhost:8081"
	@echo "📖 API Docs: http://localhost:8081/api/documentation"
	@echo "🐰 RabbitMQ Management: http://localhost:15672"

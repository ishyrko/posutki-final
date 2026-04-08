.PHONY: test test-unit test-functional

test:
	docker compose exec php sh -lc "cd /var/www/backend && composer test"

test-unit:
	docker compose exec php sh -lc "cd /var/www/backend && composer test:unit"

test-functional:
	docker compose exec php sh -lc "cd /var/www/backend && composer test:functional"
.PHONY: help install up down restart logs backend-install backend-migrate migrate db-migrate backend-fixtures admin-user frontend-install frontend-dev frontend-build clean exchange-rates prod prod-up prod-down prod-restart prod-logs prod-migrate prod-check-env prod-full prod-build-frontend prod-backend-install prod-rebuild prod-fix-perms prod-fix-uploads prod-fix-assets-perms prod-admin-user

PROD_ENV_FILE = .env.prod
PROD_COMPOSE  = docker compose -f docker-compose.prod.yml --env-file $(PROD_ENV_FILE)
HOST_UID      = $(shell id -u)
HOST_GID      = $(shell id -g)

# Colors for output
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

help: ## Show this help message
	@echo '${GREEN}Available commands:${RESET}'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${YELLOW}%-20s${RESET} %s\n", $$1, $$2}'

install: ## Install and setup the entire project
	@echo "${GREEN}Installing project...${RESET}"
	make up
	make backend-install
	make frontend-install
	@echo "${GREEN}Installation complete!${RESET}"

up: ## Start all Docker containers
	@echo "${GREEN}Starting Docker containers...${RESET}"
	docker-compose up -d
	@echo "${GREEN}Containers started${RESET}"

down: ## Stop all Docker containers
	@echo "${YELLOW}Stopping Docker containers...${RESET}"
	docker-compose down

restart: ## Restart all Docker containers
	make down
	make up

logs: ## Show logs from all containers
	docker-compose logs -f

logs-backend: ## Show backend logs
	docker-compose logs -f php

logs-frontend: ## Show frontend logs
	docker-compose logs -f frontend

logs-nginx: ## Show nginx logs
	docker-compose logs -f nginx

# Backend commands

backend-install: ## Install backend dependencies
	@echo "${GREEN}Installing backend dependencies...${RESET}"
	docker-compose exec php composer install
	docker-compose exec php php bin/console doctrine:database:create --if-not-exists
	make backend-migrate

backend-migrate: ## Run database migrations (Doctrine)
	@echo "${GREEN}Running migrations...${RESET}"
	docker-compose exec php sh -lc "cd /var/www/backend && php bin/console doctrine:migrations:migrate --no-interaction"

migrate: backend-migrate ## Alias: run database migrations

backend-fixtures: ## Load database fixtures
	@echo "${GREEN}Loading fixtures...${RESET}"
	docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

admin-user: ## Create or promote admin (EMAIL=... PASSWORD=... optional FIRST= LAST=)
	@test -n "$(EMAIL)" && test -n "$(PASSWORD)" || (echo "${YELLOW}Usage: make admin-user EMAIL=you@example.com PASSWORD=secret [FIRST=Admin] [LAST=Admin]${RESET}" && exit 1)
	docker-compose exec -T php php bin/console app:create-admin "$(EMAIL)" "$(PASSWORD)" "$(or $(FIRST),Admin)" "$(or $(LAST),Admin)"

exchange-rates: ## Fetch exchange rates from NBRB and recalculate prices
	@echo "${GREEN}Updating exchange rates...${RESET}"
	docker-compose exec php php bin/console app:update-exchange-rates

backend-cache-clear: ## Clear backend cache
	docker-compose exec php php bin/console cache:clear

backend-shell: ## Open backend shell
	docker-compose exec php sh

backend-composer: ## Run composer command (usage: make backend-composer CMD="require package")
	docker-compose exec php composer $(CMD)

# Frontend commands

frontend-install: ## Install frontend dependencies
	@echo "${GREEN}Installing frontend dependencies...${RESET}"
	docker-compose exec frontend npm install

frontend-dev: ## Start frontend development server
	docker-compose exec frontend npm run dev

frontend-build: ## Build frontend for production
	docker-compose exec frontend npm run build

frontend-shell: ## Open frontend shell
	docker-compose exec frontend sh

frontend-npm: ## Run npm command (usage: make frontend-npm CMD="install package")
	docker-compose exec frontend npm $(CMD)

# Database commands

db-migrate: backend-migrate ## Run Doctrine migrations (same as make migrate)

db-shell: ## Open MySQL shell
	docker-compose exec mysql mysql -u rnb_user -prnb_password rnb_db

db-dump: ## Dump database to file
	docker-compose exec mysql mysqldump -u rnb_user -prnb_password rnb_db > dump.sql

db-restore: ## Restore database from dump.sql
	docker-compose exec -T mysql mysql -u rnb_user -prnb_password rnb_db < dump.sql

# Utility commands

clean: ## Clean all containers, volumes, and caches
	@echo "${YELLOW}Cleaning up...${RESET}"
	docker-compose down -v
	rm -rf backend/var/cache/*
	rm -rf frontend/.next
	rm -rf frontend/node_modules
	@echo "${GREEN}Cleanup complete${RESET}"

rebuild: ## Rebuild all Docker images
	docker-compose build --no-cache

test-backend: ## Run backend tests
	docker-compose exec php php bin/phpunit

test-frontend: ## Run frontend tests
	docker-compose exec frontend npm run test

# Production commands

prod-check-env: ## Validate production env and TLS cert paths
	@test -f $(PROD_ENV_FILE) || (echo "${YELLOW}Error: $(PROD_ENV_FILE) not found. Copy .env.prod.example -> .env.prod and fill in the values.${RESET}" && exit 1)
	@grep -q '^SSL_CERT_FULLCHAIN_PATH=' $(PROD_ENV_FILE) || (echo "${YELLOW}Error: SSL_CERT_FULLCHAIN_PATH is missing in $(PROD_ENV_FILE).${RESET}" && exit 1)
	@grep -q '^SSL_CERT_PRIVKEY_PATH=' $(PROD_ENV_FILE) || (echo "${YELLOW}Error: SSL_CERT_PRIVKEY_PATH is missing in $(PROD_ENV_FILE).${RESET}" && exit 1)
	@grep -q '^BASIC_AUTH_HTPASSWD_PATH=' $(PROD_ENV_FILE) || (echo "${YELLOW}Error: BASIC_AUTH_HTPASSWD_PATH is missing in $(PROD_ENV_FILE).${RESET}" && exit 1)
	@set -a; . ./$(PROD_ENV_FILE); set +a; \
	test -n "$$SSL_CERT_FULLCHAIN_PATH" || (echo "${YELLOW}Error: SSL_CERT_FULLCHAIN_PATH is empty.${RESET}" && exit 1); \
	test -n "$$SSL_CERT_PRIVKEY_PATH" || (echo "${YELLOW}Error: SSL_CERT_PRIVKEY_PATH is empty.${RESET}" && exit 1); \
	test -n "$$BASIC_AUTH_HTPASSWD_PATH" || (echo "${YELLOW}Error: BASIC_AUTH_HTPASSWD_PATH is empty.${RESET}" && exit 1); \
	test -f "$$SSL_CERT_FULLCHAIN_PATH" || (echo "${YELLOW}Error: certificate file not found at $$SSL_CERT_FULLCHAIN_PATH.${RESET}" && exit 1); \
	test -f "$$SSL_CERT_PRIVKEY_PATH" || (echo "${YELLOW}Error: private key file not found at $$SSL_CERT_PRIVKEY_PATH.${RESET}" && exit 1); \
	test -f "$$BASIC_AUTH_HTPASSWD_PATH" || (echo "${YELLOW}Error: htpasswd file not found at $$BASIC_AUTH_HTPASSWD_PATH.${RESET}" && exit 1)
	@mkdir -p docker/nginx/acme

prod: ## Fast production update (cached frontend build + restart + migrate)
	@make prod-check-env
	@echo "${GREEN}Building frontend image (with Docker cache)...${RESET}"
	$(PROD_COMPOSE) build frontend
	@echo "${GREEN}Restarting frontend and app nginx...${RESET}"
	$(PROD_COMPOSE) up -d --no-deps frontend nginx
	@make prod-fix-assets-perms
	@echo "${GREEN}Installing Symfony assets...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console assets:install public -n
	@make prod-fix-perms
	@make prod-fix-uploads
	@echo "${GREEN}Running migrations...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "${GREEN}Warmup cache...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console cache:warmup
	@echo "${GREEN}Fast production update complete!${RESET}"

prod-full: ## Full production deployment (all images + composer install)
	@make prod-check-env
	@echo "${GREEN}Building production images...${RESET}"
	$(PROD_COMPOSE) build
	@echo "${GREEN}Starting production containers...${RESET}"
	$(PROD_COMPOSE) up -d
	@echo "${GREEN}Installing backend dependencies...${RESET}"
	$(PROD_COMPOSE) exec -u $(HOST_UID):$(HOST_GID) php composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
	@make prod-fix-assets-perms
	@echo "${GREEN}Installing Symfony assets...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console assets:install public -n
	@make prod-fix-perms
	@make prod-fix-uploads
	@echo "${GREEN}Running migrations...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "${GREEN}Warming up cache...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console cache:warmup
	@echo "${GREEN}Full production deployment complete!${RESET}"

prod-build-frontend: ## Build only frontend production image (with cache)
	@make prod-check-env
	$(PROD_COMPOSE) build frontend

prod-backend-install: ## Reinstall backend prod dependencies
	@make prod-check-env
	$(PROD_COMPOSE) exec -u $(HOST_UID):$(HOST_GID) php composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
	@make prod-fix-assets-perms
	$(PROD_COMPOSE) exec php php bin/console assets:install public -n
	@make prod-fix-perms
	@make prod-fix-uploads

prod-rebuild: ## Rebuild all production images without cache
	@make prod-check-env
	$(PROD_COMPOSE) build --no-cache

prod-up: ## Start production containers without rebuilding
	@make prod-check-env
	$(PROD_COMPOSE) up -d

prod-down: ## Stop production containers
	$(PROD_COMPOSE) down

prod-restart: ## Restart production containers
	$(PROD_COMPOSE) restart

prod-logs: ## Stream production logs
	$(PROD_COMPOSE) logs -f

prod-migrate: ## Run DB migrations on production
	@make prod-fix-perms
	@make prod-fix-uploads
	$(PROD_COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction

prod-admin-user: ## Create or promote admin on production (EMAIL=... PASSWORD=... optional FIRST= LAST=)
	@make prod-check-env
	@test -n "$(EMAIL)" && test -n "$(PASSWORD)" || (echo "${YELLOW}Usage: make prod-admin-user EMAIL=you@example.com PASSWORD=secret [FIRST=Admin] [LAST=Admin]${RESET}" && exit 1)
	$(PROD_COMPOSE) exec -T php php bin/console app:create-admin "$(EMAIL)" "$(PASSWORD)" "$(or $(FIRST),Admin)" "$(or $(LAST),Admin)"

prod-fix-perms: ## Fix backend var permissions for php-fpm user
	$(PROD_COMPOSE) exec -u root php sh -lc 'mkdir -p /var/www/backend/var/cache /var/www/backend/var/log /var/www/backend/var/sessions && chown -R www-data:www-data /var/www/backend/var && chmod -R ug+rwX /var/www/backend/var'

prod-fix-uploads: ## Ensure uploads directory exists and is writable
	$(PROD_COMPOSE) exec -u root php sh -lc 'mkdir -p /var/www/backend/public/uploads && chown -R www-data:www-data /var/www/backend/public/uploads && chmod -R 775 /var/www/backend/public/uploads'

prod-fix-assets-perms: ## Ensure Symfony assets dir exists and is writable
	$(PROD_COMPOSE) exec -u root php sh -lc 'mkdir -p /var/www/backend/public/bundles && chown -R www-data:www-data /var/www/backend/public/bundles && chmod -R 775 /var/www/backend/public/bundles'

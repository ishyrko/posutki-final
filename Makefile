.PHONY: test test-unit test-functional

test:
	docker compose exec php sh -lc "cd /var/www/backend && composer test"

test-unit:
	docker compose exec php sh -lc "cd /var/www/backend && composer test:unit"

test-functional:
	docker compose exec php sh -lc "cd /var/www/backend && composer test:functional"
.PHONY: help install up down restart logs backend-install backend-migrate migrate db-migrate backend-seed-demo admin-user frontend-install frontend-dev frontend-build frontend-build-cpanel frontend-build-cpanel-prod frontend-build-cpanel-verify-3g frontend-export-cpanel frontend-cpanel-clean clean exchange-rates sync-metro-proximity prod prod-up prod-down prod-restart prod-logs prod-migrate prod-exchange-rates prod-sync-metro-proximity prod-check-env prod-full prod-build-frontend prod-backend-install prod-rebuild prod-fix-perms prod-fix-uploads prod-fix-assets-perms prod-admin-user prod-edge-up prod-edge-full

PROD_ENV_FILE = .env.prod
CPANEL_ENV_FILE = .env.cpanel
CPANEL_COMPOSE  = docker compose -f docker-compose.yml -f docker-compose.cpanel-build.yml
-include $(CPANEL_ENV_FILE)
PROD_COMPOSE  = docker compose -f docker-compose.prod.yml --env-file $(PROD_ENV_FILE)
PROD_EDGE_ARGS = $(shell set -a; . ./$(PROD_ENV_FILE) 2>/dev/null; set +a; [ "$${PROD_ENABLE_EDGE_PROXY:-0}" = "1" ] && echo --profile edge)
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

backend-seed-demo: ## Replace all listings with demo data (regions/cities unchanged). Не использовать doctrine:fixtures:load — ломает справочники.
	@echo "${GREEN}Seeding demo properties (app:seed-demo-properties)...${RESET}"
	docker-compose exec php sh -lc "cd /var/www/backend && php bin/console app:seed-demo-properties"

admin-user: ## Create or promote admin (EMAIL=... PASSWORD=... optional FIRST= LAST=)
	@test -n "$(EMAIL)" && test -n "$(PASSWORD)" || (echo "${YELLOW}Usage: make admin-user EMAIL=you@example.com PASSWORD=secret [FIRST=Admin] [LAST=Admin]${RESET}" && exit 1)
	docker-compose exec -T php php bin/console app:create-admin "$(EMAIL)" "$(PASSWORD)" "$(or $(FIRST),Admin)" "$(or $(LAST),Admin)"

exchange-rates: ## Fetch exchange rates from NBRB and recalculate prices
	@echo "${GREEN}Updating exchange rates...${RESET}"
	docker-compose exec php php bin/console app:update-exchange-rates

sync-metro-proximity: ## Recalculate metro proximity for all properties
	@echo "${GREEN}Recalculating metro proximity...${RESET}"
	docker-compose exec php php bin/console app:sync-metro-proximity --no-interaction

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

define verify_cpanel_next
	@test -f frontend/$(1)/BUILD_ID || (echo "Missing frontend/$(1)/BUILD_ID" && exit 1)
	@test -d frontend/$(1)/static/chunks || (echo "Missing frontend/$(1)/static — incomplete build, do not upload" && exit 1)
	@test -d frontend/$(1)/server || (echo "Missing frontend/$(1)/server — incomplete build, do not upload" && exit 1)
	@if [ -d frontend/$(1)/dev ]; then echo "WARN: frontend/$(1)/dev present — remove before upload (rm -rf frontend/$(1)/dev)"; fi
endef

frontend-cpanel-clean: ## Remove .next before cPanel build (stops dev container if running)
	docker-compose stop frontend 2>/dev/null || true
	$(CPANEL_COMPOSE) run --rm --no-deps frontend sh -c 'mkdir -p /app/.next && find /app/.next -mindepth 1 -delete'

# Если собирали через `docker-compose exec` (dev-контейнер), .next в volume — скопировать на хост.
frontend-export-cpanel: ## Copy .next from Docker volume to frontend/.next-cpanel
	rm -rf frontend/.next-cpanel
	docker cp posutki_frontend:/app/.next frontend/.next-cpanel
	$(call verify_cpanel_next,.next-cpanel)
	@echo "${GREEN}Ready to upload: frontend/.next-cpanel ($(shell du -sh frontend/.next-cpanel 2>/dev/null | cut -f1))${RESET}"
	@echo "On server: rm -rf .next && upload .next-cpanel as .next"

frontend-build-cpanel: ## Build frontend with low-memory profile (run in Docker, upload .next to cPanel)
	$(MAKE) frontend-cpanel-clean
	$(CPANEL_COMPOSE) run --rm --no-deps -e NODE_ENV=production frontend npm run build:low-memory
	$(call verify_cpanel_next,.next)
	@echo "${GREEN}Build output: frontend/.next ($(shell du -sh frontend/.next 2>/dev/null | cut -f1))${RESET}"

frontend-build-cpanel-prod: ## Prod NEXT_PUBLIC_* for cPanel (vars in .env.cpanel or VAR=value on CLI)
	@test -n "$(NEXT_PUBLIC_API_URL)" || (echo "Set NEXT_PUBLIC_API_URL (e.g. in $(CPANEL_ENV_FILE))" && exit 1)
	$(MAKE) frontend-cpanel-clean
	$(CPANEL_COMPOSE) run --rm --no-deps \
		-e NODE_ENV=production \
		-e BACKEND_INTERNAL_URL="$(BACKEND_INTERNAL_URL)" \
		-e NEXT_PUBLIC_API_URL="$(NEXT_PUBLIC_API_URL)" \
		-e NEXT_PUBLIC_SITE_URL="$(NEXT_PUBLIC_SITE_URL)" \
		-e NEXT_PUBLIC_GOOGLE_CLIENT_ID="$(NEXT_PUBLIC_GOOGLE_CLIENT_ID)" \
		-e NEXT_PUBLIC_RECAPTCHA_SITE_KEY="$(NEXT_PUBLIC_RECAPTCHA_SITE_KEY)" \
		-e NEXT_PUBLIC_YANDEX_MAPS_API_KEY="$(NEXT_PUBLIC_YANDEX_MAPS_API_KEY)" \
		-e NEXT_PUBLIC_YANDEX_GEOCODER_API_KEY="$(NEXT_PUBLIC_YANDEX_GEOCODER_API_KEY)" \
		-e REVALIDATION_SECRET="$(REVALIDATION_SECRET)" \
		frontend npm run build:low-memory
	$(call verify_cpanel_next,.next)
	@echo "${GREEN}Build output: frontend/.next ($(shell du -sh frontend/.next 2>/dev/null | cut -f1))${RESET}"

frontend-build-cpanel-verify-3g: ## Smoke test: prod build must pass with Docker mem_limit=3g
	$(MAKE) frontend-build-cpanel-prod

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

prod-check-env: ## Validate production env (TLS paths only when PROD_ENABLE_EDGE_PROXY=1)
	@test -f $(PROD_ENV_FILE) || (echo "${YELLOW}Error: $(PROD_ENV_FILE) not found. Copy .env.prod.example -> .env.prod and fill in the values.${RESET}" && exit 1)
	@grep -q '^APP_HTTP_BIND=' $(PROD_ENV_FILE) || (echo "${YELLOW}Error: APP_HTTP_BIND is missing in $(PROD_ENV_FILE) (e.g. 127.0.0.1:9080).${RESET}" && exit 1)
	@set -a; . ./$(PROD_ENV_FILE); set +a; \
	test -n "$$APP_HTTP_BIND" || (echo "${YELLOW}Error: APP_HTTP_BIND is empty.${RESET}" && exit 1); \
	if [ "$${PROD_ENABLE_EDGE_PROXY:-0}" = "1" ]; then \
	  grep -q '^SSL_CERT_FULLCHAIN_PATH=' $(PROD_ENV_FILE) || (echo "${YELLOW}Error: SSL_CERT_FULLCHAIN_PATH is missing in $(PROD_ENV_FILE).${RESET}" && exit 1); \
	  grep -q '^SSL_CERT_PRIVKEY_PATH=' $(PROD_ENV_FILE) || (echo "${YELLOW}Error: SSL_CERT_PRIVKEY_PATH is missing in $(PROD_ENV_FILE).${RESET}" && exit 1); \
	  grep -q '^BASIC_AUTH_HTPASSWD_PATH=' $(PROD_ENV_FILE) || (echo "${YELLOW}Error: BASIC_AUTH_HTPASSWD_PATH is missing in $(PROD_ENV_FILE).${RESET}" && exit 1); \
	  test -n "$$SSL_CERT_FULLCHAIN_PATH" || (echo "${YELLOW}Error: SSL_CERT_FULLCHAIN_PATH is empty.${RESET}" && exit 1); \
	  test -n "$$SSL_CERT_PRIVKEY_PATH" || (echo "${YELLOW}Error: SSL_CERT_PRIVKEY_PATH is empty.${RESET}" && exit 1); \
	  test -n "$$BASIC_AUTH_HTPASSWD_PATH" || (echo "${YELLOW}Error: BASIC_AUTH_HTPASSWD_PATH is empty.${RESET}" && exit 1); \
	  test -f "$$SSL_CERT_FULLCHAIN_PATH" || (echo "${YELLOW}Error: certificate file not found at $$SSL_CERT_FULLCHAIN_PATH.${RESET}" && exit 1); \
	  test -f "$$SSL_CERT_PRIVKEY_PATH" || (echo "${YELLOW}Error: private key file not found at $$SSL_CERT_PRIVKEY_PATH.${RESET}" && exit 1); \
	  test -f "$$BASIC_AUTH_HTPASSWD_PATH" || (echo "${YELLOW}Error: htpasswd file not found at $$BASIC_AUTH_HTPASSWD_PATH.${RESET}" && exit 1); \
	  mkdir -p docker/nginx/acme; \
	fi

prod: ## Fast production update (cached frontend build + restart + migrate)
	@make prod-check-env
	@echo "${GREEN}Building frontend image (with Docker cache)...${RESET}"
	$(PROD_COMPOSE) build frontend
	@echo "${GREEN}Restarting frontend, app nginx and cron...${RESET}"
	$(PROD_COMPOSE) up -d --no-deps frontend nginx
	$(PROD_COMPOSE) up -d --no-deps --force-recreate cron
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
	$(PROD_COMPOSE) $(PROD_EDGE_ARGS) up -d
	$(PROD_COMPOSE) up -d --no-deps --force-recreate cron
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
	$(PROD_COMPOSE) $(PROD_EDGE_ARGS) up -d

prod-edge-up: ## Start production with built-in TLS reverse-proxy (:80/:443)
	@grep -q '^PROD_ENABLE_EDGE_PROXY=1' $(PROD_ENV_FILE) || (echo "${YELLOW}Set PROD_ENABLE_EDGE_PROXY=1 in $(PROD_ENV_FILE) first.${RESET}" && exit 1)
	@make prod-check-env
	$(PROD_COMPOSE) --profile edge up -d

prod-edge-full: ## Full deploy with built-in TLS reverse-proxy (:80/:443)
	@grep -q '^PROD_ENABLE_EDGE_PROXY=1' $(PROD_ENV_FILE) || (echo "${YELLOW}Set PROD_ENABLE_EDGE_PROXY=1 in $(PROD_ENV_FILE) first.${RESET}" && exit 1)
	@make prod-full

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

prod-exchange-rates: ## Fetch NBRB rates and recalculate prices (production)
	@echo "${GREEN}Updating exchange rates...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console app:update-exchange-rates --no-interaction

prod-sync-metro-proximity: ## Recalculate metro proximity for all properties (production)
	@echo "${GREEN}Recalculating metro proximity...${RESET}"
	$(PROD_COMPOSE) exec php php bin/console app:sync-metro-proximity --no-interaction

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

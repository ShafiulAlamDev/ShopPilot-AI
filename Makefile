# =============================================================================
# ShopPilot AI — Makefile
# Shortcuts for Docker Compose operations
# =============================================================================

.PHONY: up down build restart logs shell artisan migrate fresh seed test \
        queue horizon npm pnpm tools clean

# --- Start all core services (detached) ---
up:
	docker compose up -d

# --- Start with optional GUI tools (phpMyAdmin, Redis Commander) ---
tools:
	docker compose --profile tools up -d

# --- Stop all services ---
down:
	docker compose down

# --- Rebuild all images and start ---
build:
	docker compose build --no-cache && docker compose up -d

# --- Restart a specific service (usage: make restart s=php) ---
restart:
	docker compose restart $(s)

# --- Tail logs (usage: make logs s=php) ---
logs:
	docker compose logs -f $(s)

# --- Shell into PHP container ---
shell:
	docker compose exec php bash

# --- Run any artisan command (usage: make artisan c="migrate --seed") ---
artisan:
	docker compose exec php php artisan $(c)

# --- Run migrations ---
migrate:
	docker compose exec php php artisan migrate

# --- Fresh migration + seed ---
fresh:
	docker compose exec php php artisan migrate:fresh --seed

# --- Run seeders only ---
seed:
	docker compose exec php php artisan db:seed

# --- Run PHPUnit tests ---
test:
	docker compose exec php php artisan test

# --- Run npm in frontend container (usage: make npm c="install") ---
npm:
	docker compose exec frontend npm $(c)

# --- Generate app key ---
key:
	docker compose exec php php artisan key:generate

# --- Clear all Laravel caches ---
cache-clear:
	docker compose exec php php artisan cache:clear
	docker compose exec php php artisan config:clear
	docker compose exec php php artisan route:clear
	docker compose exec php php artisan view:clear

# --- Remove all containers, volumes, and images ---
clean:
	docker compose down -v --rmi local

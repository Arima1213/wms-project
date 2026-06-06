.PHONY: help build up down restart logs logs-app logs-nginx logs-postgres clean ps \
	install setup migrate seed horizon queue reverb \
	frontend-build frontend-up

COMPOSE := docker compose -f docker-compose.yml
ENGINE := $(COMPOSE) --env-file .env

help:
	@echo "WMS Multi-Gudang - Available Commands"
	@echo "======================================"
	@echo "  make install       # Install dependencies (composer + npm)"
	@echo "  make setup         # Initial setup: env, migrate, seed"
	@echo "  make up            # Start all services"
	@echo "  make down          # Stop all services"
	@echo "  make restart       # Restart all services"
	@echo "  make logs          # Tail all logs"
	@echo "  make logs-app      # Tail app logs"
	@echo "  make ps            # Show running containers"
	@echo "  make clean         # Stop + remove containers + volumes"
	@echo "  make migrate       # Run backend migrations"
	@echo "  make seed          # Seed database"
	@echo "  make horizon       # Open Horizon dashboard (port 8000/horizon)"
	@echo "  make queue # Run queue worker (foreground)"
	@echo "  make reverb # Start Reverb websocket (foreground)"
	@echo "  make frontend-up   # Start frontend dev server"

build:
	$(ENGINE) build --no-cache

up:
	$(ENGINE) up -d
	@echo "Waiting for postgres..."
	@sleep 5
	@echo "All services started. Access:"
	@echo "  Frontend : http://localhost:5173"
	@echo "  Backend : http://localhost:8000"
	@echo "  MinIO   : http://localhost:9001"
	@echo "  Meili   : http://localhost:7700"

down:
	$(ENGINE) down

restart: down up

logs:
	$(ENGINE) logs --follow --tail=50

logs-app:
	$(ENGINE) logs -f app

logs-nginx:
	$(ENGINE) logs -f nginx

logs-postgres:
	$(ENGINE) logs -f postgres

ps:
	$(ENGINE) ps

clean:
	$(ENGINE) down -v --remove-orphans
	@echo "Cleaned up containers and volumes"

install:
	@echo "Installing backend dependencies..."
	cd backend && docker compose -f Dockerfile run --rm app composer install
	@echo "Installing frontend dependencies..."
	cd frontend && docker compose -f Dockerfile run --rm node npm install

setup: up
	@echo "Copying env files..."
	@cp backend/.env.example backend/.env 2>/dev/null || true
	@cp frontend/.env.example frontend/.env 2>/dev/null || true
	@echo "Generating app keys..."
	$(ENGINE) exec app php artisan key:generate
	$(ENGINE) exec app php artisan jwt:secret --force
	$(ENGINE) exec app php artisan storage:link
	@echo "Running migrations..."
	$(ENGINE) exec app php artisan migrate --force
	@echo "Setup complete!"

migrate:
	$(ENGINE) exec app php artisan migrate --force

migrate-refresh:
	$(ENGINE) exec app php artisan migrate:fresh --force --seed

seed:
	$(ENGINE) exec app php artisan db:seed --force

horizon:
	@echo "Horizon available at http://localhost:8000/horizon"

queue:
	$(ENGINE) exec app php artisan queue:work

reverb:
	$(ENGINE) exec reverb php artisan reverb:start --host=0.0.0.0

frontend-up:
	$(ENGINE) up -d frontend
	@echo "Frontend dev server at http://localhost:5173"

# 🐳 Docker Installation & Setup Guide
## ShopPilot AI

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Install Docker](#2-install-docker)
   - [Windows](#windows)
   - [macOS](#macos)
   - [Linux (Ubuntu)](#linux-ubuntu)
3. [Verify Installation](#3-verify-installation)
4. [Project Setup](#4-project-setup)
5. [Start the Application](#5-start-the-application)
6. [First-Time Bootstrap](#6-first-time-bootstrap)
7. [Access the Services](#7-access-the-services)
8. [Daily Workflow](#8-daily-workflow)
9. [Common Commands](#9-common-commands)
10. [Stopping & Cleanup](#10-stopping--cleanup)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Prerequisites

| Platform | Requirement |
|---|---|
| **Windows** | Windows 10/11 (64-bit), Build 19041 or later, WSL 2 enabled |
| **macOS** | macOS 12 Monterey or later (Intel or Apple Silicon) |
| **Linux** | Ubuntu 20.04+, Debian 11+, or any modern 64-bit distro |
| **RAM** | Minimum 8 GB (16 GB recommended) |
| **Disk** | At least 10 GB free space |

---

## 2. Install Docker

### Windows

#### Step 1 — Enable WSL 2

Open **PowerShell as Administrator** and run:

```powershell
wsl --install
```

Restart your computer when prompted. If WSL is already installed, ensure version 2:

```powershell
wsl --set-default-version 2
```

#### Step 2 — Download Docker Desktop

Go to: **https://www.docker.com/products/docker-desktop/**

Click **"Download for Windows"** and run the installer.

#### Step 3 — Install

During installation:
- ✅ Check **"Use WSL 2 instead of Hyper-V"**
- ✅ Check **"Add shortcut to desktop"**
- Click **Install** → **Close and restart**

#### Step 4 — Start Docker Desktop

After restart, open **Docker Desktop** from the Start Menu.
Wait for the 🐳 whale icon in the system tray to show **"Running"**.

---

### macOS

#### Step 1 — Download Docker Desktop

Go to: **https://www.docker.com/products/docker-desktop/**

- **Apple Silicon (M1/M2/M3):** Download for Mac – Apple Silicon
- **Intel Mac:** Download for Mac – Intel Chip

#### Step 2 — Install

Open the downloaded `.dmg` and drag **Docker** to your **Applications** folder.

#### Step 3 — Start

Open Docker from Applications. Accept the terms when prompted.
Wait for the 🐳 icon in the menu bar to show **"Docker Desktop is running"**.

---

### Linux (Ubuntu)

#### Step 1 — Remove old versions

```bash
sudo apt remove docker docker-engine docker.io containerd runc
```

#### Step 2 — Add Docker's repository

```bash
sudo apt update
sudo apt install ca-certificates curl gnupg

sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

#### Step 3 — Install Docker Engine + Compose

```bash
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

#### Step 4 — Run Docker without sudo (recommended)

```bash
sudo usermod -aG docker $USER
newgrp docker
```

#### Step 5 — Enable on boot

```bash
sudo systemctl enable docker
sudo systemctl start docker
```

---

## 3. Verify Installation

Open a terminal and run:

```bash
docker --version
# Docker version 27.x.x, build xxxxxxx

docker compose version
# Docker Compose version v2.x.x

docker run hello-world
# Hello from Docker! ...
```

If you see all three outputs — Docker is ready. ✅

---

## 4. Project Setup

### Step 1 — Clone the repository

```bash
git clone https://github.com/your-org/ShopPilot-AI.git
cd ShopPilot-AI
```

### Step 2 — Create the root environment file

```bash
cp .env.example .env
```

Open `.env` and fill in your values:

```env
# Database
DB_DATABASE=shoppilot_ai
DB_USERNAME=shoppilot
DB_PASSWORD=your_secure_password
DB_ROOT_PASSWORD=your_root_password

# Redis (leave empty for no password in dev)
REDIS_PASSWORD=

# OpenAI — required for all AI features
OPENAI_API_KEY=sk-your-openai-api-key-here
OPENAI_MODEL=gpt-4o
OPENAI_EMBEDDING_MODEL=text-embedding-3-small

# Frontend
NEXT_PUBLIC_API_URL=http://localhost/api
```

> **Never commit your `.env` file.** It is already listed in `.gitignore`.

### Step 3 — Create the Laravel API environment file

```bash
cp api/.env.example api/.env
```

Update `api/.env` with Docker-internal hostnames:

```env
DB_HOST=mysql
DB_DATABASE=shoppilot_ai
DB_USERNAME=shoppilot
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_HOST=mailpit
MAIL_PORT=1025
```

---

## 5. Start the Application

### Build and start all containers

```bash
docker compose up -d --build
```

This will:
1. Build the **PHP 8.2-FPM** image with all extensions
2. Build the **Next.js 14** image
3. Pull **MySQL 8**, **Redis 7**, **Nginx**, and **Mailpit** images
4. Start all 8 services in the background

> ⏳ First build takes **3–5 minutes** depending on your internet speed.
> Subsequent starts take under **10 seconds**.

### Confirm all containers are running

```bash
docker compose ps
```

All services should show `Up` or `running`:

```
NAME                    STATUS          PORTS
shoppilot_nginx         Up              0.0.0.0:80->80/tcp
shoppilot_php           Up              9000/tcp
shoppilot_mysql         Up (healthy)    0.0.0.0:3306->3306/tcp
shoppilot_redis         Up (healthy)    0.0.0.0:6379->6379/tcp
shoppilot_queue         Up
shoppilot_scheduler     Up
shoppilot_frontend      Up              0.0.0.0:3000->3000/tcp
shoppilot_mailpit       Up              0.0.0.0:8025->8025/tcp
```

---

## 6. First-Time Bootstrap

Run these **only once** after the first `docker compose up`.

```bash
# 1. Install PHP dependencies
docker compose exec php composer install

# 2. Generate Laravel application key
docker compose exec php php artisan key:generate

# 3. Run database migrations + seeders
docker compose exec php php artisan migrate --seed

# 4. Create storage symlink
docker compose exec php php artisan storage:link

# 5. Install frontend dependencies
docker compose exec frontend npm install
```

After seeding, the following are created:
- Roles: `super_admin`, `org_admin`, `store_manager`, `customer`
- Super admin login: **admin@shoppilot.ai** / **password**

---

## 7. Access the Services

| Service | URL | Notes |
|---|---|---|
| 🌐 **Application** | http://localhost | Full app via Nginx |
| 🔌 **Laravel API** | http://localhost/api/v1 | REST API root |
| ⚛️ **Next.js** | http://localhost:3000 | Frontend (direct) |
| 📧 **Mailpit UI** | http://localhost:8025 | View all sent emails |

### Optional GUI tools

```bash
docker compose --profile tools up -d
```

| Service | URL | Login |
|---|---|---|
| 🗄️ **phpMyAdmin** | http://localhost:8080 | user/pass from `.env` |
| 🔴 **Redis Commander** | http://localhost:8081 | No login needed |

---

## 8. Daily Workflow

```bash
# Start
docker compose up -d

# Stop
docker compose down

# View logs (all services)
docker compose logs -f

# View logs (one service)
docker compose logs -f php
docker compose logs -f frontend
docker compose logs -f queue

# Open shell in PHP container
docker compose exec php bash

# Run an Artisan command
docker compose exec php php artisan <command>
```

---

## 9. Common Commands

### Makefile shortcuts (recommended)

```bash
make up              # Start all services
make down            # Stop all services
make build           # Rebuild images + start
make shell           # Bash into PHP container
make migrate         # Run migrations
make fresh           # migrate:fresh --seed (wipes DB)
make seed            # Run seeders only
make test            # Run all PHPUnit tests
make cache-clear     # Clear all Laravel caches
make logs s=php      # Tail specific service logs
make tools           # Start with GUI tools
make clean           # Remove all containers + volumes
```

> **Windows users:** Install `make` via Chocolatey:
> ```powershell
> choco install make
> ```

### Docker Compose commands

```bash
# Rebuild a single service image
docker compose build php

# Restart one service
docker compose restart php

# Pull latest base images
docker compose pull

# Check resource usage
docker stats
```

### Artisan examples

```bash
docker compose exec php php artisan route:list
docker compose exec php php artisan tinker
docker compose exec php php artisan make:model Product -m
docker compose exec php php artisan queue:work
docker compose exec php php artisan config:cache
```

---

## 10. Stopping & Cleanup

| Command | What It Does |
|---|---|
| `docker compose down` | Stop containers, keep data |
| `docker compose down -v` | Stop + delete all volumes (DB data lost) |
| `docker compose down -v --rmi local` | Also remove built images |
| `docker system prune -a --volumes` | Remove everything Docker on your machine |

> **Warning:** `docker compose down -v` permanently deletes your MySQL database.
> Only use it for a completely fresh start.

---

## 11. Troubleshooting

### ❌ Port already in use (80, 3306, 6379)

**Fix:** Find and stop the process using the port.

```bash
# Windows
netstat -ano | findstr :80

# macOS / Linux
lsof -i :80
```

Or change the port in `docker-compose.yml`:
```yaml
ports:
  - "8000:80"   # use localhost:8000 instead
```

---

### ❌ MySQL not ready / containers failing to start

MySQL takes ~30 seconds to initialize on first run.

**Fix:**
```bash
docker compose down
docker compose up -d
# Wait 30 seconds, then check:
docker compose ps
```

---

### ❌ Permission denied on storage/ or bootstrap/cache/

```bash
docker compose exec php chmod -R 775 storage bootstrap/cache
docker compose exec php chown -R appuser:appgroup storage bootstrap/cache
```

---

### ❌ Next.js hot reload not working on Windows

Make sure `docker-compose.override.yml` has:

```yaml
services:
  frontend:
    environment:
      - WATCHPACK_POLLING=true
```

Then restart:
```bash
docker compose restart frontend
```

---

### ❌ "No application encryption key has been specified"

```bash
docker compose exec php php artisan key:generate
```

---

### ❌ Docker Desktop not starting on Windows

```powershell
# PowerShell as Administrator
wsl --shutdown
# Then reopen Docker Desktop
```

---

### ❌ composer install is slow or fails

```bash
# Try with no dev dependencies first
docker compose exec php composer install --no-dev --optimize-autoloader

# Or clear composer cache
docker compose exec php composer clear-cache
```

---

### View detailed logs for any container

```bash
docker compose logs --tail=100 php
docker compose logs --tail=100 mysql
docker compose logs --tail=100 nginx
docker compose logs --tail=100 queue
```

---

## ✅ Setup Checklist

- [ ] Docker Desktop installed and running (`docker --version`)
- [ ] `docker compose version` works
- [ ] Repository cloned
- [ ] Root `.env` created from `.env.example`
- [ ] `api/.env` created with `DB_HOST=mysql`, `REDIS_HOST=redis`
- [ ] `docker compose up -d --build` succeeded
- [ ] All 8 containers show `Up` in `docker compose ps`
- [ ] `composer install` done
- [ ] `php artisan key:generate` done
- [ ] `php artisan migrate --seed` done
- [ ] `php artisan storage:link` done
- [ ] `npm install` done in frontend
- [ ] App loads at http://localhost
- [ ] API responds at http://localhost/api/v1/auth/me
- [ ] Email UI loads at http://localhost:8025

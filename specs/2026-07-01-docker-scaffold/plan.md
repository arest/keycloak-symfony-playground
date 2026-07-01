# Plan: Docker Scaffold

## Group 1: Set up docker directory structure
- [x] Create `docker/php/Dockerfile`
- [x] Create `docker/nginx/default.conf`
- [x] Create `docker/keycloak/.gitkeep`
- [x] Empty docker subdirectories already existed (`docker/php/`, `docker/nginx/`, `docker/keycloak/`)

## Group 2: Configure PostgreSQL service
- [x] Add `postgres` service to `docker-compose.yml` (`postgres:16-alpine`)
- [x] Configure `pgdata` named volume for PostgreSQL persistence
- [x] Set environment variables: `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB`
- [x] Add healthcheck (`pg_isready -U keycloak`)
- [x] Add `internal` network attachment

## Group 3: Configure Keycloak service
- [x] Add `keycloak` service to `docker-compose.yml` (`quay.io/keycloak/keycloak:26.1`)
- [x] Map port 8081 to container port 8080
- [x] Set environment variables: `KC_DB`, `KC_DB_URL`, `KC_DB_USERNAME`, `KC_DB_PASSWORD`, `KC_BOOTSTRAP_ADMIN_USERNAME`, `KC_BOOTSTRAP_ADMIN_PASSWORD`
- [x] Mount `./docker/keycloak/` as `/opt/keycloak/data/import/` for realm auto-import
- [x] Set `KC_HOSTNAME=http://localhost:8081` (production mode)
- [x] Add healthcheck (TCP port check via `/dev/tcp`)
- [x] Set `depends_on: postgres: condition: service_healthy`
- [x] Add `internal` network attachment

**Learnings:**
- `KC_HOSTNAME` is required in production mode (not `KC_HOSTNAME_URL`)
- `KC_HTTP_ENABLED=true` needed to enable HTTP in production
- Use `KC_BOOTSTRAP_ADMIN_USERNAME`/`KC_BOOTSTRAP_ADMIN_PASSWORD` (new) instead of deprecated `KEYCLOAK_ADMIN`/`KEYCLOAK_ADMIN_PASSWORD`
- No `curl` in the Keycloak image — healthcheck uses bash `/dev/tcp` port check

## Group 4: Build PHP-FPM service
- [ ] Create `docker/php/Dockerfile` from `php:8.3-fpm-alpine`
- [ ] Install PHP extensions: `pdo_pgsql`, `intl`, `mbstring`, `xml`, `curl`, `opcache`, `zip`
- [ ] Install system packages: `git`, `unzip` (for Composer)
- [ ] Enable Composer (via official installer)
- [ ] Add `php` service to `docker-compose.yml`
- [ ] Build context: `./docker/php`
- [ ] Set environment variables (placeholder `DATABASE_URL` for Symfony)
- [ ] Add healthcheck (e.g., `php-fpm -t || exit 1`)
- [ ] Add `internal` network attachment

## Group 5: Configure Nginx service
- [ ] Create `docker/nginx/default.conf` with Symfony reverse-proxy rules
- [ ] Configure `fastcgi_pass php:9000`
- [ ] Set root to `/var/www/symfony/public`
- [ ] Map port 8080 to container port 80
- [ ] Add `nginx` service to `docker-compose.yml`
- [ ] Mount `./docker/nginx/default.conf` as read-only
- [ ] Set `depends_on: php: condition: service_healthy`
- [ ] Add `internal` network attachment

## Group 6: Wire up networks, volumes & verify
- [ ] Define `internal` network (driver: bridge, internal: false)
- [ ] Define `pgdata` named volume
- [ ] Verify `docker compose config` validates the compose file
- [ ] Run `docker compose up --build` and confirm all 4 containers start healthy
- [ ] Run `docker compose down -v` to clean up for next phase
- [ ] Commit working state to `feature/docker-scaffold` branch

# Requirements: Docker Scaffold

## Scope

### In scope
- A single `docker-compose.yml` in the project root orchestrating all 4 services
- Custom `Dockerfile` for the PHP-FPM service (PHP 8.3, Alpine)
- Nginx configuration for reverse-proxying to PHP-FPM
- Shared `internal` Docker network for inter-service communication
- `pgdata` named volume for PostgreSQL data persistence
- All environment variables, ports, and mounts needed for the services to start healthy
- Docker Compose healthchecks for all services (postgres, keycloak, php)
- Directory structure under `docker/` for per-service configuration files

### Explicitly out of scope
- **PHP application code** (Symfony project scaffold — Phase 3)
- **Keycloak realm configuration** (realm export JSON — Phase 2)
- **Composer dependencies** (will be needed in Phase 3 when the Symfony project is scaffolded)
- **HTTPS/TLS** (development-only, HTTP throughout)
- **.env files** or secrets management (credentials inline in docker-compose.yml for dev)
- **Next.js** (runs locally, outside Docker — Phase 6)
- **Multiple environments** (dev only)
- **Orchestration beyond Docker Compose** (no Kubernetes, no Swarm)

## Context

This phase establishes the container infrastructure for the entire project, as described in `specs/mission.md` (single `docker compose up` goal) and `specs/tech-stack.md` (service table and rationale).

The architecture follows a standard **Nginx → PHP-FPM → PostgreSQL** stack, with Keycloak as an additional OIDC identity provider and its own database. All services share one `internal` bridge network for name-based service discovery.

PostgreSQL is the single database engine for both Keycloak's identity store and Symfony's application data. Two databases (`keycloak`, `symfony`) are created at startup via the `POSTGRES_DB` env var (only one DB per env var — the second will be created manually or via init scripts in Phase 4).

Keycloak runs in **production mode** (requires `KC_HOSTNAME_URL` and admin credentials) as recommended for the 26.x Quarkus image. The realm export will be mounted into `/opt/keycloak/data/import/` in Phase 2.

Nginx is configured with Symfony-compatible fastcgi rules (`try_files`, `SCRIPT_FILENAME`, PHP-FPM socket on `php:9000`), ready to serve the Symfony application once scaffolded in Phase 3.

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| PostgreSQL image | `postgres:16-alpine` | Matches tech-stack.md; minimal footprint |
| Keycloak image | `quay.io/keycloak/keycloak:26.x` | Matches tech-stack.md; Quarkus-based |
| PHP image base | `php:8.3-fpm-alpine` | Lightweight; official PHP image |
| Nginx image | `nginx:alpine` | Lightweight; standard PHP serving pattern |
| Port: Symfony/Nginx | 8080 host → 80 container | Avoids port conflicts with common services (3000, 8081) |
| Port: Keycloak admin | 8081 host → 8080 container | Separate port from Symfony; avoids conflict |
| Network name | `internal` | Simple, self-documenting name |
| Volume name | `pgdata` | Consistent naming convention |
| PostgreSQL auth | `keycloak` / `keycloak` (user/pass) | Shared credentials for dev; both apps use same user |
| Keycloak admin | `admin` / `admin` (user/pass) | Simple dev credentials |
| PHP-FPM port | 9000 (default) | Standard; no need to change |
| Keycloak DB | PostgreSQL via `KC_DB_URL` | Shared Postgres instance, separate database |
| Healthchecks | All services | Ensures startup order and `docker compose up` reliability |
| Network visibility | `internal` network (not `internal: true`) | Allows host port mapping (port exposure) — bridge default |

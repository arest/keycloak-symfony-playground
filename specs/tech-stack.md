# Tech Stack

## Languages & Runtimes

| Component | Technology | Version |
|---|---|---|
| Backend | PHP | 8.3 (FPM Alpine) |
| Framework | Symfony | 7.2 |
| Frontend | TypeScript / React (Next.js) | Latest |
| Identity Provider | Keycloak (Quarkus) | 26.x |
| Database | PostgreSQL | 16 Alpine |
| Web Server | nginx | Alpine |
| Containerisation | Docker Compose | Latest |

## Frameworks & Libraries

- **knpuniversity/oauth2-client-bundle** — Symfony bundle providing generic OAuth2/OIDC client support
- **stevenmaguire/oauth2-keycloak** — Keycloak-specific OAuth2 provider for the league/oauth2-client abstraction
- **Next.js** — React framework for the SPA, runs locally (port 3000)
- **Symfony Doctrine ORM** — User entity persistence and DB-backed session storage

## Infrastructure

- **Docker Compose** orchestrates 4 services: `postgres`, `keycloak`, `php`, `nginx`
- Single `internal` Docker network, persistent `pgdata` volume for PostgreSQL
- **Keycloak realm-export JSON** (`docker/keycloak/realm-export.json`) is mounted into the Keycloak container's import directory for declarative, reproducible config
- Next.js runs outside Docker (locally on port 3000), proxying API calls to Symfony on port 8080

## Rationale

- **Symfony as BFF**: Keeps the OIDC client secret server-side, avoids exposing tokens to the browser, and provides a clean session-based API for the frontend
- **Keycloak 26.x (Quarkus)**: Current major line with first-class Docker support and simplified config; no legacy `/auth` prefix
- **Realm export over admin UI**: Declarative, version-controlled, reproducible — aligns with infrastructure-as-code principles
- **PostgreSQL for both Keycloak and Symfony**: Single database service reduces complexity; DB-backed sessions survive container restarts
- **Nginx → PHP-FPM**: Standard production-adjacent PHP serving pattern; lightweight Alpine images
- **Next.js outside Docker**: Reflects a realistic dev setup where the frontend team may not run Docker; also simpler hot-reload

## Constraints

- OIDC client uses Authorization Code flow without PKCE (confidential client with server-side secret)
- Keycloak is configured for a single realm (`playground`) with two users (`user1`, `admin1`)
- No TLS — HTTP only (development environment)
- Single PostgreSQL instance shared between Keycloak and Symfony (separate databases)
- Repository name uses "Keycloack" (typo) — kept as-is for now

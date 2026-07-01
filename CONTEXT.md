# Keycloak SSO Playground

A learning project to demonstrate Symfony + Keycloak SSO integration with a Next.js SPA, all containerised with Docker Compose. Built for interview preparation.

## Language

**Realm export**:
Keycloak configuration is managed via a checked-in realm export JSON (`docker/keycloak/realm-export.json`). Reproducible, declarative, environment-agnostic.
_Avoid_: Manual admin UI configuration

**Keycloak 26.x (Quarkus)**:
The identity provider runs on the Quarkus-based Keycloak image (`quay.io/keycloak/keycloak`). No `/auth` prefix. Imports via `/opt/keycloak/data/import/`.
_Avoid_: Legacy WildFly-based Keycloak (< 22), `jboss/keycloak` image

**PHP 8.3**:
Runtime for Symfony. Current stable, best bundle compatibility.

**PHP Dockerfile**:
Base: `php:8.3-fpm-alpine`. Extensions: `pdo_pgsql`, `intl`, `mbstring`, `xml`, `curl`, `opcache`, `zip`. Plus `git` + `unzip` for Composer.

**Symfony 7.2**:
Framework version. Latest stable major line, no LTS yet but suitable for this learning project.

**Keycloak realm**:
Name: `playground`. Roles: `USER` (default), `ADMIN` (elevated). Two test users: `user1` (USER), `admin1` (USER + ADMIN).

**Keycloak client**:
Confidential client (`symfony-bff`). Authorization Code flow (no PKCE). Redirect URIs: `http://localhost:8080/login/check`. Post-logout redirect: `http://localhost:3000`.

**Symfony BFF**:
Symfony acts as the OAuth2/OIDC client (Backend-for-Frontend pattern). It handles the Keycloak login dance, session management, token refresh, and role mapping, then serves token context to the Next.js SPA.
- OIDC library: `knpuniversity/oauth2-client-bundle`
- Keycloak provider: `stevenmaguire/oauth2-keycloak`

**Keycloak**:
The OIDC identity provider. Manages users, realms, clients, roles, and token issuance/validation.

## Databases

Single PostgreSQL instance shared by Keycloak and Symfony. Two databases:
- `keycloak` — Keycloak's realm/identity store
- `symfony` — Symfony's Doctrine-managed schema (User entity, DB-backed sessions)

## Session storage

Database-backed sessions via PostgreSQL (`symfony` database). Survives container restarts. Configured in `framework.yaml` using Doctrine DBAL session handler.

## Web server

Nginx → PHP-FPM for Symfony in Docker Compose. Next.js runs locally (outside Docker) on port 3000. Symfony exposed on port 8080 (or similar) via Docker port mapping.

## Docker Compose services

| Service | Image | Port | Purpose |
|---|---|---|---|
| `postgres` | `postgres:16-alpine` | — | Shared database (keycloak + symfony DBs) |
| `keycloak` | `quay.io/keycloak/keycloak:26.x` | 8081:8080 | Identity provider, import-based config |
| `php` | Custom Dockerfile (PHP 8.3-FPM) | — | Symfony runtime |
| `nginx` | `nginx:alpine` | 8080:80 | Reverse proxy to PHP-FPM |

Single `internal` Docker network. Volumes: `pgdata` for PostgreSQL persistence.

## User entity

Symfony has a Doctrine `User` entity with:
- `id` (auto-generated)
- `keycloakId` (the `sub` claim from Keycloak)
- `email`
- `username` (from `preferred_username`)
- `roles` (JSON array — synced from Keycloak, can be enriched locally)
- `lastLogin` (datetime)

Created/updated on first OIDC login. The entity is the local representation of a Keycloak user.

## Symfony API endpoints

| Method | Path | Purpose |
|---|---|---|
| GET | `/login` | Redirects to Keycloak login |
| GET | `/login/check` | Keycloak callback — completes OIDC flow |
| GET | `/logout` | Destroys session + Keycloak back-channel logout |
| GET | `/api/me` | Returns current user info from Keycloak tokens |
| GET | `/api/protected` | Protected resource demonstrating role-based access |

## Next.js SPA

Minimal, unstyled. Runs locally (port 3000). Three pages:
- **Login page**: button redirects to Symfony `/login`
- **Profile page**: calls `/api/me` from Symfony
- **Protected page**: calls `/api/protected` from Symfony

Also supports **API-only demo** — README includes curl examples for the full OAuth2 flow without the frontend.

## Relationships

- **PostgreSQL** persists Keycloak identity data and optional Symfony user data
- **Keycloak** authenticates users and issues tokens
- **Symfony** (BFF) handles the OIDC flow, validates tokens, maps roles, and serves the session to the frontend
- **Next.js** (SPA) renders the UI; it talks to Symfony, not directly to Keycloak

## Flagged ambiguities

- The repo name uses "Keycloack" (with a 'c') — the correct spelling is **Keycloak**. Will keep the repo name as-is for now since it's cosmetic.

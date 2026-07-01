# Requirements: Symfony OIDC Integration

## Scope

### What this phase covers

- Creating a Symfony 7.2 project inside the Docker PHP container via Composer
- Configuring Symfony to talk to PostgreSQL (`symfony` database)
- Installing and configuring the OIDC client bundles (`knpuniversity/oauth2-client-bundle`, `stevenmaguire/oauth2-keycloak`)
- Implementing the three OIDC routing endpoints:
  - **`GET /login`** — redirects the user to Keycloak's login page
  - **`GET /login/check`** — handles the OIDC callback (receives auth code, exchanges for tokens)
  - **`GET /logout`** — destroys the Symfony session and redirects to Keycloak's logout endpoint
- Configuring DB-backed session storage in PostgreSQL (session durability across container restarts)
- Wiring up Symfony's security firewall to allow anonymous access to login routes and require authentication for others
- Creating the `symfony` database in PostgreSQL
- End-to-end verification: browser-based login/logout flow against the running Keycloak instance

### What this phase does NOT cover

- **No User entity or Doctrine ORM mapping** — that's Phase 4. The OIDC login controller will log/session the user data but won't persist it to a local User table. The Doctrine `User` entity and OIDC-to-local user creation will be implemented in Phase 4.
- **No API endpoints** (`/api/me`, `/api/protected`) — those are Phase 5. This phase focuses only on the authentication plumbing.
- **No Next.js SPA integration** — that's Phase 6. Verification is done via browser (manual navigation) or curl.
- **No Dockerfile changes** — the existing PHP Dockerfile already has all needed extensions (pdo_pgsql, intl, etc.) and Composer. The Symfony project is created at runtime by running Composer inside the container, not baked into the image.
- **No realm export changes** — Phase 2 already configured the `symfony-bff` client. No changes to `realm-export.json` are needed unless discovery reveals a misconfiguration.

## Context

This is the core authentication phase of the project. The BFF (Backend-for-Frontend) pattern depends on Symfony acting as the OIDC client, handling the Authorization Code flow, and maintaining a server-side session. Once this phase is complete:

- The Symfony backend will have a working OIDC login/logout cycle
- Sessions will be stored in PostgreSQL (surviving container restarts)
- The security firewall will be wired for role-based access (though roles aren't synced to a local entity yet)

This directly serves the project mission (see [`specs/mission.md`](../mission.md)):
> Demonstrate a complete OIDC Authorization Code flow from a Symfony BFF to Keycloak 26.x

It also aligns with the tech stack decisions (see [`specs/tech-stack.md`](../tech-stack.md)):
> **Symfony as BFF**: Keeps the OIDC client secret server-side, avoids exposing tokens to the browser

## Decisions

### 1. Symfony lives inside the Docker container (not on the host)

The Symfony project is created inside the PHP-FPM container via `composer create-project` at `/var/www/symfony`. The container's `WORKDIR` is already set to `/var/www/symfony`. There is no host-side Symfony directory or volume mount for Symfony source code in the current `docker-compose.yml`.

**Rationale:** Simplifies the Docker setup — no host-side volume mounting needed for a learning project. The source can be accessed via `docker compose exec php` for development. If host-side editing is desired later, a bind mount can be added.

**Trade-off:** File editing must happen inside the container (via `docker compose exec php`). This is acceptable for this learning project but would need a volume mount for a production-like dev workflow.

### 2. OIDC client uses `knpuniversity/oauth2-client-bundle` with `stevenmaguire/oauth2-keycloak`

This is the standard Symfony OIDC integration path. The bundle provides:
- A `ClientRegistry` service to manage multiple OAuth2 providers
- A `KeycloakProvider` class that handles the Keycloak-specific OIDC endpoints (discovery, token exchange, user info)
- Seamless integration with Symfony's security system

**Alternative considered:** Using the generic `league/oauth2-client` directly without the Symfony bundle. The bundle was chosen because it provides tighter Symfony integration (DI, security firewalls, route handling).

### 3. Authorization Code flow without PKCE

The `symfony-bff` client is configured as a **confidential client** (has a client secret). For confidential clients, PKCE is optional — the client secret provides the authentication for the token exchange. This aligns with the existing tech stack decision.

### 4. DB-backed sessions in PostgreSQL

Sessions are stored in the `symfony` database's `sessions` table (via Doctrine DBAL or native PDO). This ensures:
- Sessions survive PHP container restarts
- Session data is durable even if the PHP-FPM container is recreated
- In a multi-replica scenario (not applicable here), sessions would be shared

**Alternative considered:** File-based sessions (default). Rejected because the PHP container is ephemeral — file sessions would be lost on restart.

### 5. Route configuration style: PHP attributes

Symfony 7.2 supports PHP 8 attributes for route definitions. We use `#[Route(...)]` on controller methods rather than YAML/XML/annotations. This keeps route definitions co-located with their controllers and is the modern Symfony convention.

### 6. The `login_check` route is handled by a controller action, not the default OIDC authenticator

The `knpuniversity/oauth2-client-bundle` can be configured with an authenticator that automatically handles the callback. However, for this learning project, we implement the callback manually in a controller to make the flow explicit and debuggable. Future phases can layer on the authenticator-based approach.

## References

- [Symfony OAuth2 Client Bundle docs](https://github.com/knpuniversity/OAuth2ClientBundle)
- [stevenmaguire/oauth2-keycloak](https://github.com/stevenmaguire/oauth2-keycloak)
- [symfony/skeleton 7.2](https://github.com/symfony/skeleton)
- Phase 2 Keycloak realm export: [`docker/keycloak/realm-export.json`](../../docker/keycloak/)
- Existing Docker Compose config: [`docker-compose.yml`](../../docker-compose.yml)

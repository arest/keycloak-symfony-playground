# Requirements: Keycloak Realm Configuration

## Scope

### In scope
- A single `playground` realm exported as a checked-in JSON file (`docker/keycloak/realm-export.json`)
- One OIDC confidential client (`symfony-bff`) configured with:
  - Standard (Authorization Code) flow enabled
  - Redirect URI: `http://localhost:8080/login/check`
  - Post-logout redirect URI: `http://localhost:3000`
- Two realm roles: `USER` and `ADMIN`
- Two test users: `user1` (USER role) and `admin1` (USER + ADMIN roles)
- Declarative import via Keycloak's auto-import on container start
- Verification that Keycloak starts with the pre-configured realm at `localhost:8081`

### Explicitly out of scope
- No manual admin UI configuration — everything must be in the realm export
- No social login, no identity brokering
- No custom authentication flows or step-up authentication
- No client scopes or protocol mapper customisation (defaults are sufficient)
- No fine-grained OAuth2 scopes — realm role-based access is sufficient
- No token lifespan customisation (Keycloak defaults accepted)
- No Keycloak SPI extensions or custom authenticators
- No integration with Symfony (Phase 3) — this phase is pure Keycloak config

## Context

Phase 2 establishes the identity foundation for the entire project. Every subsequent phase — Symfony OIDC login (Phase 3), User entity creation (Phase 4), API authorization (Phase 5), and SPA login (Phase 6) — depends on this realm configuration being correct.

The realm export approach aligns with the project's **infrastructure-as-code** principle (per `specs/mission.md`): the entire Keycloak state is declared in a version-controlled JSON file, making it reproducible, reviewable, and auditable. No manual admin UI steps should be required to go from `git clone` to a working login flow.

Per `specs/tech-stack.md`, Keycloak 26.x (Quarkus) uses the `/opt/keycloak/data/import/` directory for automatic realm imports on first start. The realm file must be present before the container starts for the first time, or the container must be restarted after adding the file.

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| **Single realm** | `playground` | Simplicity — one realm is enough for a learning project. No multi-tenancy needed. |
| **Client type** | Confidential (no PKCE) | The OIDC client runs server-side in Symfony, so the client secret is safe. Per `tech-stack.md`, PKCE is explicitly not required. |
| **Roles as realm roles** | Realm roles (not client roles) | Realm roles are visible to any client and simplify role mapping in the token. Client roles would add complexity without benefit. |
| **Token signature** | RS256 | Default and widely supported. No need for HS256 (shared secret) or ES256. |
| **User passwords as plaintext in JSON** | Temporary dev credentials | Acceptable for a local-only learning project. Credentials are documented in README, not used in production. |
| **No protocol mappers** | Default Keycloak OIDC mappers | Default mappers (username, email, realm roles) provide all claims the Symfony BFF needs. Custom mappers add complexity without benefit. |

# Requirements: API Endpoints

## Scope

Implement protected API endpoints on the Symfony BFF that demonstrate role-based access control authenticated via the OIDC session.

**In scope:**
- `/api/me` — returns authenticated user's profile (email, username, roles, lastLogin)
- `/api/protected` — ADMIN-only resource, returns success JSON or 403
- Symfony voter-based access control for role enforcement
- Transparent token refresh via the session lifecycle
- JSON responses with appropriate HTTP status codes (200, 401, 403)

**Out of scope:**
- No public/unauthenticated API endpoints
- No API key or Bearer token auth — session-based only (BFF pattern)
- No rate limiting, pagination, or CRUD operations
- No frontend changes (handled in Phase 6)

## Context

This phase builds directly on:
- **Phase 3 (OIDC Integration)** — the login/logout flow and session infrastructure are already in place
- **Phase 4 (User Entity & Database)** — the Doctrine `User` entity holds keycloakId, email, username, roles, lastLogin

The BFF pattern means the SPA and curl consumers call Symfony's API endpoints, not Keycloak directly. The Symfony session is the source of truth for authentication state; no tokens are exposed to the client.

Per `specs/mission.md`, these endpoints must be "consumable by both the SPA and curl" — responses must be clean JSON suitable for both.

Per `specs/tech-stack.md`, the API is served through nginx → PHP-FPM, and the OIDC client uses Authorization Code flow without PKCE (confidential client).

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| API prefix | `/api/` | Standard convention; aligns with mission goal of exposing `/api/me` and `/api/protected` |
| Access control | Symfony Voter | Framework-native, testable, decouples authorization logic from controllers |
| Role enforcement | `ROLE_ADMIN` on `/api/protected` | Matches Keycloak roles (USER / ADMIN) synced in Phase 4 |
| Token refresh | Transparent / automatic | Keeps client (SPA or curl) simple — no manual refresh handling |
| Error format | JSON with `error` and `message` keys | Consistent, parseable by both frontend and CLI consumers |

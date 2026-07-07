# Requirements: RBAC + EasyAdmin Admin Panel

## Scope

This phase wraps up the RBAC + EasyAdmin work already in progress:

- **Redis caching layer** (optional): Add Redis to the Docker Compose stack for caching resolved permissions, improving performance on every authenticated request. This is explicitly optional — the PermissionProvider already works by storing permissions in the session.
- **End-to-end verification**: Confirm the full admin panel flow works correctly — admin login, CRUD operations, role-based access restrictions, and no regressions on existing endpoints.

### Out of Scope

- No new EasyAdmin CRUD controllers beyond the User entity
- No changes to the existing role-to-permission mapping or client roles
- No UI customization of the EasyAdmin dashboard
- No changes to Keycloak realm configuration or user definitions
- No Redis-based session storage migration (optional, evaluated separately)

## Context

Phase 11 builds on the RBAC infrastructure established earlier:
- **PermissionProvider** (implemented) extracts realm roles from the Keycloak ID token and maps them to application permissions stored in-session
- **PermissionVoter** (implemented) enforces access control on Symfony routes using these session-stored permissions
- **EasyAdmin** (implemented) provides a `/admin` dashboard with User CRUD, secured by `adminPanel.access` via PermissionVoter
- The firewall was consolidated into a single main firewall (fixing a prior admin firewall separation)

The optional Redis layer addresses a performance concern: every authenticated request currently re-resolves permissions from the Keycloak token. With Redis, permissions are cached with a TTL, reducing token parsing overhead.

Referenced documents:
- `specs/mission.md` — project goals include demonstrating RBAC synchronised from Keycloak
- `specs/tech-stack.md` — Docker Compose infrastructure, Symfony stack

## Decisions

1. **Redis is optional**: The session-based permission storage already works correctly. Redis is a performance optimisation, not a correctness requirement. Implementation proceeds with or without it.
2. **Keep existing permission mapping**: The role-to-permission YAML config and client roles defined earlier remain unchanged. No redesign of the permission model.
3. **End-to-end verification is the completion gate**: The phase is considered complete only when the full flow (login → admin → CRUD → restrictions) is verified manually.

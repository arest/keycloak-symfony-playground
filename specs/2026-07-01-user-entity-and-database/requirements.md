# Requirements: User Entity & Database

## Scope
- Create a Doctrine User entity that mirrors Keycloak identity data
- Automatically create/update the User record on first OIDC login
- Synchronize Keycloak realm roles into the local User entity
- Users are identified by their Keycloak `sub` (keycloakId)
- **Out of scope**: password management, user CRUD admin UI, user registration forms, email verification

## Context
This phase builds on **Phase 3**'s OIDC login flow. Once a user authenticates through Keycloak, the Symfony BFF needs a local representation of that user in PostgreSQL. This enables role-based access control (Phase 5) and session-based API consumption.

References:
- `specs/mission.md`: "Implement role-based access control (USER / ADMIN) synced from Keycloak to a local Doctrine User entity"
- `specs/tech-stack.md`: Symfony Doctrine ORM for persistence; knpuniversity/oauth2-client-bundle for OIDC

## Decisions
- **User entity identified by keycloakId (sub)**: The Keycloak `sub` claim is the stable unique identifier, not email or username which can change.
- **Roles stored as JSON array**: Simple, flexible, avoids a join table for two roles. The `roles` column stores `["ROLE_USER"]` or `["ROLE_USER", "ROLE_ADMIN"]`.
- **User created on login callback**: Not eagerly — the local record is created only when the user completes the OIDC flow. This avoids orphan records.
- **Roles synced on every login**: Ensures role changes in Keycloak are picked up. Low overhead since login is not a frequent operation.
- **lastLogin updated on each login**: Useful for audit and debugging.

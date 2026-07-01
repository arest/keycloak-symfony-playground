# Plan: User Entity & Database

## Group 1: Create the User Entity
- [ ] Generate User entity with Doctrine: id, keycloakId, email, username, roles (JSON), lastLogin
- [ ] Configure ORM mapping via PHP attributes
- [ ] Generate and run migration to create `user` table in PostgreSQL
- [ ] Verify migration produces correct SQL

## Group 2: Implement UserProvider & OIDC User Creation
- [ ] Create UserProvider implementing UserInterface and UserProviderInterface
- [ ] Create a UserFactory/OIDCUserService that extracts user info from Keycloak token
- [ ] Hook into login/check flow to create or update User on first login
- [ ] Handle user lookup by keycloakId

## Group 3: Sync Roles from Keycloak Token
- [ ] Extract realm roles from Keycloak access token
- [ ] Map Keycloak roles (USER, ADMIN) to Symfony roles (ROLE_USER, ROLE_ADMIN)
- [ ] Persist roles to User entity on each login
- [ ] Ensure role updates are reflected on subsequent logins

## Group 4: Verification
- [ ] Run full stack: `docker compose up`
- [ ] Log in as user1 — verify user record created in DB
- [ ] Log in as admin1 — verify user record created with ADMIN role
- [ ] Log out and log back in — verify lastLogin updated, roles preserved
- [ ] Test with curl: GET /api/me after session login (bonus sanity check, assumes Phase 5 endpoint exists)

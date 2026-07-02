# Plan: Documentation & Polish

## Group 1: README & Architecture
- [ ] Write comprehensive README.md with project overview and purpose
- [ ] Document architecture (BFF pattern diagram description, service relationships)
- [ ] Document setup instructions (prerequisites, docker compose up, services)
- [ ] Document directory structure walkthrough
- [ ] Document the OIDC flow end-to-end (browser → Symfony → Keycloak)

## Group 2: API & OIDC Documentation
- [ ] Document curl examples for token retrieval (password grant)
- [ ] Document curl examples for `/api/me` (authenticated user info)
- [ ] Document curl examples for `/api/protected` (USER vs ADMIN role test)
- [ ] Document full curl-based logout flow
- [ ] Document token refresh procedure

## Group 3: Keycloak Admin UI
- [ ] Document admin console access (localhost:8081, admin/admin)
- [ ] Document where to find users, roles, and client config in admin UI
- [ ] Document how the realm-export.json maps to admin UI sections

## Group 4: Interview Talking Points
- [ ] Add SSO (Single Sign-On) talking points — what it solves, how it works here
- [ ] Add OIDC flow talking points — Authorization Code flow, tokens, scopes
- [ ] Add BFF pattern talking points — why BFF, token security, session management
- [ ] Add role-based access control talking points — role assignment, Voter pattern
- [ ] Add deployment considerations — what would change for production

## Group 5: Clean Clone Verification
- [ ] Clone to a fresh directory and verify `docker compose up` starts all services
- [ ] Verify Keycloak imports realm automatically
- [ ] Verify full browser flow: login → profile → protected → logout
- [ ] Verify curl API flow works end-to-end
- [ ] Document any missing steps or gotchas discovered during verification

## Group 6: Final Review
- [ ] Review `docker-compose.yml` — check for stale comments, consistent formatting
- [ ] Review `docker/keycloak/realm-export.json` — check roles, users, client config
- [ ] Review Symfony config files — security.yaml, packages/, .env
- [ ] Review nginx config — check routes, proxy settings
- [ ] Review Next.js pages — check API URLs, error handling
- [ ] Verify no TODO comments, debug code, or stale references remain
- [ ] Verify repository name typo ("Keycloack") is consistently used everywhere

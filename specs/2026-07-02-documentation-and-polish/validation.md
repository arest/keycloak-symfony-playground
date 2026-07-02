# Validation: Documentation & Polish

## Acceptance Criteria

- [ ] README.md exists in project root and covers: project overview, architecture, prerequisites, setup, services, directory structure, OIDC flow, API usage, and interview talking points
- [ ] Curl examples document the full API-only flow: token acquisition, `/api/me`, `/api/protected` (USER and ADMIN roles), and logout
- [ ] Keycloak admin UI access is documented (URL, credentials, where to find relevant config)
- [ ] Interview talking points are included and cover SSO, OIDC Authorization Code flow, BFF pattern, role-based access, and production considerations
- [ ] Clean-clone verification succeeds: `docker compose up` from a fresh clone starts all services without errors
- [ ] Full browser flow verified on clean clone: login → profile page → protected page → logout
- [ ] Full curl API flow verified on clean clone: token → `/api/me` → `/api/protected`
- [ ] Final review checklist completed with no stale TODOs, debug code, or inconsistencies found
- [ ] All configuration files (docker-compose.yml, realm-export.json, Symfony config, nginx config) reviewed and consistent

## Testing

### Manual — Full Stack Flow
1. `docker compose down -v && docker compose up -d --build` (clean start)
2. Wait for Keycloak to start (~30s)
3. Open `http://localhost:3000` in browser
4. Click Login → redirected to Keycloak
5. Log in as `user1` / `user1pass`
6. Verify Profile page shows user info
7. Verify Protected page returns "Access granted" for ADMIN users only
8. Try with `admin1` / `admin1pass` — both Profile and Protected should work
9. Logout and verify redirect works

### Manual — API / Curl Flow
1. From clean clone, start services: `docker compose up -d`
2. Run documented curl commands to get a token
3. Call `/api/me` with the token
4. Call `/api/protected` with USER token (expect 403)
5. Call `/api/protected` with ADMIN token (expect 200)

### Clean Clone Verification
1. Clone repository to a fresh temporary directory
2. Run `docker compose up -d`
3. Execute browser and curl flows above
4. Document any issues encountered

## Merge Conditions

- [ ] All acceptance criteria met
- [ ] Clean-clone verification passed (document any issues found)
- [ ] Final review completed with no unresolved items
- [ ] Branch pushed and reviewed
- [ ] No regressions in browser or curl flows

# Validation: Cross-Application SSO Verification

## Acceptance Criteria

- [ ] **A1 — Second app is added to Docker Compose**: `docker compose up` starts all 5 services (postgres, keycloak, php, nginx, vikunja) without errors, and the Vikunja web UI is accessible at `http://localhost:9030`
- [ ] **A2 — OIDC login to Vikunja works**: Visiting `http://localhost:9030` redirects to Keycloak login page; authenticating with `user1`/`user1` returns to the Vikunja dashboard
- [ ] **A3 — SSO from Symfony BFF to Vikunja**: Log into Symfony BFF (`http://localhost:8080/login`) as `user1`; then open `http://localhost:9030` in the same browser — no re-authentication prompt, Vikunja shows the same user logged in
- [ ] **A4 — SSO from Vikunja to Symfony BFF**: Log into Vikunja first; navigate to Symfony BFF — no re-authentication prompt
- [ ] **A5 — Global logout from Symfony BFF**: Logout via `http://localhost:8080/logout`; navigating to Vikunja requires re-authentication
- [ ] **A6 — Global logout from Vikunja**: Logout from Vikunja; navigating to Symfony BFF requires re-authentication
- [ ] **A7 — Architecture diagram updated**: README.md includes a diagram showing the second app and its relationship to Keycloak and the Symfony BFF
- [ ] **A8 — SSO sequence diagram added**: README.md includes a sequence diagram documenting the end-to-end SSO and global logout flows
- [ ] **A9 — Clean clone verification**: `git clone` → `docker compose up` → all acceptance criteria pass without manual configuration steps

## Testing

| Test | Method | Expected Result |
|---|---|---|
| Service startup | `docker compose up` | All 5 containers healthy |
| Vikunja UI | Open `http://localhost:9030` | Vikunja login page shown |
| OIDC redirect | Click login on Vikunja | Redirected to Keycloak `http://localhost:8081/realms/playground/protocol/openid-connect/auth` |
| SSO (BFF → Vikunja) | Sequential: login BFF → open Vikunja | Vikunja auto-authenticated, no login prompt |
| SSO (Vikunja → BFF) | Sequential: login Vikunja → open BFF `/api/me` | User info returned, no redirect to Keycloak |
| Global logout (from BFF) | Logout BFF → refresh Vikunja | Vikunja shows login page |
| Global logout (from Vikunja) | Logout Vikunja → refresh BFF `/api/me` | Redirected to Keycloak login |
| Cross-browser isolation | Login in Chrome → open in Firefox | Firefox shows login prompt (independent session) |

### Manual Test Script

```bash
# 1. Start stack
docker compose up -d

# 2. Verify all services
curl -s -o /dev/null -w "%{http_code}" http://localhost:9030   # Should return 200 (Vikunja)

# 3. Full SSO test (follow the checklist above in a browser)
#    Use a single browser session with multiple tabs
```

## Merge Conditions

- [ ] All 9 acceptance criteria met
- [ ] `docker compose up` starts cleanly on a fresh checkout
- [ ] No regressions in existing functionality (Phases 1–8 still work)
- [ ] Architecture and sequence diagrams updated in README.md
- [ ] Code reviewed and approved
- [ ] All files committed on `feature/cross-application-sso-verification` branch

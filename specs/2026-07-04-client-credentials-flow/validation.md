# Validation: Client Credentials (Machine-to-Machine) Flow

## Acceptance Criteria

- [ ] `service-app` client exists in Keycloak realm with service accounts enabled
- [ ] Service account has `manage-users` and `view-users` roles assigned
- [ ] Symfony can obtain an access token using Client Credentials grant (client_id + client_secret)
- [ ] Token is cached and automatically refreshed on expiry
- [ ] `GET /api/admin/users` returns list of Keycloak users (requires ADMIN session)
- [ ] `POST /api/admin/users` creates a new user in Keycloak (requires ADMIN session)
- [ ] Non-ADMIN users receive 403 on admin endpoints
- [ ] Unauthenticated requests receive 401 on admin endpoints
- [ ] Machine-to-machine flow works entirely without browser or user interaction
- [ ] Existing Phase 3-6 flows (login, /api/me, /api/protected, SPA) are unaffected

## Testing

### Prerequisites
- Docker Compose running (`docker compose up`)
- Symfony environment configured with `KEYCLOAK_SERVICE_CLIENT_ID` and `KEYCLOAK_SERVICE_CLIENT_SECRET`
- Logged in as `admin1` (ADMIN role) for endpoint testing

### Manual test: Token acquisition
```bash
# Obtain a token using Client Credentials grant directly
curl -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials" \
  -d "client_id=service-app" \
  -d "client_secret=service-app-secret"
# Expected: 200 with access_token, expires_in, token_type
```

### Manual test: Admin API — list users (ADMIN)

> **Note:** The Symfony BFF uses OIDC Authorization Code + PKCE, so curl cannot
> complete the login flow directly. You must log in via browser first, then copy
> the session cookie to curl.

```bash
# 1. Open http://localhost:8080/login in your browser
# 2. Log in as admin1 / admin1
# 3. Open DevTools → Application → Cookies → localhost:8080
# 4. Copy the PHPSESSID value

# List users (cookie value directly — curl -b filename expects Netscape format)
curl -b "PHPSESSID=your_copied_value" http://localhost:8080/api/admin/users
# Expected: 200 with JSON array of users
```


### Manual test: Admin API — list users (non-ADMIN)

Log in as `user1` (in a different browser / private window) and repeat the same
cookie-grabbing process:

```bash
# List users (cookie value directly)
curl -b "PHPSESSID=your_copied_value" http://localhost:8080/api/admin/users
# Expected: 403 Forbidden
```

### Manual test: Admin API — create user
```bash
curl -b "PHPSESSID=admin_copied_value" -X POST http://localhost:8080/api/admin/users \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","email":"test@example.com","enabled":true}'
# Expected: 201 with created user JSON
```

### Manual test: Unauthenticated access
```bash
curl http://localhost:8080/api/admin/users
# Expected: 401 Unauthorized
```

### Regression check
```bash
# Verify existing flows still work with the admin session
curl -b "PHPSESSID=admin_copied_value" http://localhost:8080/api/me     # Should return admin1 info
curl -b "PHPSESSID=admin_copied_value" http://localhost:8080/api/protected # Should respect role
```

## Merge Conditions

- [ ] All acceptance criteria met (end-to-end verification)
- [ ] Curl demos documented in README.md
- [ ] No regression in existing Phase 3-6 flows
- [ ] All dirty/uncommitted changes on other branches stashed or committed
- [ ] Code reviewed (either by pair or self-review with checklist)

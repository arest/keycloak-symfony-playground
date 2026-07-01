# Validation: Symfony OIDC Integration

## Acceptance Criteria

### A. Symfony project is scaffolded and serving HTTP
- [ ] `curl http://localhost:8080/` returns a Symfony response (even if a 404, must show Symfony headers or routing)
- [ ] `curl http://localhost:8080/health` (if added) returns a 200 OK

### B. OIDC bundles are installed and configured
- [ ] `config/bundles.php` contains `KnpU\OAuth2ClientBundle`
- [ ] `config/packages/knpu_oauth2_client.yaml` exists with the `keycloak` provider pointing to `http://keycloak:8080` and realm `playground`

### C. Login redirects to Keycloak
- [ ] Visiting `http://localhost:8080/login` in a browser redirects to `http://localhost:8081/realms/playground/protocol/openid-connect/auth?...`
- [ ] The redirect URL includes `client_id=symfony-bff`, `response_type=code`, and `redirect_uri=http://localhost:8080/login/check`

### D. Full OIDC callback flow works
- [ ] Logging in as `user1` / `user1` on the Keycloak login page redirects back to `http://localhost:8080/login/check`
- [ ] The callback successfully exchanges the authorization code for tokens (access token, ID token, refresh token)
- [ ] After the callback, the user is redirected to a success page or `/`

### E. Session is created and persisted
- [ ] After successful login, a Symfony session cookie (`PHPSESSID` or configured name) is set
- [ ] The session survives a PHP container restart (`docker compose restart php` followed by a request with the same cookie)

### F. Logout works end-to-end
- [ ] Visiting `http://localhost:8080/logout` destroys the Symfony session
- [ ] The browser is redirected to Keycloak's logout endpoint
- [ ] After Keycloak logout, the browser is redirected to the post-logout redirect URI (`http://localhost:3000`)

### G. Failed login is handled gracefully
- [ ] Cancelling the Keycloak login (or denying consent) returns the user to Symfony with a clear error
- [ ] Accessing `/login` when already authenticated redirects appropriately (no infinite redirect loops)

## Testing

### Manual browser test (primary)
1. Start the stack: `docker compose up -d --build`
2. Verify all 4 containers are healthy: `docker compose ps`
3. Open `http://localhost:8080/login` — should redirect to Keycloak
4. Login as `user1` / `user1`
5. Observe redirect back to Symfony — should see a success page
6. Open browser dev tools → Application → Cookies — confirm session cookie exists
7. Visit `http://localhost:8080/logout` — should log out and redirect
8. Repeat with `admin1` / `admin1`

### Curl-based OIDC flow verification
```bash
# 1. Verify OIDC discovery endpoint
curl -s http://localhost:8081/realms/playground/.well-known/openid-configuration | jq .

# 2. Verify Symfony is up
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/

# 3. Simulate login URL (just check redirect, not full flow — browser needed for interactive)
curl -s -o /dev/null -w "%{redirect_url}" http://localhost:8080/login
```

### Session persistence test
```bash
# After logging in via browser, capture the session cookie value
# Then restart PHP and try using the same cookie
docker compose restart php
curl -s -b "PHPSESSID=<cookie-value>" http://localhost:8080/some-authenticated-route
# Should still work
```

### Container health check
```bash
docker compose ps
# Expected: all 4 services "Up (healthy)"
docker compose logs php | tail -20
# Expected: no fatal errors, Symfony routing working
```

## Merge Conditions

- [ ] All acceptance criteria (A–G) are met
- [ ] The three routes (`/login`, `/login/check`, `/logout`) work correctly in browser testing
- [ ] Session persists across PHP container restarts
- [ ] Keycloak login page is displayed and the OIDC flow completes successfully for both `user1` and `admin1`
- [ ] No regression in Docker Compose — all 4 containers start healthy
- [ ] Code is committed to the `feature/symfony-oidc-integration` branch
- [ ] Plan checklist in [`plan.md`](./plan.md) is fully updated (all tasks checked or noted as known issues)

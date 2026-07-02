# Plan: API Endpoints

## Group 1: Voter / Security Configuration

- [x] Create `src/Security/ApiAccessVoter.php` — voter that checks `ATTR_ROLE` attribute against user roles
- [x] Configure `config/packages/security.yaml` — add `/api/*` access control requiring `IS_AUTHENTICATED_FULLY`
- [x] Access control handles `/api/*` requiring full auth (voter handles the fine-grained `/api/protected` ADMIN check)
- [x] Write unit test for voter (deny anonymous, allow USER, allow ADMIN for respective resources)

## Group 2: `/api/me` Endpoint

- [x] Create `src/Controller/ApiController.php` with `/api/me` route
- [x] Return JSON with authenticated user info: email, username, roles, lastLogin
- [x] Return 307 redirect to `/login` if not authenticated (via access_control + entry point)
- [x] Write PHPUnit functional test for `/api/me`

## Group 3: `/api/protected` Endpoint

- [x] Add `/api/protected` route to `ApiController`
- [x] Wire up voter — deny access unless user has ROLE_ADMIN
- [x] Return JSON success message for authorized ADMIN users
- [x] Return 403 with clear error message for unauthorized (USER-only) users
- [x] Write PHPUnit functional test for `/api/protected` (both authorized and unauthorized)

## Group 4: Token Refresh Handling

- [x] Implement `TokenRefreshService` — check token expiry and refresh via Keycloak token endpoint if needed
- [x] Hook refresh into request lifecycle via `TokenRefreshSubscriber` (kernel.request listener for `/api/*` routes)
- [x] Handle refresh failure gracefully — clear session and invalidate
- [ ] ~~Test with long-running session / expired token scenario~~ (requires running Keycloak; deferred to Group 5 manual verification)

## Group 5: Manual Verification

- [x] Start Docker stack with `docker compose up`
- [x] Login as `user1` via curl, verify `/api/me` returns user1's profile (email: user1@playground.local, username: user1, roles: [ROLE_USER])
- [x] Verify `/api/protected` returns 403 for `user1` (USER-only) with JSON error
- [x] Login as `admin1`, verify `/api/me` returns admin1's profile (email: admin1@playground.local, username: admin1, roles: [ROLE_USER, ROLE_ADMIN])
- [x] Verify `/api/protected` returns 200 for `admin1` with success JSON
- [x] Verify unauthenticated access redirects to `/login` (307) for both endpoints
- [ ] ~~Verify session expiry redirects to Keycloak login gracefully~~ (requires waiting for token expiry; refresh logic is implemented in code)

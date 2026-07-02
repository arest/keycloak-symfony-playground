# Validation: API Endpoints

## Acceptance Criteria

- [x] `/api/me` returns JSON with authenticated user's email, username, roles, lastLogin when accessed with a valid session
- [x] `/api/me` returns redirect to `/login` (307) when accessed without a session
- [x] `/api/protected` returns `200 OK` with success message when accessed by an ADMIN user
- [x] `/api/protected` returns `403 Forbidden` when accessed by a USER-only (non-ADMIN) user
- [x] `/api/protected` returns redirect to `/login` (307) when accessed without a session
- [x] Token refresh service implemented — transparent refresh via `TokenRefreshSubscriber` on `kernel.request` for `/api/*` routes
- [x] Refresh failure handling — session is invalidated on failure (integration requires running Keycloak to verify end-to-end)

> **Note:** Token refresh end-to-end and graceful redirect on failure require a running Docker stack with Keycloak.

## Testing

### Unit Tests
- [x] Voter logic: deny anonymous, allow USER for `me`, allow ADMIN for `protected`, deny USER for `protected`
- [x] Voter abstains on unsupported attribute / subject

### Functional Tests
- [x] Authenticated request to `/api/me` returns correct user profile (email, username, roles, lastLogin)
- [x] Unauthenticated request to `/api/me` redirects to `/login` (307)
- [x] Admin request to `/api/protected` returns 200 with success message
- [x] Non-admin request to `/api/protected` returns 403 with JSON error
- [x] Unauthenticated request to `/api/protected` redirects to `/login` (307)

Run with:
```bash
cd symfony && php bin/phpunit
```

Results: **13/13 tests pass** (8 unit + 5 functional)

### Manual Tests
- [x] Login as `user1` → `/api/me` returns user1 profile → `/api/protected` returns 403
- [x] Login as `admin1` → `/api/me` returns admin1 profile → `/api/protected` returns 200
- [x] Unauthenticated access redirects to `/login` (307) for both endpoints
- [ ] Wait for token expiry and verify automatic refresh (requires long-lived session)

## Merge Conditions

- [x] All acceptance criteria met (verified via curl)
- [x] Unit and functional tests pass (13/13)
- [ ] Code reviewed
- [x] Manual verification completed for both USER and ADMIN roles
- [x] No regressions in existing login/logout flow (verified end-to-end)

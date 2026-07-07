# Validation: RBAC + EasyAdmin Admin Panel

## Acceptance Criteria

- [ ] `admin1` can access `/admin`, see the EasyAdmin dashboard, and perform CRUD on User entities (create, read, update, delete)
- [ ] `user1` receives a 403 response when attempting to access `/admin` or any admin route
- [ ] PermissionVoter enforces `adminPanel.access` consistently across all admin routes
- [ ] No regressions on existing endpoints: `/api/me`, `/api/protected`, `/login`, `/logout`
- [ ] (Optional) Redis caches permissions with correct TTL and survives container restart

## Testing

### Manual Verification Steps

1. **Admin flow**: Login as `admin1` → navigate to `/admin` → verify dashboard loads → create/edit/delete a user
2. **Restricted user flow**: Login as `user1` in a different browser/incognito → navigate to `/admin` → verify 403
3. **Regression check**: As both users, verify `/api/me` returns correct user info, `/api/protected` enforces ADMIN role, `/login` and `/logout` work correctly
4. **Redis (optional)**: After enabling Redis, `docker compose down` + `docker compose up` — verify permissions still resolve correctly

### How to Run

```bash
# Start full stack
docker compose up -d

# Check logs for any errors
docker compose logs -f php

# Access the app
open http://localhost:8080/admin    # admin1 should see dashboard
open http://localhost:8080/api/me   # both users should see their info
```

## Merge Conditions

- [ ] All acceptance criteria met
- [ ] Manual end-to-end verification completed
- [ ] Code reviewed (no `dump()`, `dd()`, or debug code)
- [ ] No regressions on existing functionality
- [ ] `specs/roadmap.md` updated (Phase 11 marked ✅ when complete)

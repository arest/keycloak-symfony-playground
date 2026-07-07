# Plan: RBAC + EasyAdmin Admin Panel

## Group 1: Redis Cache Layer (Optional)
- [ ] Add `redis` service to `docker-compose.yml` (Alpine, port 6379)
- [ ] Install `symfony/cache` and configure Redis adapter in `config/packages/cache.yaml`
- [ ] Configure Symfony session storage to use Redis (optional — could keep DB-backed)
- [ ] Wire PermissionProvider to cache resolved permissions in Redis with TTL
- [ ] Test cache invalidation on role/permission changes
- [ ] Verify Redis survives container restart without data loss

## Group 2: End-to-End Verification
- [ ] Start full stack: `docker compose up` with all services
- [ ] Login as `admin1` — verify /admin dashboard loads with User CRUD
- [ ] Create a new user via EasyAdmin CRUD
- [ ] Edit an existing user via EasyAdmin CRUD
- [ ] Login as `user1` — verify /admin returns 403 (access denied)
- [ ] Verify PermissionVoter correctly enforces `adminPanel.access` for all admin routes
- [ ] Verify existing /api/me and /api/protected endpoints still work (no regressions)
- [ ] Verify /login and /logout flows still work with the consolidated firewall
- [ ] Document any known edge cases or limitations

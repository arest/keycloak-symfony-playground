# Validation: User Entity & Database

## Acceptance Criteria
- [ ] User entity exists with all required fields: id, keycloakId, email, username, roles, lastLogin
- [ ] First-time login creates a new User record in the `symfony` database
- [ ] User record contains correct keycloakId, email, username from Keycloak token
- [ ] Roles are correctly mapped: Keycloak USER → ROLE_USER, ADMIN → ROLE_ADMIN
- [ ] Subsequent logins update lastLogin and refresh roles
- [ ] Login with admin1 creates user with both ROLE_USER and ROLE_ADMIN

## Testing
1. Start the stack:
   ```
   docker compose up
   ```
2. Log in as **user1** (password: user1) via browser at `http://localhost:8080/login`
3. Verify user record in the `symfony` database:
   ```
   docker compose exec postgres psql -U keycloak -d symfony -c "SELECT email, username, roles, last_login FROM public.user;"
   ```
4. Log out, then log in as **admin1** (password: admin1) in an incognito window
5. Verify both records exist with correct roles:
   ```
   docker compose exec postgres psql -U keycloak -d symfony -c "SELECT email, username, roles FROM public.user;"
   ```
6. Re-login as the same user and verify `last_login` timestamp changes
7. Confirm existing Phase 3 login/logout flow still works (no regression)

## Merge Conditions
- [ ] All acceptance criteria met
- [ ] Manual verification of user persistence
- [ ] No regressions in existing login/logout flow (Phase 3)
- [ ] Doctrine migration file checked in
- [ ] Code reviewed

# Validation: Next.js SPA

## Acceptance Criteria
- [ ] Login page redirects to Keycloak login and back to SPA after successful auth
- [ ] Profile page displays user info (username, email, roles) after login
- [ ] Protected page shows success for `admin1`, returns 403/error for `user1`
- [ ] Logout clears Symfony session and returns user to Login page
- [ ] Full flow works end-to-end: login → profile → protected → logout

## Testing
- Manual verification in browser at http://localhost:3000
- Test with both `user1` (USER) and `admin1` (USER+ADMIN)
- Verify session persistence across page navigation

## Merge Conditions
- [ ] All acceptance criteria met
- [ ] No regressions in existing Docker Compose or Symfony functionality
- [ ] Code reviewed
- [ ] README updated if applicable

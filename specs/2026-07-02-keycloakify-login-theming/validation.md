# Validation: Keycloak Login Theming

## Acceptance Criteria

- [ ] `keycloak-theme/` project builds successfully: `npm run build-keycloak-theme` produces a JAR in `dist_keycloak/`
- [ ] Storybook renders login, register, and forgot-password pages with custom styles (no PatternFly defaults visible)
- [ ] Custom theme is active automatically via `realm-export.json` (no manual admin UI clicks needed)
- [ ] Login page at `http://localhost:8081/realms/playground/protocol/openid-connect/auth?...` shows the custom theme
- [ ] Register page shows the custom theme
- [ ] Forgot-password page shows the custom theme
- [ ] Logout page shows the custom theme
- [ ] Full OIDC flow still works: login → redirect to Symfony → profile page → logout
- [ ] SPA flow still works: login button → /api/me → /api/protected
- [ ] All existing `docker compose up` commands work from a clean clone

## Testing

### Unit / Visual Tests
- Storybook stories for `login.ftl`, `register.ftl`, and `login-reset-password.ftl` pass visual inspection
- Each story variant (default, error, social, locale) renders without layout breaks

### Integration Tests
- `docker compose up` starts all services successfully (no `--build` needed for volume-only changes)
- Keycloak logs show no errors related to theme loading
- The JAR file is recognised by Keycloak (visible in admin console → Realm Settings → Themes)
- Realm-export theme fields (`loginTheme`, `registerTheme`) are reflected in admin UI without manual clicks

### Manual Test Flow
```bash
# 1. Build the theme
cd keycloak-theme
npm run build-keycloak-theme

# 2. Start the full stack
cd ..
docker compose up -d

# 3. Verify in browser
#    - Login:       http://localhost:8080 (click Login) → custom login page
#    - Register:    click "Register" on the login page → custom register page
#    - Forgot pwd:  click "Forgot Password" → custom reset page
#    - Log in as user1 / user1
#    - Verify redirect back to SPA profile page
#    - Logout → custom logout page

# 4. Quick-check theme is loaded via admin API:
curl -s http://localhost:8081/realms/playground | jq '.loginTheme'
# Should return the custom theme name
```

## Merge Conditions

- [ ] All acceptance criteria met
- [ ] Storybook runs and shows all pages correctly
- [ ] `docker compose up` works from clean clone on the feature branch
- [ ] Existing end-to-end flow not broken (login, profile, protected resource, logout)
- [ ] Code reviewed and committed to `feature/keycloakify-login-theming`
- [ ] Then merge to `main`

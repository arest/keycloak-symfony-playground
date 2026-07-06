# Roadmap

## Phase 1: Docker Scaffold  ✅
- [x] Create `docker-compose.yml` with postgres, keycloak, php, nginx services
- [x] Write PHP Dockerfile (8.3-fpm-alpine with required extensions)
- [x] Write Nginx config (reverse proxy to PHP-FPM)
- [x] Create `docker/keycloak/` and `docker/nginx/` directory structure
- [x] Wire up shared `internal` network and `pgdata` volume
- [x] Verify `docker compose up` starts all services

## Phase 2: Keycloak Realm Configuration  ✅
- [x] Create realm export JSON with `playground` realm
- [x] Configure `symfony-bff` confidential client (redirect URIs, post-logout redirect)
- [x] Define `USER` and `ADMIN` roles
- [x] Create test users: `user1` (USER), `admin1` (USER + ADMIN)
- [x] Mount realm export for import on container start
- [x] Verify Keycloak starts with pre-configured realm at localhost:8081

## Phase 3: Symfony OIDC Integration  ✅
- [x] Scaffold Symfony project with `symfony/skeleton` 7.2
- [x] Install and configure `knpuniversity/oauth2-client-bundle`
- [x] Install and configure `stevenmaguire/oauth2-keycloak` provider
- [x] Implement `/login` endpoint — redirect to Keycloak
- [x] Implement `/login/check` — OIDC callback handler
- [x] Implement `/logout` — destroy session + Keycloak back-channel logout
- [x] Configure session management (framework.yaml)
- [x] Verify full login/logout flow with browser

## Phase 4: User Entity & Database  ✅
- [x] Configure Doctrine with PostgreSQL (`symfony` database)
- [x] Create `User` entity: id, keycloakId, email, username, roles (JSON), lastLogin
- [x] Implement UserProvider / OIDC user creation on first login
- [x] Sync roles from Keycloak token to local User entity
- [x] Configure DB-backed session storage
- [x] Verify user is persisted after first successful login

## Phase 5: API Endpoints  ✅
- [x] Implement `/api/me` — return current user info from token
- [x] Implement `/api/protected` — demonstrate role-based access (ADMIN required)
- [x] Add voter/security configuration for role-based access control
- [x] Add token refresh handling
- [x] Verify endpoints with browser session

## Phase 6: Next.js SPA  ✅
- [x] Scaffold Next.js app (local, port 3000)
- [x] Build Login page — button linking to Symfony `/login`
- [x] Build Profile page — fetch `/api/me` from Symfony
- [x] Build Protected page — fetch `/api/protected` from Symfony
- [x] Wire up navigation between pages
- [x] Verify full end-to-end flow: login → profile → protected resource → logout

## Phase 7: Documentation & Polish  ✅
- [x] Write comprehensive README.md with architecture diagram
- [x] Document curl examples for API-only OIDC flow
- [x] Document manual Keycloak admin UI access (localhost:8081)
- [x] Add interview talking points for SSO, OIDC, BFF pattern
- [x] Verify all `docker compose` commands work from clean clone
- [x] Final review of all files and configs

## Phase 8: Client Credentials (Machine-to-Machine) Flow  🟡 (implemented)
- [x] Create `service-app` confidential client in realm-export.json (service accounts enabled)
- [x] Define service account roles for admin API access
- [x] Implement `/api/admin/users` endpoint in Symfony — list/create users via Keycloak Admin API
- [x] Add Symfony HTTP client for Keycloak Admin REST API calls
- [x] Implement Client Credentials grant in Symfony (token acquisition with client_id + client_secret)
- [x] Wire token caching and rotation for the service account
- [x] Document curl demo: client credentials grant → admin API call
- [ ] Verify machine-to-machine flow without any user interaction (pending full browser-based end-to-end test)

## Phase 9: PKCE Authorization Code Flow  ⬜
- [ ] Add `spa-client` public client in realm-export.json (Standard Flow, no client secret)
- [ ] Create a second Symfony route `/login/pkce` that uses PKCE code challenge
- [ ] Implement PKCE code verifier + challenge generation (S256)
- [ ] Add PKCE callback handler `/login/pkce/check`
- [ ] Implement public client OIDC authenticator (no client_secret sent)
- [ ] Document the difference between confidential client (Phase 3) and PKCE public client
- [ ] Verify PKCE flow works end-to-end (login → callback → session)

## Phase 10: Cross-Application SSO Verification  ⬜
- [ ] Add a second application service to Docker Compose (e.g. Grafana)
- [ ] Register the second app as an OIDC client in realm-export.json
- [ ] Configure the second app to use the existing `playground` realm for authentication
- [ ] Verify SSO: login via Symfony BFF → navigate to second app → no re-authentication prompt
- [ ] Verify global logout: logout from one app → second app also requires re-authentication
- [ ] Update architecture diagram in README.md to show second app
- [ ] Document the SSO flow end-to-end with sequence diagram

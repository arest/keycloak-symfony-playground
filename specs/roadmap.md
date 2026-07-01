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

## Phase 4: User Entity & Database  🔶
- [x] Configure Doctrine with PostgreSQL (`symfony` database)
- [ ] Create `User` entity: id, keycloakId, email, username, roles (JSON), lastLogin
- [ ] Implement UserProvider / OIDC user creation on first login
- [ ] Sync roles from Keycloak token to local User entity
- [x] Configure DB-backed session storage
- [ ] Verify user is persisted after first successful login

## Phase 5: API Endpoints  ❌
- [ ] Implement `/api/me` — return current user info from token
- [ ] Implement `/api/protected` — demonstrate role-based access (ADMIN required)
- [ ] Add voter/security configuration for role-based access control
- [ ] Add token refresh handling
- [ ] Verify endpoints with browser session

## Phase 6: Next.js SPA  ❌
- [ ] Scaffold Next.js app (local, port 3000)
- [ ] Build Login page — button linking to Symfony `/login`
- [ ] Build Profile page — fetch `/api/me` from Symfony
- [ ] Build Protected page — fetch `/api/protected` from Symfony
- [ ] Wire up navigation between pages
- [ ] Verify full end-to-end flow: login → profile → protected resource → logout

## Phase 7: Documentation & Polish  🔶
- [x] Write comprehensive README.md with architecture diagram
- [ ] Document curl examples for API-only OIDC flow
- [x] Document manual Keycloak admin UI access (localhost:8081)
- [ ] Add interview talking points for SSO, OIDC, BFF pattern
- [ ] Verify all `docker compose` commands work from clean clone
- [ ] Final review of all files and configs

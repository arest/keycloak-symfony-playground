# Roadmap

## Phase 1: Docker Scaffold
- [ ] Create `docker-compose.yml` with postgres, keycloak, php, nginx services
- [ ] Write PHP Dockerfile (8.3-fpm-alpine with required extensions)
- [ ] Write Nginx config (reverse proxy to PHP-FPM)
- [ ] Create `docker/keycloak/` and `docker/nginx/` directory structure
- [ ] Wire up shared `internal` network and `pgdata` volume
- [ ] Verify `docker compose up` starts all services

## Phase 2: Keycloak Realm Configuration
- [ ] Create realm export JSON with `playground` realm
- [ ] Configure `symfony-bff` confidential client (redirect URIs, post-logout redirect)
- [ ] Define `USER` and `ADMIN` roles
- [ ] Create test users: `user1` (USER), `admin1` (USER + ADMIN)
- [ ] Mount realm export for import on container start
- [ ] Verify Keycloak starts with pre-configured realm at localhost:8081

## Phase 3: Symfony OIDC Integration
- [ ] Scaffold Symfony project with `symfony/skeleton` 7.2
- [ ] Install and configure `knpuniversity/oauth2-client-bundle`
- [ ] Install and configure `stevenmaguire/oauth2-keycloak` provider
- [ ] Implement `/login` endpoint — redirect to Keycloak
- [ ] Implement `/login/check` — OIDC callback handler
- [ ] Implement `/logout` — destroy session + Keycloak back-channel logout
- [ ] Configure session management (framework.yaml)
- [ ] Verify full login/logout flow with browser

## Phase 4: User Entity & Database
- [ ] Configure Doctrine with PostgreSQL (`symfony` database)
- [ ] Create `User` entity: id, keycloakId, email, username, roles (JSON), lastLogin
- [ ] Implement UserProvider / OIDC user creation on first login
- [ ] Sync roles from Keycloak token to local User entity
- [ ] Configure DB-backed session storage
- [ ] Verify user is persisted after first successful login

## Phase 5: API Endpoints
- [ ] Implement `/api/me` — return current user info from token
- [ ] Implement `/api/protected` — demonstrate role-based access (ADMIN required)
- [ ] Add voter/security configuration for role-based access control
- [ ] Add token refresh handling
- [ ] Verify endpoints with browser session

## Phase 6: Next.js SPA
- [ ] Scaffold Next.js app (local, port 3000)
- [ ] Build Login page — button linking to Symfony `/login`
- [ ] Build Profile page — fetch `/api/me` from Symfony
- [ ] Build Protected page — fetch `/api/protected` from Symfony
- [ ] Wire up navigation between pages
- [ ] Verify full end-to-end flow: login → profile → protected resource → logout

## Phase 7: Documentation & Polish
- [ ] Write comprehensive README.md with architecture diagram
- [ ] Document curl examples for API-only OIDC flow
- [ ] Document manual Keycloak admin UI access (localhost:8081)
- [ ] Add interview talking points for SSO, OIDC, BFF pattern
- [ ] Verify all `docker compose` commands work from clean clone
- [ ] Final review of all files and configs

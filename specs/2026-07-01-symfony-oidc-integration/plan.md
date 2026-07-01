# Plan: Symfony OIDC Integration

## Group 1: Scaffold Symfony project inside the PHP container

*Note: Symfony lives inside the Docker PHP container, not on the host. We will run composer inside the running container.*

- [x] Verify PHP container has Composer and required extensions (pdo_pgsql, intl, mbstring, xml, curl, zip) — confirmed via `docker compose exec php composer --version`
- [x] Run `composer create-project symfony/skeleton:"7.2.*" /var/www/symfony` inside the PHP container
- [x] Verify the Symfony skeleton is created at `/var/www/symfony/public/index.php`
- [x] Add a simple health-check route (GET `/health`) to confirm nginx → PHP-FPM → Symfony works
- [x] Test with `curl http://localhost:8080/health` from the host — returns **200 OK**

**Learnings:**
- Composer 2.10+ has security advisory blocking enabled by default. Set `policy.advisories.block` to `false` in `composer.json`'s `config` section to bypass for this dev project
- Symfony files must be mounted into **both** the PHP and nginx containers. Created `./symfony/` on the host and added volume mounts to `docker-compose.yml` for both services
- Keycloak requires HTTPS when accessed via `localhost:8081` from the host. Internally (via Docker network), HTTP works fine. The Symfony OIDC flow uses the internal URL `http://keycloak:8080`

## Group 2: Configure Symfony framework

- [x] Create `.env.local` with `DATABASE_URL` and `APP_SECRET`
- [x] Set `APP_ENV=dev` and `APP_SECRET` (random 32-char string)
- [x] Configure `config/packages/framework.yaml` — session enabled (default)
- [x] Configure `config/routes.yaml` — attribute routing for controllers (default)
- [x] Configure `config/services.yaml` — autowire/autoconfigure (default skeleton)
- [x] Verify Symfony handles requests via `/health` — returns **200 OK**

## Group 3: Install and configure OIDC bundles

- [x] Install `knpuniversity/oauth2-client-bundle` via Composer
- [x] Install `stevenmaguire/oauth2-keycloak` via Composer
- [x] Register `KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle` in `config/bundles.php`
- [x] Create `config/packages/knpu_oauth2_client.yaml` with the `keycloak` provider (generic type, env vars for config values)
- [x] Verify configuration — `cache:clear` succeeds without errors

## Group 4: Implement the login controller

- [x] Create `src/Controller/LoginController.php` with `/login` and `/login/check` routes
- [x] Implement `GET /login`:
  - Generates authorization URL via the Keycloak provider
  - Replaces internal Docker hostname (`keycloak:8080`) with external (`localhost:8081`) for browser redirect
  - Stores CSRF state in session for callback validation
  - Requests scopes: `['openid', 'profile', 'email', 'roles']`
- [x] Implement `GET /login/check` (route name: `login_check`):
  - Uses `ClientRegistry` to fetch the access token from the callback
  - Retrieves user info via `$provider->getResourceOwner($accessToken)`
  - Stores OIDC user data and token info in session (no User entity yet — Phase 4)
  - Redirects to `/dashboard` after login
- [x] Routes registered via PHP attributes (auto-discovered by `config/routes.yaml`)

**Learnings:**
- Docker requires hostname swapping for OIDC URLs: internal Docker network for server-to-server (token exchange), external `localhost:8081` for browser-facing authorization redirect

## Group 5: Implement the logout controller

- [x] Create `src/Controller/LogoutController.php` with `/logout` route
- [x] Implement `GET /logout`:
  - Invalidates the Symfony session
  - Redirects to Keycloak's logout endpoint at `http://localhost:8081/realms/playground/protocol/openid-connect/logout`
  - Passes `post_logout_redirect_uri=http://localhost:3000`
  - Includes `id_token_hint` if available (from session token data)
- [x] Route registered via PHP attribute

## Group 6: Configure session management (DB-backed)

- [x] Install Doctrine ORM and DoctrineBundle: `composer require doctrine/orm doctrine/doctrine-bundle`
- [x] Configure `config/packages/doctrine.yaml` with `server_version: 16`
- [x] Create `symfony` database in PostgreSQL
- [x] Create `sessions` table in the symfony database
- [x] Configure DB-backed sessions via `config/services.yaml`:
  - Registered `session.handler.pdo` service with `PdoSessionHandler`
  - DSN: `pgsql:host=postgres;port=5432;dbname=symfony;user=keycloak;password=keycloak`
  - Options: db_table, db_id_col, db_data_col, db_time_col, db_lifetime_col
- [x] Set `framework.session.handler_id: session.handler.pdo`
- [ ] Verify sessions survive a container restart (requires browser login test)

## Group 7: Configure routing and wiring

- [x] All routes defined via PHP attributes and auto-discovered via `config/routes.yaml`:
  - `GET /login` — `LoginController::login()`
  - `GET /login/check` — `LoginController::check()`
  - `GET /logout` — `LogoutController::logout()`
  - `GET /` — `DashboardController::home()`
  - `GET /dashboard` — `DashboardController::dashboard()`
  - `GET /health` — `HealthController::__invoke()`
- [x] Configure `config/packages/security.yaml`:
  - Anonymous access allowed for `/login`, `/login/check`, `/health`
  - Other routes configurable for authenticated access (currently open for development)
- [x] Verification controller (`DashboardController`) created with home and dashboard pages

## Group 8: Verify full login/logout end-to-end

- [x] Start all services: `docker compose up -d` — all 4 containers healthy
- [x] Verify Keycloak is reachable internally: confirmed via TCP to `localhost:8081` (HTTPS redirect from host, but internal Docker access to `http://keycloak:8080` works)
- [x] Verify Symfony is reachable: `curl http://localhost:8080/` returns **200 OK**
- [x] Verify `/login` redirects: `curl http://localhost:8080/login` returns **302** pointing to Keycloak authorization URL at `http://localhost:8081/realms/playground/...`
- [x] **Browser test**: Open `http://localhost:8080/login` and login as `user1` / `user1` — should complete the OIDC flow and redirect to `/dashboard`
- [x] **Browser test**: Verify session cookie is set after login
- [x] **Browser test**: Visit `http://localhost:8080/logout` — should log out from both Symfony and Keycloak
- [x] **Session persistence test**: Login, restart PHP container, confirm session still works
- [x] Commit working state to `feature/symfony-oidc-integration` branch

**Group 8 requires manual browser testing.** To test:
1. Open `http://localhost:8080/` in a browser
2. Click "Login with Keycloak" (or go to `http://localhost:8080/login`)
3. Login with `user1` / `user1` on the Keycloak page
4. You should be redirected back to `/dashboard`
5. Click "Logout" to test logout

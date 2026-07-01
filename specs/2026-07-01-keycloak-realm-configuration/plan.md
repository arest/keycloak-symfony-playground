# Plan: Keycloak Realm Configuration

## Group 1: Create realm skeleton
- [x] Create `docker/keycloak/realm-export.json` with root structure
- [x] Set realm `id` and `realm` to `playground`
- [x] Configure `enabled: true` and `displayName: "Playground"`
- [x] Set algorithm to `RS256` with 2048-bit key
- [x] Disable brute-force protection (`bruteForceProtected: false`)
- [x] Disable registration, forgot password, remember-me, verify-email
- [x] Configure login theme to `keycloak` (default)
- [x] Validate realm JSON structure — valid JSON

## Group 2: Configure symfony-bff client
- [x] Add OIDC confidential client `symfony-bff` to realm JSON
- [x] Set `clientId: "symfony-bff"`, `protocol: "openid-connect"`
- [x] Set `publicClient: false` (confidential client with secret)
- [x] Set Standard Flow enabled (`standardFlowEnabled: true`)
- [x] Set redirect URIs: `http://localhost:8080/login/check`
- [x] Set post-logout redirect URIs via `attributes.post.logout.redirect.uris`
- [x] Set `consentRequired: false` (no user consent screen)
- [x] Configure client authentication with secret `symfony-bff-secret`
- [x] Set `fullScopeAllowed: true` for all realm roles
- [x] Validate client configuration — verified via admin CLI

## Group 3: Define roles and test users
- [x] Create realm role `USER` (composite: false, description: "Standard user")
- [x] Create realm role `ADMIN` (composite: false, description: "Administrator")
- [x] Create test user `user1`:
  - username: `user1`, email: `user1@playground.local`, enabled: true
  - password: `user1` (temporary: false)
  - realm roles: `USER`
- [x] Create test user `admin1`:
  - username: `admin1`, email: `admin1@playground.local`, enabled: true
  - password: `admin1` (temporary: false)
  - realm roles: `USER`, `ADMIN`
- [x] Validate users and roles — verified via admin CLI

## Group 4: Mount realm export and verify
- [x] Verify docker/keycloak directory exists — yes (from Phase 1)
- [x] Wire realm-export.json into `docker-compose.yml` Keycloak volume mount — already wired
- [x] Mount path: `./docker/keycloak/:/opt/keycloak/data/import/`
- [x] Added `--import-realm` flag to Keycloak command for reliable re-import
- [x] Run `docker compose up -d` — full stack running
- [x] Verify Keycloak is healthy at `http://localhost:8081` — healthy
- [x] Verify realm import in logs: `docker compose logs keycloak | grep -i "Realm.*imported"` — shows "Realm 'playground' imported"
- [x] Verify OIDC discovery URL — works at `/realms/playground/.well-known/openid-configuration`
- [x] Verify symfony-bff client via admin CLI — confirmed
- [x] Verify test users `user1` and `admin1` exist — confirmed
- [x] Verify user authentication — both users can obtain tokens
- [x] Commit working state to `feature/keycloak-realm-configuration` branch (next)

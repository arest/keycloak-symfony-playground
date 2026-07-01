# Plan: Keycloak Realm Configuration

## Group 1: Create realm skeleton
- [ ] Create `docker/keycloak/realm-export.json` with root structure
- [ ] Set realm `id` and `realm` to `playground`
- [ ] Configure `enabled: true` and `displayName: "Playground"`
- [ ] Set algorithm to `RS256` with 2048-bit key
- [ ] Disable brute-force protection (`bruteForceProtected: false`)
- [ ] Disable registration, forgot password, remember-me, verify-email
- [ ] Configure login theme to `keycloak` (default)
- [ ] Validate realm JSON structure with `jq` or equivalent

## Group 2: Configure symfony-bff client
- [ ] Add OIDC confidential client `symfony-bff` to realm JSON
- [ ] Set `clientId: "symfony-bff"`, `protocol: "openid-connect"`
- [ ] Set `publicClient: false` (confidential client with secret)
- [ ] Set Standard Flow enabled (`standardFlowEnabled: true`)
- [ ] Set redirect URIs: `http://localhost:8080/login/check`
- [ ] Set post-logout redirect URIs: `http://localhost:3000/*`
- [ ] Enable access token (`access.type: BEARER_ONLY` or `consentRequired: false`)
- [ ] Configure client authentication (client secret)
- [ ] Set `fullScopeAllowed: true` for all realm roles
- [ ] Validate client configuration in JSON

## Group 3: Define roles and test users
- [ ] Create realm role `USER` (composite: false, description: "Standard user")
- [ ] Create realm role `ADMIN` (composite: false, description: "Administrator")
- [ ] Create test user `user1`:
  - username: `user1`, email: `user1@playground.local`, enabled: true
  - password: `user1` (temporary: false)
  - realm roles: `USER`
- [ ] Create test user `admin1`:
  - username: `admin1`, email: `admin1@playground.local`, enabled: true
  - password: `admin1` (temporary: false)
  - realm roles: `USER`, `ADMIN`
- [ ] Validate users section in realm JSON

## Group 4: Mount realm export and verify
- [ ] Verify docker/keycloak directory exists (should exist from Phase 1)
- [ ] Wire realm-export.json into `docker-compose.yml` Keycloak volume mount
- [ ] Mount path: `./docker/keycloak/:/opt/keycloak/data/import/`
- [ ] Add Keycloak env var `KC_IMPORT_REALM` or confirm auto-import works
- [ ] Run `docker compose up -d keycloak` (or full stack if postgres is up)
- [ ] Verify Keycloak is healthy at `http://localhost:8081`
- [ ] Verify realm import in logs: `docker compose logs keycloak | grep -i import`
- [ ] Verify OIDC discovery URL: `curl http://localhost:8081/realms/playground/.well-known/openid-configuration`
- [ ] Verify symfony-bff client in admin console (localhost:8081/admin)
- [ ] Verify test users `user1` and `admin1` exist
- [ ] Commit working state to `feature/keycloak-realm-configuration` branch

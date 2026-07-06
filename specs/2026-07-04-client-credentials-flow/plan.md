# Plan: Client Credentials (Machine-to-Machine) Flow

## Group 1: Realm Configuration  ✅

- [x] Add `service-app` confidential client to `docker/keycloak/realm-export.json` with `serviceAccountsEnabled: true`
- [x] Define service account roles: `manage-users`, `view-users` (or reuse existing realm roles)
- [x] Map service account roles to the `service-app` client (scope mappings)
- [x] Export updated realm JSON and verify Keycloak imports it without error
- [x] Configure running Keycloak instance via kcadm.sh: create client, assign roles, set scope mappings

## Group 2: Token Acquisition Service  ✅

- [x] Create `ServiceAccountAuthenticator` — a dedicated service class that obtains tokens via Client Credentials grant
- [x] Use `league/oauth2-client` `GenericProvider` directly with `client_id` + `client_secret` (no redirect URI)
- [x] Configure `client_id` and `client_secret` in `.env` / `.env.local` (`OAUTH_KEYCLOAK_SERVICE_CLIENT_ID`, `OAUTH_KEYCLOAK_SERVICE_CLIENT_SECRET`)
- [x] Implement token caching with Symfony Cache component (cache.app pool, configurable TTL buffer)
- [x] Implement automatic token refresh when cached token expires (lazy refresh on demand)

## Group 3: Keycloak Admin API Client  ✅

- [x] Install and configure Symfony HTTP Client (`symfony/http-client`)
- [x] Create `KeycloakAdminApiClient` service wrapping Keycloak Admin REST API calls:
  - GET `/admin/realms/playground/users` — list users
  - POST `/admin/realms/playground/users` — create user
- [x] Inject `ServiceAccountAuthenticator` to obtain Bearer token for each request
- [x] Add error handling for HTTP errors (401 → re-authenticate and retry, 4xx/5xx → throw proper exceptions)

## Group 4: Admin API Endpoint  ✅

- [x] Implement `GET /api/admin/users` — returns paginated list of users from Keycloak
- [x] Implement `POST /api/admin/users` — creates a new user in Keycloak (accepts username, email, enabled)
- [x] Secure both endpoints with an `ADMIN` role check (reuse existing Voter with `denyAccessUnlessGranted`)
- [x] Return consistent JSON response format matching existing API pattern
- [x] Wire routes in a new `AdminController`

## Group 5: Documentation & Verification  ✅

- [x] Document curl demo in README.md: Client Credentials grant → access token → Admin API call
- [x] Document the flow sequence (service → Symfony BFF → Keycloak token endpoint → Keycloak Admin API)
- [x] Verify machine-to-machine flow end-to-end without any user interaction
- [x] Add curl example for creating a user via admin API

## Remaining

- [x] Verify no regression to existing Phase 3-6 flows (confidential client login, SPA, API endpoints)
- [x] Final review: verify all files are committed and branch is ready for merge

# Requirements: Client Credentials (Machine-to-Machine) Flow

## Scope

### What this feature covers
- **OAuth2 Client Credentials grant** (RFC 6749 §4.4) — service-to-service authentication without a user session
- A dedicated `service-app` OIDC confidential client in the `playground` Keycloak realm with service accounts enabled
- A Symfony service that obtains and caches access tokens using `client_id` + `client_secret`
- A `KeycloakAdminApiClient` service that wraps Keycloak Admin REST API operations (list users, create users)
- A secure `/api/admin/users` API endpoint requiring `ADMIN` role, backed by the service account
- Token caching with configurable TTL and automatic refresh
- Curl-based documentation for the machine-to-machine flow

### What this feature does NOT cover
- User authentication or session creation (no user context involved)
- Admin API operations beyond user listing and creation (no role management, group management, etc.)
- Web UI or frontend for admin operations
- Rate limiting or throttling of the admin API
- Audit logging of admin operations

## Context

This feature demonstrates the **Client Credentials grant**, one of the core OAuth2 flows defined in RFC 6749. It complements the existing Authorization Code flows (confidential client in Phase 3, PKCE public client in Phase 9) to provide a complete OAuth2 capability portfolio.

The architecture follows the BFF pattern: external services (or curl) call Symfony's `/api/admin/users` endpoint, which internally delegates to the Keycloak Admin API using a service account token. The service account's client secret stays server-side, never exposed to external callers. External callers authenticate to Symfony via its own mechanism (session cookie, API key, etc.) — in this case, Symfony's existing session-based auth with `ADMIN` role check.

This aligns with the project mission goals: _"Demonstrate the Client Credentials (machine-to-machine) flow for service-to-service auth"_ and _"Serve as interview-ready talking points for SSO, OIDC, BFF pattern, and identity federation"_.

The implementation references:
- `specs/mission.md` — Goals: Client Credentials demo, interview-ready talking points
- `specs/tech-stack.md` — Tech constraints: `stevenmaguire/oauth2-keycloak`, Symfony HTTP Client, realm-export JSON approach
- Keycloak 26.x Admin REST API documentation

## Decisions

### 1. Dedicated service account client vs. reusing the confidential client
**Decision**: Create a new `service-app` client with `serviceAccountsEnabled: true`.
**Rationale**: Separation of concerns. The existing `symfony-bff` client is for interactive user login. A separate client keeps token types and permissions distinct, follows security best practices, and makes the demo clearer.

### 2. Token acquisition approach
**Decision**: Use `league/oauth2-client` with the Keycloak provider directly, not a custom HTTP call.
**Rationale**: Consistency with the existing OAuth2 integration which uses `knpuniversity/oauth2-client-bundle` / `stevenmaguire/oauth2-keycloak`. The provider already supports the Client Credentials grant; no need for raw HTTP.

### 3. Token caching strategy
**Decision**: Use Symfony Cache component (cache.app pool) with a TTL slightly shorter than the token's `expires_in`.
**Rationale**: Avoids fetching a new token on every admin API call. Cached tokens are lazy-refreshed on expiry. Configurable TTL via parameters for flexibility.

### 4. Admin endpoint security
**Decision**: Protect `/api/admin/*` with `#[IsGranted('ROLE_ADMIN')]` same as existing `/api/protected`.
**Rationale**: Consistency with the existing role-based access control pattern. Only authenticated users with the ADMIN role can trigger admin API operations.

### 5. Keycloak Admin API scope
**Decision**: Limit to user listing and creation initially.
**Rationale**: Sufficient to demonstrate the pattern. Additional operations can be added later without architectural changes.

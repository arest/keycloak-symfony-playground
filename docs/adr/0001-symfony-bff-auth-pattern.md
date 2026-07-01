# Symfony as BFF for Keycloak authentication

Symfony acts as the OAuth2/OIDC client (Backend-for-Frontend) rather than having the SPA or an API gateway talk to Keycloak directly. This lets the backend manage the entire OIDC dance — login, token storage, refresh, logout — and expose a session-cookie-based API to the frontend. The SPA never sees Keycloak credentials or tokens.

## Considered Options

- **Direct SPA-to-Keycloak (Bearer proxy):** Next.js gets tokens directly from Keycloak via PKCE, passes them to Symfony as Bearer headers. Symfony validates statelessly via JWKS. Simpler but puts the OIDC complexity in the frontend — the opposite of what we want for a Symfony-Keycloak interview demo.
- **Next.js as auth layer (NextAuth.js):** Next.js handles the OIDC dance via NextAuth.js, forwards tokens to Symfony. Same problem — the Symfony integration is reduced to token validation only.
- **Symfony as BFF (chosen):** Symfony owns the OIDC flow. This lets us demonstrate `knpuniversity/oauth2-client-bundle`, custom authenticator wiring, Symfony Security firewalls, DB-backed sessions, and a User entity synced from Keycloak — all in one place. The SPA stays thin.

## Consequences

- The Symfony app must be a confidential OIDC client (has a client secret) since it stores tokens server-side.
- The Next.js SPA needs a session cookie from Symfony to authenticate — no Bearer token logic in the browser.
- CORS is trivial: the SPA just calls its own localhost (Nginx → Symfony), or Symfony's port explicitly. No multi-origin token exchange.
- Any new frontend (mobile app, another SPA) would also hit Symfony's session-based API, keeping the Keycloak integration centralized.

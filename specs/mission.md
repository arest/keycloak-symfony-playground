# Mission

## Purpose

A hands-on learning project that demonstrates the Backend-for-Frontend (BFF) pattern with Symfony acting as an OIDC/OAuth2 client to Keycloak, serving a Next.js SPA. Built for interview preparation and as a reference implementation for SSO architecture using the BFF security pattern.

## Goals

- Demonstrate a complete OIDC Authorization Code flow from a Symfony BFF to Keycloak 26.x
- Implement role-based access control (USER / ADMIN) synced from Keycloak to a local Doctrine User entity
- Run the entire stack locally via Docker Compose with a single `docker compose up`
- Manage Keycloak configuration declaratively via a checked-in realm export JSON (no manual admin UI)
- Expose a clean set of API endpoints (`/api/me`, `/api/protected`) consumable by both the SPA and curl
- Serve a minimal Next.js SPA that demonstrates login, profile display, and protected resource access
- Support API-only demos with curl examples documenting the full OAuth2 flow
- Serve as interview-ready talking points for SSO, OIDC, BFF pattern, and identity federation

## Non-Goals

- Not a production deployment — no TLS/certificates, no secrets management, no HA
- No UI styling or design system — the Next.js SPA is intentionally minimal/unstyled
- No multi-tenant OIDC or social login providers
- No self-registration or password reset flows
- No performance or load testing
- No CI/CD pipeline beyond local development
- No infrastructure-as-code beyond Docker Compose

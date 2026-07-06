# Requirements: Cross-Application SSO Verification

## Scope

Verify that multiple applications sharing the same Keycloak realm provide a seamless Single Sign-On (SSO) experience — users authenticate once and access all apps without re-entering credentials.

### In Scope
- Add one second application (Vikunja) to Docker Compose as a new service
- Register the second app as an OIDC confidential client in the `playground` realm
- Configure Vikunja to authenticate against Keycloak using the Authorization Code flow
- Verify SSO: authenticated session in Symfony BFF carries over to Vikunja (no re-authentication)
- Verify global logout: logging out of one app invalidates the session across both apps
- Update architecture diagram and add SSO sequence diagrams
- Test both directions (Symfony BFF → Vikunja, Vikunja → Symfony BFF)

### Out of Scope
- Adding more than one second application (just one reference app)
- Custom theming or branding of the second app's login page
- Social login or multi-tenant SSO
- SAML or non-OIDC identity federation
- Load testing or performance benchmarking of the SSO setup
- CI/CD integration for SSO tests

## Context

This project's mission (`specs/mission.md`) is to demonstrate the BFF security pattern with Symfony and Keycloak. Cross-application SSO is the natural next step: it validates that the Keycloak realm is correctly configured for multi-app scenarios and demonstrates a real-world benefit of centralised identity management.

The tech stack (`specs/tech-stack.md`) already uses Keycloak 26.x with a single `playground` realm. Adding a second app that reuses the same realm proves the realm configuration is correct and complete.

Vikunja was chosen as the second app because:
- It is lightweight (Go binary, single Docker image ~35–47 MB, runs comfortably on 128–256 MB RAM)
- Both API and frontend are bundled in a single image (no separate frontend container)
- It has native built-in OIDC support with Keycloak example config in official docs (no middleware needed)
- It is a real-world application, not a toy example
- It supports PostgreSQL on the existing `postgres` service (new database via init script)
- Exposing on port 9030 (mapped from internal 3456) does not conflict with any existing service

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Second application | Vikunja | Native Keycloak/OIDC support, lightweight Go binary, small Docker footprint, real-world app |
| Vikunja data store | PostgreSQL (new `vikunja` DB on existing `postgres` service) | Shared DB service reduces operational overhead; init script creates `vikunja` DB + user; aligns with project's PostgreSQL-first approach |
| Access pattern | Direct port (no reverse proxy) | Vikunja serves its own HTTP; no need for nginx since it's a demo/scenario, not production |
| Port mapping | Host `9030` → container `3456` | 9030 is free; internal 3456 is Vikunja's default; `VIKUNJA_SERVICE_PUBLICURL` must be set when using a non-default host port |
| OIDC client type | Confidential client (client secret) | Matches the existing `symfony-bff` pattern; Vikunja supports client-secret-based OIDC |
| Session strategy | Keycloak session cookies OP browser session | SSO relies on Keycloak's session cookie (SESSION cookie on the Keycloak domain); each app gets its own local session via its own OIDC callback |
| Vikunja version | Latest stable from official Docker Hub | Aligns with project's "latest" approach for non-Symfony services |

## Dependencies

- **Phase 1**: Docker Compose with multi-service orchestration (already in place)
- **Phase 2**: Keycloak realm with OIDC client support (already in place)
- **Phase 7**: Documentation structure for README.md (already in place)
- All other phases (3–8) provide the existing Symfony BFF and API endpoints that will be used in SSO testing

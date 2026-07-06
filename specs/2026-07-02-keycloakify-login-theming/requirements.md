# Requirements: Keycloak Login Theming

## Scope

**What this feature covers:**
- Customise the Keycloak **Login** and **Register** pages with a branded look using Keycloakify
- Set up a standalone Keycloakify theme project (`keycloak-theme/`) using React + TypeScript
- Build a `.jar` theme artifact and mount it into the existing Keycloak Docker container
- Develop with hot-reload via Storybook (no Keycloak dependency for UI work)
- Test inside a real Keycloak instance via `npx keycloakify start-keycloak` or the existing Docker Compose stack

**What this feature explicitly does NOT cover:**
- Account Theme, Email Theme, or Admin Theme customisation (out of scope, defer to future work)
- Structural changes to the existing Symfony BFF or Next.js SPA
- Production deployment, TLS, or secrets management — aligns with the mission's non-goals

## Context

The project already has a working Keycloak 26.x instance with the `playground` realm, two users (`user1`, `admin1`), and a full OIDC flow (Symfony BFF + Next.js SPA). The Keycloak login page currently uses Keycloak's default PatternFly theme, which is functional but un-branded.

Adding Keycloakify aligns with the project's goal of being a reference implementation for SSO architecture — a customised login theme demonstrates a production-adjacent setup where the identity provider's UI matches the application's brand.

Reference: `specs/mission.md` (SSO/BFF reference project), `specs/tech-stack.md` (Keycloak 26.x, Docker Compose).

## Decisions

1. **React framework** — Keycloakify has the most complete integration with React, including full Admin Theme support. Angular and Svelte have additional caveats (see Keycloakify docs). Since this is a reference project, React is the safest choice.

2. **Standalone project in monorepo** — The theme lives in `keycloak-theme/` at the repo root, separate from the Symfony backend and Next.js SPA. This avoids coupling dependencies and follows Keycloakify's recommended structure (standalone or subproject in monorepo).

3. **CSS-level first, component-level if needed** — Start with CSS customisations using `kc*` classes (fast, low-risk). Use `npx keycloakify eject-page` only for pages that need deeper structural changes. This minimises maintenance surface.

4. **JAR mounted into existing container** — The built `.jar` is mounted into the existing `keycloak` service in `docker-compose.yml` via a volume, rather than building a custom Docker image. This keeps the setup simple and avoids image rebuilds on theme changes.

5. **Development workflow** — UI development happens in Storybook (fast feedback). The JAR is rebuilt and tested inside Docker Compose before committing. The `npx keycloakify start-keycloak` command is available for isolated theme testing if needed.

## Keycloakify Version

Use the latest stable Keycloakify version compatible with Keycloak 26.x. The starter template handles version targeting automatically via the build process (produces `keycloak-theme-for-kc-all-other-versions.jar` for 26+).

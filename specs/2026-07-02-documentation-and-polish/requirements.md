# Requirements: Documentation & Polish

## Scope

**In scope:**
- A comprehensive README.md in the project root covering architecture, setup, usage, and architecture decisions
- Curl-based documentation for the API-only OIDC flow (token retrieval, protected resource access, logout)
- Documentation for the Keycloak admin UI manual access path
- Interview-ready talking points for SSO, OIDC, BFF pattern, and role-based access control
- Clean-clone verification that all `docker compose` commands work from a fresh clone
- Final review pass across all configuration files and code for consistency and completeness

**Out of scope:**
- No new features, endpoints, or functionality
- No UI styling or design system work
- No production deployment documentation (TLS, secrets management, HA)
- No CI/CD pipeline setup
- No automated test suite (beyond manual verification)
- No restructuring or refactoring of existing code
- No Mermaid/visual architecture diagrams — text-based description preferred for markdown portability

## Context

This is the final phase of the Keycloak BFF playground project. Phases 1-6 have built the complete stack:
- Docker Compose orchestration (Phase 1)
- Keycloak realm with users and roles (Phase 2)
- Symfony BFF with OIDC login/logout (Phase 3)
- User entity persistence and role sync (Phase 4)
- API endpoints with role-based access (Phase 5)
- Next.js SPA consuming the BFF API (Phase 6)

Per `specs/mission.md`, this project serves as:
- An interview preparation tool for SSO/OIDC/BFF architecture questions
- A reference implementation for the BFF security pattern
- A demonstrable learning project that shows the complete OAuth2 Authorization Code flow

Per `specs/tech-stack.md`, the project uses:
- Symfony 7.2 as the BFF (OIDC client secret stays server-side)
- Keycloak 26.x (Quarkus) as the identity provider
- PostgreSQL for both Keycloak and Symfony data
- Nginx → PHP-FPM serving pattern in Docker
- Next.js running locally (port 3000) outside Docker

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| README format | Plain Markdown | Universal rendering on GitHub, no tool dependencies |
| Architecture visualisation | Text description + directory tree | Portable, no Mermaid/binary image dependencies |
| API examples | curl commands | Available everywhere, zero setup, copy-paste ready |
| Interview format | Bullet-point talking points | Quick to scan, easy to expand during conversation |
| Clean-clone test | Manual `git clone` to temp dir | Realistic fresh-start validation without CI |
| Final review approach | Per-file checklist walkthrough | Systematic coverage without automation

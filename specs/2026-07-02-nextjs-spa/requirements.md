# Requirements: Next.js SPA

## Scope
A minimal Next.js single-page application that demonstrates the BFF pattern by consuming Symfony's session-based API endpoints. Covers login, profile display, protected resource access (ADMIN role), and logout.

**In scope:**
- Three pages: Login, Profile, Protected (ADMIN-only)
- SPA layout with navigation bar and logout button
- API proxy from Next.js dev server to Symfony (localhost:8080)
- Session-based auth via Symfony (no tokens exposed in browser)

**Out of scope:**
- UI styling, design system, or CSS framework
- Client-side routing beyond basic navigation
- SSR or SSG concerns — this is a client-side demo SPA
- Error boundaries or loading skeletons
- Tests

## Context
This is the frontend counterpart to phases 3-5 (Symfony OIDC + API endpoints). The SPA demonstrates how a frontend app can delegate authentication entirely to the Symfony BFF without ever handling JWT tokens directly. It uses Next.js App Router with client components.

The SPA runs outside Docker (locally on port 3000), mirroring a realistic dev setup where frontend developers work without the containerised backend.

## Decisions
1. **Next.js App Router** — modern default; `/app` directory with client components
2. **API proxy** — `next.config.js` rewrites `/api/*` to `localhost:8080/api/*` so all fetch calls are same-origin
3. **Client components** — all pages are client components since they depend on browser-side fetch calls
4. **No state management** — no Redux/Zustand. Each page fetches its own data on mount. Sufficient for a 3-page demo.
5. **Simple redirect-based auth** — login and logout are `<a>` tags pointing to Symfony routes, not programmatic POSTs

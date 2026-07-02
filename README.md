# Keycloack SSO Playground

> **Note:** The repository name spells "Keycloack" (with a 'c') — a known typo. The correct spelling is **Keycloak**. Kept as-is for consistency.

A hands-on learning project demonstrating the **Backend-for-Frontend (BFF) pattern** with **Symfony** acting as an OIDC/OAuth2 client to **Keycloak**, serving a **Next.js SPA**. Built for interview preparation and as a reference implementation for SSO architecture using the BFF security pattern.

---

## Table of Contents

- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Services Overview](#services-overview)
- [Directory Structure](#directory-structure)
- [End-to-End OIDC Flow](#end-to-end-oidc-flow)
- [Browser Flow (SPA)](#browser-flow-spa)
- [API / Curl Flow](#api--curl-flow)
- [Token Refresh](#token-refresh)
- [Keycloak Admin Console](#keycloak-admin-console)
- [How the Realm Export Maps to the Admin UI](#how-the-realm-export-maps-to-the-admin-ui)
- [Interview Talking Points](#interview-talking-points)
  - [SSO (Single Sign-On)](#sso-single-sign-on)
  - [OIDC Authorization Code Flow](#oidc-authorization-code-flow)
  - [BFF (Backend-for-Frontend) Pattern](#bff-backend-for-frontend-pattern)
  - [Role-Based Access Control](#role-based-access-control)
  - [Production Deployment Considerations](#production-deployment-considerations)

---

## Architecture

```
                         ┌─────────────────────────────────────┐
                         │          Internet / Localhost        │
                         └─────────────────────────────────────┘
                                      │
                    ┌─────────────────┼────────────────────┐
                    │                 │                     │
                    ▼                 ▼                     │
            ┌──────────────┐  ┌──────────────┐             │
            │   Browser    │  │    curl      │             │
            │ (Next.js SPA)│  │  (API-only)  │             │
            │  :3000       │  │              │             │
            └──────┬───────┘  └──────┬───────┘             │
                   │                 │                     │
                   │  Session        │  Bearer Token       │
                   │  Cookie         │  (Direct Grant)     │
                   │                 │                     │
                   ▼                 ▼                     │
            ┌──────────────────────────────────┐           │
            │        nginx (:8080)             │           │
            │    Reverse Proxy → PHP-FPM       │           │
            └──────────────┬───────────────────┘           │
                           │                               │
                           ▼                               │
            ┌──────────────────────────────────┐           │
            │     Symfony BFF (PHP 8.3)        │           │
            │  ┌────────────────────────────┐  │           │
            │  │  OIDC Client               │  │           │
            │  │  (knpu/oauth2-client)      │  │           │
            │  ├────────────────────────────┤  │           │
            │  │  Session Management        │  │           │
            │  │  (PostgreSQL-backed)       │  │           │
            │  ├────────────────────────────┤  │           │
            │  │  Token Refresh Service     │  │           │
            │  ├────────────────────────────┤  │           │
            │  │  User Entity / Role Sync   │  │           │
            │  │  (Doctrine ORM)            │  │           │
            │  ├────────────────────────────┤  │           │
            │  │  API Endpoints             │  │           │
            │  │  /api/me · /api/protected  │  │           │
            │  └────────────────────────────┘  │           │
            └──────────────┬───────────────────┘           │
                           │                               │
                           ▼                               ▼
            ┌──────────────────────────────────┐
            │      Keycloak 26.x (:8081)       │
            │  ┌────────────────────────────┐  │
            │  │  Playground Realm          │  │
            │  │  Users: user1, admin1     │  │
            │  │  Roles: USER, ADMIN       │  │
            │  │  Client: symfony-bff      │  │
            │  └────────────────────────────┘  │
            └──────────────┬───────────────────┘
                           │
                           ▼
            ┌──────────────────────────────────┐
            │  PostgreSQL 16 Alpine            │
            │  ├── keycloak DB                 │
            │  └── symfony DB                  │
            │      ├── user table              │
            │      └── sessions table          │
            └──────────────────────────────────┘
```

### Service Relationships

| Component | Role | Trust Model |
|---|---|---|
| **Browser (Next.js SPA)** | Renders UI, holds session cookie | Trusts Keycloak to authenticate users |
| **curl / API Client** | Direct API access via Password Grant | Exchanges credentials for tokens directly |
| **Nginx** | Reverse proxy to PHP-FPM | Adds CORS headers for cross-origin SPA requests |
| **Symfony BFF** | OIDC client, session manager, API provider | Trusts Keycloak to issue signed tokens |
| **Keycloak** | Identity Provider (IdP) | Source of truth for identities |
| **PostgreSQL** | Shared database | Persists Keycloak config, Symfony users, sessions |

**Key principle:** The backend (Symfony) does **not** trust the frontend (browser). It validates the session on every API call. The access token is never exposed to the browser — it stays server-side in the Symfony session.

---

## Prerequisites

- **Docker** and **Docker Compose** (for the backend stack: PostgreSQL, Keycloak, PHP, Nginx)
- **Node.js 18+** (for the Next.js SPA — runs outside Docker)
- **npm** or **yarn** (for installing Next.js dependencies)
- **curl** (for API-only demo flows)

---

## Quick Start

### 1. Start the Docker stack

```bash
docker compose up -d --build
```

This starts 4 services: PostgreSQL, Keycloak, PHP-FPM, and Nginx. Keycloak automatically imports the `playground` realm from `docker/keycloak/realm-export.json`.

### 2. Fix master realm SSL (first time only)

Keycloak's master realm requires SSL by default. Disable it for local development:

```bash
docker compose exec keycloak /opt/keycloak/bin/kcadm.sh config credentials \
  --server http://localhost:8080 --realm master --user admin --password admin
docker compose exec keycloak /opt/keycloak/bin/kcadm.sh update realms/master -s sslRequired=none
```

> **Note:** This fix is stored in the PostgreSQL volume. It survives `restart` and `up/down`, but if you delete the volume (`docker compose down -v`) you need to run it again.

### 3. Start the Next.js SPA

```bash
cd nextjs-app
npm install
npm run dev
```

### 4. Open the app

- **SPA:** http://localhost:3000
- **Symfony BFF:** http://localhost:8080 (redirects to Keycloak login)
- **Keycloak Admin Console:** http://localhost:8081

### Credentials

| Role | Username | Password | Realm Roles |
|---|---|---|---|
| Standard user | `user1` | `user1` | `USER` |
| Administrator | `admin1` | `admin1` | `USER`, `ADMIN` |
| Keycloak admin | `admin` | `admin` | Master realm admin |

### 5. Verify it works

1. Open http://localhost:3000 in your browser
2. Click **Login with Keycloak**
3. Sign in as `user1` / `user1`
4. You'll be redirected to the **Profile** page showing your user info
5. Click **Protected** in the nav — you should see **"Access Denied"** (user1 has only USER role)
6. Click **Logout**, log in as `admin1` / `admin1`
7. Visit **Protected** — you should see **"Welcome, admin!"**

---

## Services Overview

| Service | Docker Image | Internal Port | External Port | Purpose |
|---|---|---|---|---|
| `postgres` | `postgres:16-alpine` | 5432 | — | Shared database (Keycloak + Symfony) |
| `keycloak` | `quay.io/keycloak/keycloak:26.1` | 8080 | 8081 | Identity Provider, realm import |
| `php` | Custom (`php:8.3-fpm-alpine`) | 9000 | — | Symfony runtime (PHP-FPM) |
| `nginx` | `nginx:alpine` | 80 | 8080 | Reverse proxy to PHP-FPM |

All services share a single `internal` Docker bridge network. Data persistence is provided by the `pgdata` volume.

---

## Directory Structure

```
keycloack-playground/
│
├── docker-compose.yml              # Orchestrates all 4 services
├── README.md                       # This file
├── CONTEXT.md                      # Agent/LLM context documentation
│
├── docker/
│   ├── keycloak/
│   │   └── realm-export.json       # Declarative Keycloak realm config
│   ├── nginx/
│   │   └── default.conf            # Nginx config + CORS for SPA
│   └── php/
│       └── Dockerfile              # PHP 8.3-FPM Alpine image
│
├── symfony/                        # Symfony BFF application
│   ├── .env                        # Default env vars (committed)
│   ├── .env.local                  # Local overrides (gitignored)
│   ├── composer.json
│   ├── config/
│   │   ├── packages/
│   │   │   ├── security.yaml       # OIDC authenticator, access control
│   │   │   ├── framework.yaml      # Session config (PostgreSQL)
│   │   │   ├── knpu_oauth2_client.yaml  # Keycloak OIDC client config
│   │   │   ├── doctrine.yaml       # ORM + DB config
│   │   │   └── ...
│   │   ├── routes.yaml             # Route definitions
│   │   └── services.yaml           # DI services, parameters, bindings
│   ├── src/
│   │   ├── Controller/
│   │   │   ├── LoginController.php      # /login, /login/check routes
│   │   │   ├── LogoutController.php     # /logout + Keycloak back-channel
│   │   │   ├── ApiController.php        # /api/me, /api/protected
│   │   │   ├── DashboardController.php  # Symfony Twig routes
│   │   │   └── HealthController.php     # Health check endpoint
│   │   ├── Core/Security/
│   │   │   ├── Service/
│   │   │   │   ├── KeycloakAuthenticator.php  # Custom OIDC authenticator
│   │   │   │   ├── UserProvider.php           # Loads User from Doctrine
│   │   │   │   ├── TokenStorage.php           # Session-backed token store
│   │   │   │   └── TokenRefreshService.php    # Auto-refresh expired tokens
│   │   │   ├── EventSubscriber/
│   │   │   │   └── TokenRefreshSubscriber.php # Hooks refresh into /api requests
│   │   │   └── Voter/
│   │   │       └── ApiAccessVoter.php         # Role-based API access (USER/ADMIN)
│   │   ├── Entity/
│   │   │   └── User.php              # Doctrine User entity
│   │   ├── Repository/
│   │   │   └── UserRepository.php    # Doctrine repository
│   │   └── Service/User/
│   │       ├── Model/
│   │       │   └── UserCreateModel.php  # Input DTO with validation
│   │       └── Service/
│   │           ├── UserService.php      # Validation facade
│   │           └── UserManager.php      # Create/update logic + role mapping
│   ├── templates/
│   │   ├── base.html.twig           # Base layout
│   │   └── dashboard/
│   │       ├── home.html.twig       # Symfony home page
│   │       └── dashboard.html.twig  # Symfony dashboard (debug view)
│   ├── migrations/
│   │   └── Version20260701165307.php    # Doctrine migration
│   └── public/
│       └── index.php               # Symfony front controller
│
├── nextjs-app/                      # Next.js SPA (runs outside Docker)
│   ├── src/
│   │   ├── app/
│   │   │   ├── page.tsx            # Home/landing page
│   │   │   ├── layout.tsx          # Root layout + AuthProvider
│   │   │   ├── navbar.tsx          # Navigation bar
│   │   │   ├── login/page.tsx      # Login page
│   │   │   ├── profile/page.tsx    # Profile page (calls /api/me)
│   │   │   └── protected/page.tsx  # Protected resource page
│   │   └── lib/
│   │       ├── config.ts           # Symfony URL config
│   │       └── use-auth.tsx        # Auth context provider
│   ├── .env.example                # Environment template
│   └── package.json
│
└── specs/                          # Project specifications & roadmap
    ├── mission.md
    ├── tech-stack.md
    ├── roadmap.md
    └── 2026-07-02-documentation-and-polish/
        ├── plan.md
        ├── requirements.md
        └── validation.md
```

---

## End-to-End OIDC Flow

Here's what happens when a user clicks "Login with Keycloak" in the SPA:

```
Step 1: Browser → Symfony
        GET http://localhost:8080/login
        └── Symfony redirects to Keycloak's authorization endpoint

Step 2: Browser → Keycloak
        GET http://localhost:8081/realms/playground/protocol/openid-connect/auth
            ?response_type=code
            &client_id=symfony-bff
            &redirect_uri=http://localhost:8080/login/check
            &scope=openid+profile+email+roles
            &state=...

Step 3: User logs in at Keycloak
        Keycloak validates credentials (user1/user1)

Step 4: Keycloak → Browser (redirect)
        HTTP 302 → http://localhost:8080/login/check?code=...&state=...

Step 5: Browser → Symfony /login/check
        Symfony's KeycloakAuthenticator intercepts this route:
        1. Exchanges the authorization code for tokens (access, refresh, ID)
        2. Fetches user info from the ID token
        3. Creates or updates the Doctrine User entity
        4. Stores tokens in the PostgreSQL-backed session
        5. Sets a session cookie (HttpOnly, SameSite=Lax)
        6. Redirects to /profile (which redirects to the Next.js SPA)

Step 6: Browser → Next.js SPA /profile
        SPA calls GET http://localhost:8080/api/me (with session cookie)
        Symfony reads the session, returns user profile

Step 7: Browser → Next.js SPA /protected
        SPA calls GET http://localhost:8080/api/protected (with session cookie)
        Symfony checks the ApiAccessVoter for ROLE_ADMIN
        → 200 if admin, 403 if regular user

Step 8: User clicks Logout
        Browser → Symfony /logout
        Symfony:
        1. Reads the ID token from session
        2. Invalidates the Symfony session
        3. Redirects to Keycloak's logout endpoint
           → Keycloak ends the SSO session
           → Redirects back to http://localhost:3000
```

---

## Browser Flow (SPA)

### Login → Profile → Protected → Logout

1. Open http://localhost:3000
2. Click **Login with Keycloak** — redirected to Keycloak login page
3. Sign in as `user1` / `user1`
4. You're redirected to the **Profile** page showing your username, email, and roles
5. Click **Protected** — you'll see **"Access Denied"** because `user1` lacks the ADMIN role
6. Click **Logout** in the navbar
7. Login as `admin1` / `admin1`
8. Visit **Profile** — shows ADMIN role in the roles list
9. Visit **Protected** — shows **"Welcome, admin!"** with access granted

---

## API / Curl Flow

The API supports direct access using the **Resource Owner Password Credentials (ROPC)** grant — also called the **Direct Access Grant** in Keycloak. This is useful for API clients, automation, and testing.

> **Security note:** The Password Grant should generally not be used in browser-based applications. It's included here for API clients and testing purposes only.

### 1. Get a Token (Password Grant)

```bash
curl -s -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=symfony-bff" \
  -d "client_secret=symfony-bff-secret" \
  -d "grant_type=password" \
  -d "username=user1" \
  -d "password=user1" \
  -d "scope=openid profile email roles" | jq .
```

**Response:**

```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIs...",
  "expires_in": 300,
  "refresh_token": "eyJhbGciOiJIUzUxMiIs...",
  "id_token": "eyJhbGciOiJSUzI1NiIs...",
  "token_type": "Bearer",
  "not-before-policy": 0,
  "session_state": "...",
  "scope": "openid profile email roles"
}
```

> **Tip:** Pipe through `jq .` for pretty-printed JSON. If you don't have jq, omit `| jq .` for raw output.

### 2. Call `/api/me` (Authenticated User Info)

This endpoint returns the authenticated user's profile. It works for any authenticated user.

```bash
# Store the token in a variable
TOKEN=$(curl -s -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=symfony-bff" \
  -d "client_secret=symfony-bff-secret" \
  -d "grant_type=password" \
  -d "username=user1" \
  -d "password=user1" \
  -d "scope=openid profile email roles" | jq -r '.access_token')

# Call /api/me
curl -s http://localhost:8080/api/me \
  -H "Authorization: Bearer $TOKEN" | jq .
```

**Response (user1):**

```json
{
  "email": "user1@playground.local",
  "username": "user1",
  "roles": [
    "ROLE_USER"
  ],
  "lastLogin": "2026-07-02T12:00:00+00:00"
}
```

### 3. Call `/api/protected` — USER vs ADMIN Role Test

This endpoint requires the `ADMIN` role. Calling it as a regular user returns 403.

#### As user1 (USER role only — expect 403):

```bash
USER_TOKEN=$(curl -s -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=symfony-bff" \
  -d "client_secret=symfony-bff-secret" \
  -d "grant_type=password" \
  -d "username=user1" \
  -d "password=user1" \
  -d "scope=openid profile email roles" | jq -r '.access_token')

curl -s http://localhost:8080/api/protected \
  -H "Authorization: Bearer $USER_TOKEN" | jq .
```

**Response (403):**

```json
{
  "error": "forbidden",
  "message": "Access denied. ADMIN role is required."
}
```

#### As admin1 (USER + ADMIN role — expect 200):

```bash
ADMIN_TOKEN=$(curl -s -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=symfony-bff" \
  -d "client_secret=symfony-bff-secret" \
  -d "grant_type=password" \
  -d "username=admin1" \
  -d "password=admin1" \
  -d "scope=openid profile email roles" | jq -r '.access_token')

curl -s http://localhost:8080/api/protected \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq .
```

**Response (200):**

```json
{
  "message": "Welcome, admin! You have access to the protected resource.",
  "username": "admin1"
}
```

### 4. Token Refresh

When the access token is about to expire, exchange the refresh token for a new set of tokens:

```bash
# Get tokens (including refresh_token)
TOKEN_RESPONSE=$(curl -s -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=symfony-bff" \
  -d "client_secret=symfony-bff-secret" \
  -d "grant_type=password" \
  -d "username=user1" \
  -d "password=user1" \
  -d "scope=openid profile email roles")

REFRESH_TOKEN=$(echo "$TOKEN_RESPONSE" | jq -r '.refresh_token')

# Refresh
curl -s -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=symfony-bff" \
  -d "client_secret=symfony-bff-secret" \
  -d "grant_type=refresh_token" \
  -d "refresh_token=$REFRESH_TOKEN" | jq .
```

**Response:** A new set of tokens with a fresh `access_token`, `refresh_token`, and `id_token`.

### 5. Logout

To log out, hit the Keycloak end-session endpoint with the ID token:

```bash
# Get a fresh ID token
TOKEN_RESPONSE=$(curl -s -X POST http://localhost:8081/realms/playground/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=symfony-bff" \
  -d "client_secret=symfony-bff-secret" \
  -d "grant_type=password" \
  -d "username=user1" \
  -d "password=user1" \
  -d "scope=openid profile email roles")

ID_TOKEN=$(echo "$TOKEN_RESPONSE" | jq -r '.id_token')

# Logout via Keycloak
curl -v "http://localhost:8081/realms/playground/protocol/openid-connect/logout?post_logout_redirect_uri=http://localhost:3000&id_token_hint=$ID_TOKEN"
```

---

## Token Refresh

In the Symfony BFF, token refresh happens **automatically and transparently** for API requests:

1. **TokenRefreshSubscriber** listens to `KernelEvents::REQUEST` for paths starting with `/api`
2. Before the controller runs, **TokenRefreshService** checks if the stored access token is within 30 seconds of expiry
3. If expired, it exchanges the refresh token for a new set of tokens via Keycloak's token endpoint
4. The new tokens are stored in the session; the API request proceeds normally
5. If refresh fails (revoked session, network error), the session is cleared and the user must re-authenticate

This means the **Next.js SPA never needs to handle token refresh** — it just makes API calls with its session cookie, and Symfony handles the OIDC token lifecycle server-side.

---

## Keycloak Admin Console

Keycloak includes a web-based administration console for managing realms, users, roles, and clients.

### Access

| Detail | Value |
|---|---|
| URL | http://localhost:8081 |
| Username | `admin` |
| Password | `admin` |
| Realm to manage | Select **playground** from the realm dropdown (top-left) |

### What to Find in the Admin UI

#### Users
- Navigate to **Manage → Users**
- You'll see `user1` and `admin1` — the two pre-configured users
- Click a username to view/edit details, credentials, role mappings, and sessions

#### Roles
- Navigate to **Manage → Realm Roles**
- You'll see `USER`, `ADMIN`, and default roles (`offline_access`, `uma_authorization`)
- Click a role to view its description or assign it to users/service accounts

#### Client Configuration
- Navigate to **Manage → Clients**
- Find `symfony-bff` — the confidential OIDC client
- Key settings:
  - **Client authentication:** ON (confidential client with secret)
  - **Standard flow:** ENABLED (Authorization Code flow)
  - **Valid redirect URIs:** `http://localhost:8080/login/check`
  - **Valid post logout redirect URIs:** `http://localhost:3000`
  - **Client secret:** `symfony-bff-secret` (under the **Credentials** tab)

#### Client Scopes
- Navigate to **Manage → Client Scopes**
- The `roles` scope includes protocol mappers that inject `realm_roles` into the access and ID tokens
- The `profile` scope maps `username`, `given_name`, `family_name`, and `email` claims

#### Realm Settings
- Navigate to **Manage → Realm Settings**
- General tab: Realm name, display name, enabled status
- Login tab: Email as username, remember me, verify email toggles
- Tokens tab: Access token lifespan (default 5 minutes), refresh token lifespan

---

## How the Realm Export Maps to the Admin UI

The file `docker/keycloak/realm-export.json` is a complete, declarative snapshot of the `playground` realm. Every section corresponds directly to a section in the admin console:

| JSON Path | Admin Console Location | What It Defines |
|---|---|---|
| `$.realm` | Realm Settings → General | Realm name and display name |
| `$.sslRequired` | Realm Settings → Login | SSL requirement (`none` for local dev) |
| `$.roles.realm[]` | Manage → Realm Roles | Custom roles: `USER`, `ADMIN`, and built-in roles |
| `$.users[]` | Manage → Users | Test users with credentials and role assignments |
| `$.clients[]` | Manage → Clients | OIDC clients including `symfony-bff` with its secret, redirect URIs, and flow settings |
| `$.clientScopes[]` | Manage → Client Scopes | Scope definitions including `roles` with protocol mappers |
| `$.defaultDefaultClientScopes` | Client → Client Scopes tab | Scopes automatically assigned to every client |
| `$.components["org.keycloak.keys.KeyProvider"]` | Realm Settings → Keys | Signing key configuration (RSA 2048-bit RS256) |

**The benefit of declarative config:** Instead of clicking through the admin UI to configure these settings manually, you edit `realm-export.json` and restart Keycloak. The import is automatic on container start thanks to the `--import-realm` command flag and the volume mount at `/opt/keycloak/data/import/`.

---

## Interview Talking Points

### SSO (Single Sign-On)

**What SSO solves:**
- Users authenticate once and gain access to multiple applications without re-entering credentials
- Eliminates password fatigue and reduces credential sprawl
- Centralizes authentication policy (password complexity, MFA, session timeouts)

**How SSO works here:**
- Keycloak is the central authentication authority
- When a user logs in via the Symfony BFF, Keycloak creates an SSO session
- If other applications (not in this project) were registered in the same realm, the user would not need to re-authenticate
- Logging out of one application terminates the SSO session, logging the user out everywhere

**Key tradeoffs:**
- **Pro:** Centralized security policy, better UX, reduced password-related support tickets
- **Con:** Single point of failure — if Keycloak is down, no one can authenticate
- **Con:** Session management complexity — logout needs to propagate across all applications

**Protocols:** While this project uses OIDC (OAuth2 + OpenID Connect), SSO can also be implemented with SAML 2.0 (common in enterprise) or CAS (legacy).

---

### OIDC Authorization Code Flow

**What is OIDC?**
- OpenID Connect (OIDC) is an identity layer on top of OAuth 2.0
- OAuth 2.0 is about **delegated access** ("what you can do")
- OIDC adds **authentication** ("who you are") via the ID Token

**The flow step by step:**

```
1. Client → Authorization Server:  Authorization Request
2. User authenticates at the Authorization Server
3. Authorization Server → Client:  Authorization Code
4. Client → Authorization Server:  Token Request (code + secret)
5. Authorization Server → Client:  Access Token + ID Token + Refresh Token
6. Client → Resource Server:       API call with Access Token
7. Resource Server:                Validates JWT locally (no callback to IdP)
```

**Key concepts:**
- **ID Token (JWT):** Contains identity claims (sub, email, preferred_username). Verified by signature, not encrypted by default
- **Access Token (JWT):** Contains authorization claims (roles, scopes). Sent to APIs
- **Refresh Token:** Long-lived token exchanged for new access tokens. Must be stored securely
- **Scopes:** `openid` (required for OIDC), `profile`, `email`, `roles` — each grants access to specific claims

**Why Authorization Code (not Implicit or Password grant):**
- The code is a one-use credential exchanged server-side for tokens
- The client secret proves the client's identity in the token exchange
- Tokens never pass through the browser (in the BFF pattern)
- More secure than Implicit flow (which exposes tokens in URL fragments)

---

### BFF (Backend-for-Frontend) Pattern

**What is BFF?**
- A backend service dedicated to serving a specific frontend application
- It acts as an intermediary between the frontend and downstream services (including the identity provider)
- Popularized by Sam Newman and Phil Calçado; widely adopted in microservices architectures

**Why BFF for OIDC security:**

| Concern | Browser + BFF | Browser + Direct OIDC |
|---|---|---|
| Token storage | HttpOnly cookie (not accessible to JS) | localStorage (accessible to any JS) |
| Refresh token | Server-side only | Exposed to browser JavaScript |
| Client secret | Server-side only | N/A (public client — PKCE required) |
| Token refresh | Automatic, server-initiated | Manual, client-initiated |
| CSRF risk | Mitigated by SameSite cookie + CSRF tokens | No CSRF (bearer tokens) but XSS steals them |

**Architecture tradeoffs:**
- **Security:** Much stronger — tokens never reach the browser
- **Statefulness:** Symfony sessions are stateful; scaling horizontally requires shared Redis session storage
- **Latency:** One extra hop via the BFF, but token validation is local (JWKS) — no per-request call to Keycloak
- **Complexity:** More moving parts than a direct SPA + JWT approach

**What makes this a BFF specifically:**
1. Symfony handles the full OIDC login dance (not the SPA)
2. The SPA talks to Symfony, not directly to Keycloak
3. Symfony stores and refreshes tokens server-side
4. The SPA only receives a session cookie

**Common production deployment:**

```
Load Balancer
    ├── Symfony BFF #1  ─┐
    ├── Symfony BFF #2  ─┤── Redis (shared sessions)
    └── Symfony BFF #3  ─┘
                              └── Keycloak (HA with DB replication)
```

---

### Role-Based Access Control

**How roles work in this project:**

```
Keycloak (source of truth)
    │
    │  realm_roles: ["USER", "ADMIN"]
    ▼
Symfony (role mapping)
    │
    │  "USER"    → ROLE_USER
    │  "ADMIN"   → ROLE_ADMIN
    │
    ▼
Doctrine User entity
    │
    │  roles: ["ROLE_USER", "ROLE_ADMIN"]
    │
    ▼
ApiAccessVoter
    │
    ├── /api/me        → any authenticated user (ROLE_USER)
    └── /api/protected → ROLE_ADMIN only
```

**Key architectural decisions:**
- **Roles are synced from Keycloak**, not managed locally
- The Symfony `User` entity caches roles as a local copy of Keycloak's data
- Role mapping happens in `UserManager::mapRoles()` — Keycloak role names are converted to Symfony convention (`ROLE_USER`, `ROLE_ADMIN`)
- Authorization is enforced by a **Symfony Voter** (`ApiAccessVoter`), which is decoupled from the controller logic

**Why Voters instead of `#[IsGranted]` attributes:**
- Voters can encapsulate complex authorization logic (multiple attributes, custom subjects)
- They are testable in isolation
- They can be composed: multiple voters can vote on the same resource

**How to add a new role:**
1. Add the role in `realm-export.json` under `roles.realm[]`
2. Assign it to users via `realmRoles` on the user objects
3. Add a mapping in `UserManager::mapRoles()`
4. Create or update a Voter for the new permission

---

### Production Deployment Considerations

**What would change for production:**

| Aspect | Development | Production |
|---|---|---|
| **TLS/HTTPS** | HTTP only | TLS everywhere (Keycloak, Symfony, SPA) |
| **Secrets** | `symfony-bff-secret` in .env.local | Secrets manager or K8s secrets |
| **Session storage** | PostgreSQL (single instance) | Redis (shared, HA) |
| **Keycloak start** | `start-dev --import-realm` | `start --optimized` with production DB |
| **Master realm SSL** | Disabled (`sslRequired=none`) | Required (enabled by default) |
| **Keycloak hostname** | `KC_HOSTNAME=http://localhost:8081` | Real domain with proper TLS |
| **Password grant** | Enabled for curl testing | **Disabled** (not for browser apps) |
| **CORS** | Open to `localhost:3000` | Restricted to production SPA domain |
| **Logging** | Symfony dev defaults | Structured logging (JSON, ELK stack) |
| **Container orchestration** | Docker Compose (single host) | Kubernetes or ECS (multi-host) |
| **Database** | Single PostgreSQL instance | HA PostgreSQL with replication or RDS/Aurora |

**Production Keycloak checklist:**
- Use a production-grade database (PostgreSQL RDS, Aurora, etc.) — not the embedded H2
- Enable TLS with a valid certificate
- Configure a proper hostname (`KC_HOSTNAME` must match the public URL)
- Set up backup and disaster recovery for the realm configuration
- Use `start --optimized` after building a production image with `--optimized` flag
- Restrict `admin-cli` client to trusted networks only
- Rotate client secrets regularly

**Production Symfony BFF checklist:**
- Replace PostgreSQL session storage with Redis (`session.handler.redis` or Symfony's Redis adapter)
- Set up Redis Sentinel or Redis Cluster for HA session storage
- Load balancer in front of nginx → multiple PHP-FPM instances
- Move secrets to environment variables or a secrets manager (not `.env.local`)
- Enable OPCache (already configured in Dockerfile)
- Disable the Password Grant flow on the Keycloak client (if not needed for API clients)
- Use Symfony's built-in rate limiting for API endpoints

---

## Final Review Checklist

- [x] `docker-compose.yml` — all services start, healthy, and on the correct ports
- [x] `realm-export.json` — roles (USER, ADMIN), users (user1, admin1), client (symfony-bff) all configured
- [x] `security.yaml` — OIDC custom_authenticator, PUBLIC_ACCESS for /login, IS_AUTHENTICATED_FULLY for /api
- [x] `knpu_oauth2_client.yaml` — keycloak_pkce client with correct scopes and encryption algorithm
- [x] `framework.yaml` — DB-backed sessions via PDO session handler
- [x] `services.yaml` — Scalar binding for frontend-host, keycloak URLs; Twig global for TokenStorage
- [x] `.env` / `.env.local` — All required env vars documented and local overrides in .gitignore
- [x] `nginx/default.conf` — CORS configured for localhost:3000, PHP-FPM proxy correct
- [x] Next.js `config.ts` — SYMFONY_URL defaults to http://localhost:8080
- [x] Next.js `.env.example` — documents NEXT_PUBLIC_SYMFONY_URL
- [x] No TODO comments, debug code, or stale references found
- [x] Repository name typo ("Keycloack") is consistently used throughout

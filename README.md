## Quick Start

```bash
# Start the stack
docker compose up -d

# Fix: master realm requires SSL by default → disable for local dev
docker compose exec keycloak /opt/keycloak/bin/kcadm.sh config credentials \
  --server http://localhost:8080 --realm master --user admin --password admin
docker compose exec keycloak /opt/keycloak/bin/kcadm.sh update realms/master -s sslRequired=none
```

**URLs & Credentials**

| What | URL / Command |
|------|---------------|
| Keycloak Admin Console | http://localhost:8081 |
| Master realm admin | `admin` / `admin` |
| Playground realm admin | `admin1` / `admin1` |
| Playground realm user | `user1` / `user1` |
| Symfony app | http://localhost:8080 |

> **Note:** The master realm SSL fix is stored in the PostgreSQL volume. It survives `restart` and `up/down`, but if you delete the volume (`docker compose down -v`) you need to run the fix again.

---

You're asking exactly the right questions. The biggest mental shift with OAuth2/OIDC is understanding **what each token is for** and **who is supposed to trust whom**.

Let's build the mental model first, then map it to Keycloak.

---

# The actors

```
+-----------+          +-----------+          +------------+
| Frontend  | <------> |  Backend  | <------> | Keycloak   |
| (SPA)     |          | REST API  |          |  IdP       |
+-----------+          +-----------+          +------------+
```

Each component trusts different things.

* **Frontend** trusts Keycloak to authenticate users.
* **Backend** trusts Keycloak to issue signed access tokens.
* **Keycloak** is the source of truth for identities.

Notice that the backend does **not** trust the frontend.

---

# Authentication flow

A modern SPA usually uses the Authorization Code Flow with PKCE.

```
1. User opens SPA

2. SPA redirects user to Keycloak

3. User logs in

4. Keycloak returns

    Authorization Code

5. SPA exchanges it for

    Access Token
    Refresh Token
    ID Token

6. SPA stores them (carefully)

7. SPA calls backend

Authorization: Bearer eyJhbGci...
```

---

# What are these tokens?

This is probably the most confusing part.

## ID Token

Think of it as:

> "Here is information about the user."

Contains:

* username
* email
* name
* picture
* etc.

The frontend uses it.

The backend usually **doesn't need it**.

---

## Access Token

Think of it as:

> "This user is allowed to call this API."

The backend uses this.

Example:

```
Authorization: Bearer eyJhbGc...
```

This is the token your REST API should verify.

---

## Refresh Token

Think of it as:

> "Please issue me another access token."

Only exchanged with Keycloak.

Never sent to your REST API.

---

# Can I use the Access Token to call my backend?

**Yes.**

In fact, that's exactly what it is for.

```
SPA
  |
  | Authorization: Bearer AccessToken
  |
  V
Backend
```

This is the normal OAuth2 architecture.

---

# What does the backend do?

The backend should:

```
Receive JWT

↓

Verify signature

↓

Verify expiration

↓

Verify issuer

↓

Verify audience

↓

Extract user/roles

↓

Continue
```

Notice something important:

The backend is **not asking Keycloak if the token is valid**.

Instead it verifies the cryptographic signature.

---

# Why is that possible?

Because JWTs are signed.

Imagine the token is

```
Header

Payload

Signature
```

The signature is created with Keycloak's private key.

Only Keycloak owns that private key.

The backend downloads the **public key**.

```
Keycloak

Private Key
      |
      V

Signs JWT
```

Backend:

```
Public Key

↓

Verify signature
```

If the signature matches:

* token wasn't modified
* token was issued by Keycloak

---

# Where does the backend get the public key?

Keycloak exposes a JWKS endpoint.

Example

```
https://keycloak.example.com/realms/myrealm/protocol/openid-connect/certs
```

Your JWT library downloads those keys automatically.

Examples:

Spring Security

```
issuer-uri:
https://keycloak/.../realm/demo
```

Node

```
jwks-rsa
```

Python

```
PyJWT + JWKS
```

ASP.NET

```
AddJwtBearer()
```

Almost every framework supports this automatically.

---

# Should I call Keycloak for every request?

**No.**

This is a very common misconception.

Bad:

```
Frontend

↓

Backend

↓

"Hey Keycloak,
is this token valid?"

↓

Keycloak

↓

Response
```

Now every API call requires another network request.

Terrible for latency.

---

Instead:

```
Frontend

↓

Backend

↓

Verify JWT locally

↓

Done
```

Verification takes microseconds.

No network call.

---

# Should I cache the public keys?

Yes.

Actually, your JWT library usually does it.

The backend periodically refreshes them.

Flow:

```
First request

↓

Download JWKS

↓

Cache

↓

Verify locally

↓

Thousands of requests

↓

Occasionally refresh JWKS
```

You almost never implement this yourself.

---

# What should the backend verify?

At minimum:

### Signature

Is it signed by Keycloak?

---

### Expiration

```
exp
```

Reject expired tokens.

---

### Not Before

```
nbf
```

Reject if token isn't valid yet.

---

### Issuer

```
iss
```

Should equal your Keycloak realm.

Example

```
https://keycloak.company.com/realms/internal
```

---

### Audience

```
aud
```

Ensure the token was issued for your API.

Otherwise someone could reuse a token intended for another service.

---

### Scopes / Roles

Example

```
roles:

admin

editor

viewer
```

or

```
scope

orders.read
orders.write
```

Authorize based on these claims.

---

# What about logout?

Here's the interesting part.

Suppose:

```
Access Token

Expires in 15 minutes
```

User logs out.

The token is still cryptographically valid until expiration.

This surprises many developers.

JWTs are intentionally stateless.

That's why access tokens are usually short-lived.

Example:

```
Access Token

10-15 min

Refresh Token

30 days
```

When the user logs out:

* refresh token is revoked
* no new access tokens can be issued
* existing access token naturally expires soon

---

# When would I call Keycloak?

Rarely.

Mostly for:

* Login
* Refresh tokens
* Logout
* User information (if needed)
* Token introspection (only when using opaque tokens or if immediate revocation checks are required)

For normal JWT access tokens:

**No call per request.**

---

# Complete request lifecycle

```
               Login
                 │
                 ▼
            +-----------+
            | Keycloak  |
            +-----------+
                 │
      Access + Refresh Token
                 │
                 ▼
           +-----------+
           |    SPA    |
           +-----------+
                 │
 Authorization: Bearer <Access Token>
                 │
                 ▼
          +--------------+
          |   Backend    |
          +--------------+
                 │
      Verify JWT locally:
      ✔ Signature
      ✔ exp
      ✔ iss
      ✔ aud
      ✔ roles/scopes
                 │
                 ▼
            Business Logic
```

## Best practices

* Use the **Authorization Code Flow with PKCE** for SPAs.
* Send the **access token** in the `Authorization: Bearer` header to your backend.
* Validate JWTs **locally** using Keycloak's JWKS endpoint—don't call Keycloak for every request.
* Ensure your validation checks the signature, expiration, issuer, audience, and any required scopes or roles.
* Keep access tokens short-lived (typically 5–15 minutes) and use refresh tokens to obtain new ones.
* Let your JWT library cache and refresh the JWKS automatically.

Once this model is clear, integrating Keycloak into frameworks like Spring Boot, ASP.NET, Node.js (Express/NestJS), or Python (FastAPI) becomes mostly a matter of configuration rather than implementing the protocol yourself.



Yes. **This architecture scales well**, but you need to think differently than with a stateless JWT architecture.

The tradeoff is essentially:

* **Pure SPA + JWT** → stateless, horizontally scalable, more frontend complexity and token exposure.
* **BFF + Session** → stateful, much better security, requires shared session storage when scaling.

For most enterprise applications, the BFF approach is often the preferred choice because the security benefits outweigh the operational cost of managing sessions.

---

## The scalability mental model

Imagine you have three Symfony instances behind a load balancer.

```
                  +----------------+
                  | Load Balancer  |
                  +----------------+
                   /      |      \
                  /       |       \
                 v        v        v
            +-------+ +-------+ +-------+
            | App1  | | App2  | | App3  |
            +-------+ +-------+ +-------+
```

A user logs in.

The first request lands on **App1**.

App1 creates a session.

The next request might land on **App3**.

How does App3 know who the user is?

That's where shared session storage comes in.

---

## Option 1: Local filesystem ❌

```
App1
 └── session file

App2
 └── doesn't have it
```

This only works if you have a single server.

Not suitable for horizontal scaling.

---

## Option 2: Sticky sessions 🟡

The load balancer always sends the user to the same server.

```
User A -> App1
User B -> App2
```

This works, but has drawbacks:

* uneven load
* server failures log users out
* harder deployments
* poor elasticity

It's generally better to avoid relying on sticky sessions if you can.

---

## Option 3: Redis ✅ (most common)

```
             Redis
          +-----------+
          | Sessions  |
          +-----------+
           ^   ^   ^
          /    |    \
     App1  App2  App3
```

Any Symfony instance can:

* read the session
* update the session
* refresh tokens

The load balancer can send requests anywhere.

This is the most common production setup.

---

## Option 4: Database 🟡

```
PostgreSQL

Session Table
```

It works.

Symfony supports it.

However:

* slower than Redis
* unnecessary writes
* database becomes busier

It's a reasonable choice for smaller deployments, but Redis is usually preferred for session storage.

---

# What is stored in the session?

Surprisingly little.

For example:

```
session_id

↓

user_id

↓

access_token

↓

refresh_token

↓

expires_at
```

Even if each session is a few kilobytes, Redis can comfortably hold hundreds of thousands of active sessions on modest hardware.

---

# Isn't JWT supposed to avoid server-side state?

Yes.

JWTs were designed to make **resource servers** stateless.

But in a BFF, **Symfony is acting as an OAuth client**, not just a resource server.

That changes the design.

Instead of this:

```
Browser

↓

JWT

↓

API
```

You have:

```
Browser

↓

Session Cookie

↓

Symfony

↓

JWT

↓

Other APIs
```

The browser never carries the JWT.

---

# Security advantages

This is where the BFF really shines.

## No localStorage

One of the biggest concerns with SPAs is:

```javascript
localStorage.getItem("access_token")
```

Any injected JavaScript running in your page can potentially read tokens stored there.

With a BFF:

```
Browser

↓

HttpOnly Cookie
```

JavaScript cannot read an `HttpOnly` cookie.

That doesn't eliminate all XSS risks, but it prevents attackers from simply stealing long-lived tokens.

---

## Refresh token never leaves the server

This is arguably the biggest security improvement.

Instead of:

```
Browser

Refresh Token
```

You have:

```
Symfony

Refresh Token
```

An attacker compromising browser JavaScript cannot steal the refresh token because it never reaches the browser.

---

## Client Secret stays on the server

If your Keycloak client is confidential, the client secret is stored only in Symfony.

React never sees it.

---

## Automatic token refresh

When the access token expires:

```
Symfony

↓

Refresh Token

↓

Keycloak

↓

New Access Token
```

The React application continues making normal API calls without needing to know anything about OAuth.

---

# What about CSRF?

Since authentication is cookie-based, CSRF protection becomes important again.

Your API should:

* use `SameSite=Lax` or `SameSite=Strict` cookies when appropriate
* use Symfony's CSRF protection for state-changing operations where applicable
* ensure CORS is tightly configured if the frontend and backend are on different origins

This is a different threat model from bearer tokens, but it's well understood and well supported by Symfony.

---

# A typical production deployment

```
                    Internet
                        │
                        ▼
                +----------------+
                | Load Balancer  |
                +----------------+
                 /      |       \
                /       |        \
               ▼        ▼         ▼
          +--------+ +--------+ +--------+
          |Symfony | |Symfony | |Symfony |
          | BFF #1 | | BFF #2 | | BFF #3 |
          +--------+ +--------+ +--------+
                \       |        /
                 \      |       /
                  ▼     ▼      ▼
                 +----------------+
                 |     Redis      |
                 |    Sessions    |
                 +----------------+
                        │
                        ▼
                 +----------------+
                 |   Keycloak     |
                 +----------------+
```

This architecture is common in enterprise environments because it combines:

* horizontal scalability through shared session storage
* centralized authentication logic
* strong protection of OAuth tokens
* simpler frontend code

The operational cost of adding Redis is usually small compared to the security and maintainability benefits, which is why it's a popular choice for BFF-based systems.

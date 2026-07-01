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

I'd like to implement a SSO with Keycloak as a full stack developer. Guide me through the process with a mental model. When I get the access token from the IdP, can I use it for the frontend -> backend REST API? How do I verify the access token is valid on backend side ? Should I cache it or verify it with IDP every time I receive it ?


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

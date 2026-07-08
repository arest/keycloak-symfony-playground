# ADR 0002: Granular RBAC via Keycloak Client Roles & Groups

**Date:** 2026-07-08

**Status:** In Progress — Phase 1 (Keycloak export) ✅, Phase 2 (Symfony) ✅, Phase 3 (Verification) ⬜

## Context

The Symfony BFF currently authorises access via **Keycloak realm roles** (`USER`,
`ADMIN`) mapped in `permissions.yaml` to application permissions:

```
ROLE_ADMIN → admin, adminPanel.access, user.view, user.manage, user.delete, settings.view, settings.edit
ROLE_USER → user.access, profile.view, profile.edit
```

This works but has several limitations:

1. **No granularity at source** — realm roles are coarse (`ADMIN`, `USER`).
   Fine-grained permissions live only in Symfony's `permissions.yaml`, which is
   disconnected from Keycloak's view of the user.
2. **No hierarchy at source** — permission aggregation is done in PHP config,
   not in the identity provider.
3. **Groups are empty** — Keycloak groups (`"groups": []` in the export) are
   unused, missing a key organisational construct.

## Decision

Migrate from **realm-role-based** to **client-role-based** authorisation for the
`symfony-bff` client, leveraging Keycloak **composite client roles** and
**groups** to model the hierarchy natively.

### Role / Permission Hierarchy

```
┌──────────────────────────────────────────────────────────┐
│                    Keycloak Groups                        │
│                                                          │
│  marketing-group ───────────────────┐                    │
│  ├─ members: user3                  │                    │
│  └─ realm roles: (none)             │                    │
│     client roles (symfony-bff):     │                    │
│     └─ marketing (composite)        │                    │
│                                     │                    │
│  administration-group ──────────────┤                    │
│  ├─ members: admin2                 │                    │
│  └─ realm roles: (none)             │                    │
│     client roles (symfony-bff):     │                    │
│     └─ administrator (composite)    │                    │
└──────────┬──────────────────────────┘                    │
           │ assigns via group                              │
           ▼                                                │
┌────────────────────────────────────────────────────┐     │
│            symfony-bff Client Roles                 │     │
│                                                    │     │
│  ┌────────────────────────────────────────────┐    │     │
│  │ administrator (COMPOSITE)                  │    │     │
│  │  ├─ admin-panel-access                     │    │     │
│  │  ├─ user-create                            │    │     │
│  │  ├─ user-edit                              │    │     │
│  │  ├─ user-view                              │    │     │
│  │  ├─ user-delete                            │    │     │
│  │  ├─ settings-view                          │    │     │
│  │  └─ settings-edit                          │    │     │
│  └────────────────────────────────────────────┘    │     │
│                                                    │     │
│  ┌────────────────────────────────────────────┐    │     │
│  │ marketing (COMPOSITE)                      │    │     │
│  │  ├─ admin-panel-access                     │    │     │
│  │  └─ user-view                              │    │     │
│  └────────────────────────────────────────────┘    │     │
│                                                    │     │
│  ┌──────────────────────────────────────┐          │     │
│  │ user-create (LEAF)                   │          │     │
│  │ user-edit   (LEAF)                   │          │     │
│  │ user-view   (LEAF)                   │          │     │
│  │ user-delete (LEAF)                   │          │     │
│  │ settings-view (LEAF)                 │          │     │
│  │ settings-edit (LEAF)                 │          │     │
│  │ admin-panel-access (LEAF)            │          │     │
│  └──────────────────────────────────────┘          │     │
└────────────────────────────────────────────────────┘     │
                                                          │
           JWT claim: resource_access.symfony-bff.roles    │
           ──────────────────────────────────────────►     │
                                                          ▼
                                           ┌────────────────────────────┐
                                           │    Symfony Application      │
                                           │                            │
                                           │ resource_access claim       │
                                           │  → extract client roles     │
                                           │  → map to permissions       │
                                           │  → PermissionVoter checks   │
                                           └────────────────────────────┘
```

### Leaf Roles (fine-grained, non-composite)

Defined on the `symfony-bff` client. These are the atomic permissions:

| Client Role          | Description                    | Maps to (Symfony Permission) |
|----------------------|--------------------------------|------------------------------|
| `admin-panel-access` | Access the `/admin` panel       | `adminPanel.access`          |
| `user-view`          | View user list / details       | `user.view`                  |
| `user-create`        | Create new users               | `user.manage` (creates)      |
| `user-edit`          | Edit existing users            | `user.manage` (edits)        |
| `user-delete`        | Remove users                   | `user.delete`                |
| `settings-view`      | View application settings      | `settings.view`              |
| `settings-edit`      | Edit application settings      | `settings.edit`              |

> **Note:** `user-create` and `user-edit` are kept as separate Keycloak roles
> for audit granularity. They both map to the `user.manage` permission in
> Symfony. If future requirements need to distinguish them (e.g. a support
> role that can edit but not create), the Symfony side can split them.

### Composite Roles (aggregate)

| Composite Role     | Comprises (Leaf Roles)                                            |
|--------------------|-------------------------------------------------------------------|
| `administrator`    | `admin-panel-access`, `user-create`, `user-edit`, `user-view`, `user-delete`, `settings-view`, `settings-edit` |
| `marketing`        | `admin-panel-access`, `user-view`                                 |

Composite roles are never assigned directly to users — they are assigned via
groups (see below).

### Legacy Roles (kept for backwards compatibility)

| Client Role  | Description                        | Notes                               |
|--------------|-----------------------------------|-------------------------------------|
| `user-view`  | View user details (existing)       | Already defined, reused as leaf      |
| `user-manage`| Create and update users (existing) | Superseded by `user-create` + `user-edit`. Kept in export for existing sessions. |

### Groups

Groups are the organisational construct that **assign composite roles** to users.

| Group                 | Assigned Client Role (symfony-bff) | Intended Members     |
|-----------------------|-------------------------------------|----------------------|
| `/Marketing`          | `marketing` (composite)             | user3                |
| `/Administration`     | `administrator` (composite)         | admin2               |

A user who belongs to `/Marketing` inherits the `marketing` composite role,
which unfolds to `admin-panel-access` + `user-view`.

### Token Shape

After these changes, the JWT `resource_access` claim for a marketing user looks
like:

```json
{
  "resource_access": {
    "symfony-bff": {
      "roles": [
        "admin-panel-access",
        "user-view"
      ]
    }
  }
}
```

For an administrator:

```json
{
  "resource_access": {
    "symfony-bff": {
      "roles": [
        "admin-panel-access",
        "user-create",
        "user-edit",
        "user-view",
        "user-delete",
        "settings-view",
        "settings-edit"
      ]
    }
  }
}
```

The existing `roles` client scope mappers (`oidc-usermodel-client-role-mapper`)
already emit `resource_access.${client_id}.roles`. No token mapper changes are
needed.

## Implementation Plan

### Phase 1: Keycloak Realm Export

1. **Add leaf roles** to `symfony-bff` client roles: `user-create`, `user-edit`.
2. **Add composite roles**: `administrator`, `marketing`.
3. **Add groups**: `/Marketing` → `marketing` role, `/Administration` → `administrator` role.
4. **Update users** to belong to the appropriate groups and remove direct
   `clientRoles` assignments where superseded.

### Phase 2: Symfony Changes

1. **Update `UserProvider::createOrUpdateFromKeycloak()`** to extract **client
   roles** from `resource_access.symfony-bff.roles` in addition to (or instead
   of) realm roles.
2. **Update `UserManager::mapRoles()`** to handle the new client role names.
3. **Update `permissions.yaml`** to map the new Symfony roles to permissions.
4. **Optionally add admin2 / user3 fixtures** for the new groups.

### Phase 3: Verification

1. Re-import realm, log in as each test user.
2. Verify the JWT `resource_access` claim contains the expected flat list of
   leaf roles (composite roles are expanded by Keycloak).
3. Confirm `PermissionVoter` grants/denies as expected.

## Consequences

### Positive

- **Permission truth moves to Keycloak.** Group membership determines access,
  not PHP config alone.
- **Groups map to organisational structure.** Adding a new marketing hire means
  adding them to the `/Marketing` group — no code changes.
- **Audit-ready.** Fine-grained roles (`user-create`, `user-delete`) appear
  directly in the JWT.
- **Realm roles become optional.** The `USER`/`ADMIN` realm roles can remain
  for backward compatibility or be phased out.

### Negative

- **Symfony now reads client roles** instead of realm roles. Requires updating
  the token parsing in `UserProvider`.
- **Export becomes more complex** with composite roles and groups.
- **Existing sessions** with old JWTs will lack the new roles until the token
  is refreshed.

### Risks

- **Token size.** Composite roles expand to all leaf roles in the JWT — this is
  desirable (the app sees flat permissions). No risk.
- **Group-to-role assignment** in Keycloak requires the group to explicitly map
  to client roles, not realm roles. The export JSON must use the correct
  structure under `groups[].realmRoles` / `groups[].clientRoles`.

## Keycloak Export Structure

The updated `realm-export.json` changes are in three areas:

1. **`roles.client["symfony-bff"]`** — add new leaf + composite roles.
2. **`groups`** — add `/Marketing` and `/Administration` with client role mappings.
3. **`users`** — update admin2, add user3 with group memberships.

See the `realm-export.json` alongside this ADR for the concrete diff.

## Appendix: Alternative Considered

### Realm Role Composites

Instead of client roles, we could create composite realm roles
(`REALM_ADMINISTRATOR`, `REALM_MARKETING`). This was rejected because:

- Realm roles pollute the global namespace (shared across all clients).
- Client roles are scoped to `symfony-bff` and don't leak to other apps in the realm.
- The existing `oidc-usermodel-client-role-mapper` already handles client roles.

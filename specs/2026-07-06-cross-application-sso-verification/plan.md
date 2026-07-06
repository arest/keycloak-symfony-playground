# Plan: Cross-Application SSO Verification

## Group 1: Second Application Selection & Research

- [x] Evaluate lightweight web apps with native Keycloak/OIDC support (Vikunja, Grafana, Nocodb, etc.)
- [x] Confirm Vikunja supports OIDC via Keycloak realm — confirmed; uses Authorization Code Flow, OIDC Discovery
- [x] Determine Vikunja's resource footprint and Docker image compatibility with existing stack
  - Image: `vikunja/vikunja` (~35–47 MB compressed, Go binary with bundled frontend)
  - Single image (no separate frontend container)
  - RAM: no official minimums; personal/demo instance runs comfortably on 128–256 MB
  - Container user: `1000` by default
  - Compatible with existing `internal` docker network and Postgres service
- [x] Decide on data store for Vikunja — **PostgreSQL** on the existing `postgres` service (new `vikunja` DB + user via init script)
- [x] Choose exposed port for Vikunja
  - Internal: 3456 (Vikunja default)
  - External: 9030 (conflict-free)
  - **Must set `VIKUNJA_SERVICE_PUBLICURL=http://localhost:9030`** — required for CORS + OIDC redirects when using non-default port
- [x] Document selection rationale in requirements.md

## Group 2: Docker Compose Service Setup

- [x] Add Vikunja API service to `compose.yml`:
  - Image: `vikunja/vikunja`
  - Ports: `9030:3456` (internal port 3456 → host port 9030)
  - Networks: `internal`
  - Depends on: `postgres` (condition: service_healthy)
  - Volumes: `vikunja-files:/app/vikunja/files`
  - Environment: `VIKUNJA_SERVICE_PUBLICURL=http://localhost:9030` **required**
- [x] Add PostgreSQL init script at `docker/postgres/init/02-vikunja-db.sql` to create `vikunja` DB + user
- [x] Mount the init directory in the `postgres` service: `./docker/postgres/init:/docker-entrypoint-initdb.d`
- [x] Configure Vikunja environment variables for database:
  - `VIKUNJA_DATABASE_TYPE=postgres`
  - `VIKUNJA_DATABASE_HOST=postgres`
  - `VIKUNJA_DATABASE_PORT=5432`
  - `VIKUNJA_DATABASE_DATABASE=vikunja`
  - `VIKUNJA_DATABASE_USER=vikunja`
  - `VIKUNJA_DATABASE_PASSWORD=vikunja`
  - `VIKUNJA_DATABASE_SSLMODE=disable`
- [x] Configure Vikunja environment variables for OIDC:
  - `VIKUNJA_AUTH_OPENID_ENABLED=true`
  - `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_NAME=Keycloak`
  - `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_AUTHURL=http://keycloak:8080/realms/playground`
  - `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_LOGOUTURL=http://keycloak:8080/realms/playground/protocol/openid-connect/logout`
  - `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_CLIENTID=vikunja`
  - `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_CLIENTSECRET=vikunja-secret`
  - `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_SCOPE=openid profile email`
- [x] Add named volume `vikunja-files` for file uploads
- [x] Verify `docker compose config` parses all 6 services without errors

## Group 3: Keycloak Realm Client Configuration

- [x] Add `vikunja` confidential client to `docker/keycloak/realm-export.json`
  - Client type: `OpenID Connect`
  - Client authentication: `On` (confidential)
  - Root URL: `http://localhost:9030`
  - Valid redirect URIs: `/auth/openid/keycloak` — **note:** Vikunja uses `/auth/openid/<provider-id>` format, where `provider-id` is the key under `auth.openid.providers` in config
  - Valid post-logout redirect URIs: `+` (same as redirect URIs)
- [x] Configure scopes: `openid`, `email`, `profile` (via default client scopes)
- [x] Assign appropriate client roles if Vikunja uses role-based access — No custom client roles needed; Vikunja handles authorization internally via OIDC claims
- [x] Validate updated realm-export.json with `jq` — valid JSON
- [x] Create `vikunja` client via Keycloak admin API (realm data persisted in Postgres; `--import-realm` only runs on first startup)
- [x] Verify client appears in admin console with correct settings (secret, redirect URIs, scopes)

**Note:** `KC_HOSTNAME` was removed from Keycloak config to allow dynamic hostname resolution. Vikunja uses `host.docker.internal:8081` as the OIDC authurl so the issuer matches both internally (container) and externally (browser). Added `127.0.0.1 host.docker.internal` to `/etc/hosts` for browser-side resolution.

## Group 4: Custom Keycloak Mapper (Vikunja Teams via ID Token)

### 4.1 Background & Reference

- [x] Vikunja supports dynamic team assignment via OIDC by reading a `vikunja_groups` claim from the **ID token**
  - Expected claim structure:
    ```json
    {
      "vikunja_groups": [
        {"name": "team-name", "oidcID": "<uuid>", "isPublic": true, "description": "..."}
      ]
    }
    ```
  - `name` = display name in Vikunja; `oidcID` = unique persistent identifier (max 250 chars)
  - `isPublic` and `description` are optional (defaults to `false` and empty)
- [x] Reference implementation: [makerspace-darmstadt/keycloak-vikunja-mapper](https://github.com/makerspace-darmstadt/keycloak-vikunja-mapper)
  - JavaScript-based Keycloak script provider that maps **client roles** → `vikunja_groups` claim
  - Uses client role name as team name, client role UUID as `oidcID`
  - Role attributes `isPublic` and `description` are mapped to the corresponding claim fields
  - Packaged as a `.jar` containing the script + `META-INF/keycloak-scripts.json` descriptor
  - Requires the `scripts` Keycloak feature to be enabled (preview feature in Keycloak 26.x)

### 4.2 Create the Mapper Script

- [x] Create the mapper directory structure:
  ```
  docker/keycloak/mappers/vikunja-team-mapper/
  ├── vikunja-mapper.js
  └── META-INF/
      └── keycloak-scripts.json
  ```
- [x] Write `vikunja-mapper.js` — adapted from reference:
  ```javascript
  // Import Java native types
  ArrayList = Java.type("java.util.ArrayList");
  HashMap = Java.type("java.util.HashMap");

  // Get client we're operating on
  var client = keycloakSession.getContext().getClient();

  // Create group list
  var list = new ArrayList();

  // Iterate through all client roles available for this client
  client.getRolesStream().forEach(function (roleModel) {

      // If the user has this role, either directly or indirectly, add it to the list
      if (user.hasRole(roleModel)) {

          // Create a hash map for this role
          var role_map = new HashMap();
          role_map.put("oidcID", roleModel.getId());
          role_map.put("name", roleModel.getName());

          // Extract attributes from the role
          var attributes = roleModel.getAttributes();

          // If isPublic or description is set, add it to the map
          if (attributes.containsKey("isPublic")) {
              role_map.put("isPublic", roleModel.getFirstAttribute("isPublic") === "true");
          }
          if (attributes.containsKey("description")) {
              role_map.put("description", roleModel.getFirstAttribute("description"));
          }

          // Add it to the list
          list.add(role_map);
      }

  });

  // Return the list
  exports = list;
  ```
- [x] Write `META-INF/keycloak-scripts.json`:
  ```json
  {
      "mappers": [
          {
              "name": "Vikunja Team Mapping",
              "fileName": "vikunja-mapper.js",
              "description": "Maps Client roles to Vikunja Teams according to the required format."
          }
      ]
  }
  ```
- [x] Add a build/packaging script `docker/keycloak/mappers/build-mapper.sh`:
  ```bash
  #!/bin/bash
  cd "$(dirname "$0")"
  jar_file="vikunja-team-mapper.jar"
  rm -f "$jar_file"
  cd vikunja-team-mapper
  zip -vr "../$jar_file" vikunja-mapper.js META-INF/
  echo "Created $jar_file"
  ```

### 4.3 Build Custom Keycloak Image with Mapper

- [x] Create `docker/keycloak/Dockerfile` — custom build that includes the mapper JAR and enables the `scripts` feature:
  ```dockerfile
  FROM quay.io/keycloak/keycloak:26.1 as builder

  # Install custom mapper provider
  COPY mappers/vikunja-team-mapper.jar /opt/keycloak/providers/vikunja-team-mapper.jar

  # Install custom provider and enable scripts feature
  RUN /opt/keycloak/bin/kc.sh build --features="scripts"

  FROM quay.io/keycloak/keycloak:26.1
  COPY --from=builder /opt/keycloak/ /opt/keycloak/

  WORKDIR /opt/keycloak

  ENTRYPOINT ["/opt/keycloak/bin/kc.sh"]
  ```
- [x] Update `compose.yml` to use the custom image instead of `quay.io/keycloak/keycloak:26.1`:
  - Change `image:` to `build: ./docker/keycloak`
  - Keep `command: start-dev --import-realm`
  - Remove or comment out the `image:` line
- [x] Verify `docker compose build keycloak` succeeds

### 4.4 Configure the Realm for Vikunja Teams

- [x] Create a realm-level **Optional** client scope named `vikunja_scope` in `realm-export.json`:
  - Name: `vikunja_scope`
  - Type: `Optional`
  - Protocol: `openid-connect`
  - Include in token scope: `true`
  - Guessing auth note: `false`
- [x] Add a protocol mapper to the `vikunja_scope` client scope:
  - Name: `vikunja_groups`
  - Mapper type: `script-vikunja-mapper.js` (the custom script mapper)
  - Token claim name: `vikunja_groups`
  - Claim JSON type: `JSON`
  - Multivalued: `true`
  - Add to ID token: `true`
  - Add to access token: `false`
  - Add to userinfo: `false`
- [x] Add the `vikunja_scope` optional client scope to the `vikunja` client's scope list
- [x] Create **client roles** on the `vikunja` client for each Vikunja team, e.g.:
  - `general` (default team for general access)
  - `admin` (admin team)
  - (Add more as needed; role name = Vikunja team name)
- [x] Assign client roles to users:
  - `user1` → `vikunja/general` role
  - `admin1` → `vikunja/general` + `vikunja/admin` roles

### 4.5 Verify Mapper Configuration

- [x] Use the Keycloak admin console **Evaluate** tab:
  1. Go to `vikunja_scope` client scope → Mappers → Evaluate
  2. Select user `user1` with `vikunja_scope` scope
  3. Verify the generated ID token contains:
     ```json
     {
       "vikunja_groups": [
         {"name": "general", "oidcID": "<uuid>"}
       ]
     }
     ```
- [x] Verified via API: **user1** with `vikunja_scope` → `vikunja_groups: [{name: "general", oidcID: "<uuid>"}]`
- [x] Verified via API: **admin1** with `vikunja_scope` → `vikunja_groups: [{name: "admin"}, {name: "general"}]`
- [x] Verified via API: **user1** without `vikunja_scope` → no `vikunja_groups` claim (scope-gated correctly)
- [ ] Document the manual testing procedure in `validation.md`

**Note:** The script mapper JAR needs `KC_FEATURES=scripts` env var (not just `kc.sh build --features=scripts`) because `start-dev` mode auto-builds and ignores the pre-built optimized image. The JAR is owned root:root which is fine (world-readable).

## Group 5: Integration & Wiring (Vikunja Configuration)

- [ ] Vikunja uses a `config.yaml` file for persistent config, **OR** environment variables with `VIKUNJA_` prefix
  - YAML equivalent of env vars above:
    ```yaml
    auth:
      openid:
        enabled: true
        providers:
          keycloak:
            name: Keycloak
            authurl: http://keycloak:8080/realms/playground
            logouturl: http://keycloak:8080/realms/playground/protocol/openid-connect/logout
            clientid: vikunja
            clientsecret: <secret>
            scope: openid profile email vikunja_scope
    ```
- [ ] **Update Vikunja's OIDC scope** — add `vikunja_scope` to the scope list in `compose.yml`:
  - Change `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_SCOPE: openid profile email`
  - To: `VIKUNJA_AUTH_OPENID_PROVIDERS_KEYCLOAK_SCOPE: openid profile email vikunja_scope`
- [ ] **Note:** Vikunja uses OIDC Discovery (fetches `/.well-known/openid-configuration` from `authurl`) to auto-detect endpoints. No manual endpoint URLs needed.
- [ ] Remove or disable Vikunja's local registration if desired (`auth.local.enabled: false`)
- [ ] Verify claim mapping from Keycloak ID token:
  - `email` (required) — mapped from Keycloak email claim
  - `name` (optional) — mapped from Keycloak display name
  - `preferred_username` (optional) — mapped from Keycloak preferred_username
  - `vikunja_groups` — mapped by custom script mapper (enables team assignment)
- [ ] Test Vikunja login via Keycloak — verify user is redirected to Keycloak login page
- [ ] Verify successful callback and Vikunja dashboard access
- [ ] Verify the user is automatically added to the correct Vikunja teams based on their client role assignments
- [ ] Verify team membership in Vikunja admin/settings interface

## Group 6: SSO Verification

- [ ] **Login via Symfony BFF** — authenticate `user1` through http://localhost:8080/login
- [ ] **Navigate to Vikunja** — open http://localhost:9030 in same browser; verify no re-authentication prompt
- [ ] **Verify user context** — confirm same user (`user1`) is logged into both apps
- [ ] **Verify team membership** — confirm `user1` has the `general` team assigned in Vikunja
- [ ] **Global logout from Symfony** — logout via Symfony `/logout`; verify Vikunja also requires re-authentication
- [ ] **Login via Vikunja first** — authenticate `admin1` through Vikunja; then navigate to Symfony BFF; verify no re-prompt
- [ ] **Verify admin team membership** — confirm `admin1` has both `general` and `admin` teams in Vikunja
- [ ] **Global logout from Vikunja** — logout from Vikunja; verify Symfony BFF also requires re-authentication
- [ ] **Test with different browser/incognito** — confirm no cross-contamination of sessions
- [ ] **Test role revocation** — remove `admin1` from `vikunja/admin` role; verify the next login updates their teams
- [ ] Document all verification results in `validation.md`

## Group 7: Documentation

- [ ] Update architecture diagram in `README.md` (ASCII or Mermaid) to show the second app
- [ ] Add SSO sequence diagram showing the full flow: login → app A → app B (already authenticated)
- [ ] Add global logout sequence diagram showing logout from one app invalidating session across all apps
- [ ] Document Vikunja's OIDC configuration as a reference for adding future apps
- [ ] Document custom mapper setup process as a reference for adding future custom mappers
- [ ] Update curl examples with any new relevant endpoints
- [ ] Document the SSO verification results in the README
- [ ] Document how to create new Vikunja teams and assign roles via Keycloak admin console

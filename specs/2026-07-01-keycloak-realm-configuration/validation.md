# Validation: Keycloak Realm Configuration

## Acceptance Criteria

- [ ] **Keycloak starts without manual config** — `docker compose up -d keycloak` (with postgres healthy) results in a running Keycloak at `http://localhost:8081`
- [ ] **Realm imported automatically** — `docker compose logs keycloak` shows a log line confirming realm `playground` was imported (e.g., "Realm 'playground' imported")
- [ ] **OIDC discovery responds** — `curl http://localhost:8081/realms/playground/.well-known/openid-configuration` returns valid JSON with `issuer`, `authorization_endpoint`, `token_endpoint` fields
- [ ] **symfony-bff client exists** — `curl http://localhost:8081/realms/playground/.well-known/openid-configuration | jq` — or verify via admin console — that the `symfony-bff` client is registered
- [ ] **Realm roles exist** — Admin console shows `USER` and `ADMIN` roles under Realm Roles
- [ ] **Test users exist** — Admin console shows `user1` and `admin1` users with correct role assignments
- [ ] **User authentication works** — A token request with `user1` credentials succeeds (manual curl or admin console test)
- [ ] **Realm export JSON is valid** — `cat docker/keycloak/realm-export.json | python3 -m json.tool` or `jq .` succeeds without errors
- [ ] **Clean-clone reproducibility** — From a fresh `git clone`, `docker compose up` should produce the same Keycloak state without any admin UI steps

## Testing

### Automated validation
```bash
# Validate JSON
jq . docker/keycloak/realm-export.json > /dev/null && echo "JSON valid"

# Verify OIDC endpoints (after compose up)
curl -s http://localhost:8081/realms/playground/.well-known/openid-configuration | jq '.issuer'
# Expected: "http://localhost:8081/realms/playground"

# Check import in logs
docker compose logs keycloak | grep -i "realm.*import"
```

### Manual validation
1. Open `http://localhost:8081/admin` in browser
2. Log in with the admin credentials set in `docker-compose.yml`
3. Verify `playground` realm exists in the dropdown
4. Navigate to **Clients** → confirm `symfony-bff` is listed with correct redirect URIs
5. Navigate to **Realm roles** → confirm `USER` and `ADMIN` exist
6. Navigate to **Users** → confirm `user1` (role: USER) and `admin1` (roles: USER, ADMIN) exist
7. Test password login for both users via the admin console "Impersonate" or "Test" functionality

## Merge Conditions

- [ ] All acceptance criteria met
- [ ] `realm-export.json` is valid JSON
- [ ] No manual admin UI steps appear in the final instructions
- [ ] Code reviewed (at minimum, self-review of JSON structure)
- [ ] README updated (or note added for Phase 7 to document Keycloak admin URL and credentials)
- [ ] Commit message follows Conventional Commits format (e.g., `feat: add keycloak realm export for playground realm`)
- [ ] No regressions in previously working Phase 1 infrastructure

# Validation: Docker Scaffold

## Acceptance Criteria

- [ ] `docker compose config` validates without errors
- [ ] `docker compose up --build` starts all 4 containers without errors
- [ ] `postgres` container is healthy (`pg_isready` check passes)
- [ ] `keycloak` container is healthy (Keycloak starts, admin console reachable at `http://localhost:8081`)
- [ ] `php` container starts and PHP-FPM is listening on port 9000
- [ ] `nginx` container is healthy and responds on `http://localhost:8080` (returns valid HTTP response, even if 502 before Symfony is ready)
- [ ] All required PHP extensions are installed (verify via `php -m` inside the PHP container):
  - `pdo_pgsql`, `intl`, `mbstring`, `xml`, `curl`, `opcache`, `zip`
- [ ] `git` and `unzip` are available in the PHP container
- [ ] Composer is installed and executable in the PHP container
- [ ] Services can resolve each other by container name:
  - `docker compose exec php ping -c 1 postgres` (succeeds)
  - `docker compose exec php ping -c 1 keycloak` (succeeds)
  - `docker compose exec nginx ping -c 1 php` (succeeds)
- [ ] Nginx reverse-proxies to PHP-FPM correctly:
  - Request to `php:9000` from nginx container returns valid FastCGI response
- [ ] `docker compose down -v` shuts down cleanly and removes the volume
- [ ] `docker compose up` (no `--build`) reuses cached images without rebuilding

## Testing

### Automated validation (run these commands)

```bash
# Validate compose file
docker compose config

# Build and start all services
docker compose up --build -d

# Check all containers are running
docker compose ps

# Check PHP extensions
docker compose exec php php -m

# Check system packages
docker compose exec php which git unzip composer

# Check inter-service connectivity
docker compose exec php sh -c "apk add --no-cache iputils && ping -c 1 postgres"
docker compose exec php sh -c "ping -c 1 keycloak"
docker compose exec nginx sh -c "ping -c 1 php"

# Check HTTP response from nginx
curl -v http://localhost:8080

# Check Keycloak admin console
curl -v http://localhost:8081

# Clean up
docker compose down -v
```

### Manual checks
- Verify `localhost:8081` shows the Keycloak admin login page
- Verify `localhost:8080` returns a non-connection-refused response from nginx

## Merge Conditions

- [ ] All acceptance criteria above are met
- [ ] `docker compose up` works from a clean state (no cached images or volumes)
- [ ] All 4 containers start without warnings or errors in logs
- [ ] Configuration files are linted/correct (YAML, Nginx config syntax)
- [ ] The `docker-compose.yml` is committed along with all Docker config files
- [ ] No hardcoded absolute paths in any configuration file
- [ ] Commit is on `feature/docker-scaffold` branch (ready for PR to `main`)

-- Create the Vikunja database and user on the shared PostgreSQL instance.
-- This runs once during initial Postgres container startup.
-- The `keycloak` database is already created by the POSTGRES_DB env var in compose.yml.

CREATE DATABASE vikunja;
CREATE USER vikunja WITH PASSWORD 'vikunja';
GRANT ALL PRIVILEGES ON DATABASE vikunja TO vikunja;

-- Connect to the vikunja database to grant schema-level permissions
\c vikunja
GRANT ALL ON SCHEMA public TO vikunja;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO vikunja;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO vikunja;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO vikunja;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO vikunja;

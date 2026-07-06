# Plan: Keycloak Login Theming

> Based on: compose.yml (not docker-compose.yml), realm-export.json, specs/tech-stack.md
> Node.js v24.5.0, npm 11.5.1

## Group 1: Scaffold Keycloakify Theme Project

- [ ] Create `keycloak-theme/` directory at repo root
- [ ] Initialize with `npm create vite@latest keycloak-theme -- --template react-ts` (non-interactive)
- [ ] Add dependencies: `keycloakify`, `@keycloakify/login-ui`
- [ ] Run `npx keycloakify init` to generate Keycloakify config files
- [ ] Add Keycloakify Vite plugin to `vite.config.ts`
- [ ] Verify `npm run build-keycloak-theme` runs without errors
- [ ] Add repo-root `.gitignore` entries: `keycloak-theme/node_modules`, `keycloak-theme/dist_keycloak/`
- [ ] Commit scaffold

## Group 2: Add Storybook Stories

- [ ] Run `npx keycloakify add-story` — select `login.ftl`
- [ ] Run `npx keycloakify add-story` — select `register.ftl`
- [ ] Run `npx keycloakify add-story` — select `login-reset-password.ftl` (forgot password flow)
- [ ] Run `npm run storybook` and verify all pages render
- [ ] Commit storybook setup

## Group 3: Customize Login Pages (CSS)

- [ ] Create `src/main.css` (or overwrite Vite's default `src/index.css`) with custom theme styles
- [ ] Style the login form (card, inputs, button)
- [ ] Style the register form (matching design)
- [ ] Style the forgot-password form (matching design)
- [ ] Style error/info/success message banners
- [ ] Verify with Storybook — all variants look correct
- [ ] Commit CSS customizations

## Group 4: Optional — Component-Level Customization

- [ ] Run `npx keycloakify eject-page` for pages requiring deeper customisation
- [ ] Modify ejected components as needed (e.g., add logo, change layout)
- [ ] Verify with Storybook
- [ ] Commit ejected components

## Group 5: Build & Integrate with compose.yml

- [ ] Run `npm run build-keycloak-theme` — confirm JAR is created in `dist_keycloak/`
- [ ] Update `compose.yml` — add volume mount to `keycloak` service:
      ```yaml
      volumes:
        - ./keycloak-theme/dist_keycloak:/opt/keycloak/providers
      ```
- [ ] Add theme to `docker/keycloak/realm-export.json` — set `loginTheme` and `registerTheme` on the realm (avoids clicking through admin UI)
- [ ] Restart stack with `docker compose up -d` (no `--build` needed — only volumes changed)
- [ ] Verify Keycloak admin console shows the custom theme in realm settings → Realm `playground` → Themes
- [ ] Confirm theme is already active via realm-export (no manual activation needed)
- [ ] Commit compose.yml and realm-export.json changes

## Group 6: End-to-End Verification

- [ ] Open login page — confirm custom theme is displayed
- [ ] Open register page — confirm custom theme
- [ ] Log in as `user1` — verify redirect back to SPA still works
- [ ] Verify `/api/me` and `/api/protected` still return correct data
- [ ] Test logout — confirm Keycloak logout page uses custom theme
- [ ] Add a `## Keycloak Theme` section to `README.md` covering:
      - `keycloak-theme/` project structure
      - Development workflow (Storybook for UI, `npm run build-keycloak-theme` for JAR)
      - How to rebuild and reload the theme
- [ ] Final commit

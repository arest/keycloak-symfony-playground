# Plan: Next.js SPA

## Group 1: Scaffold
- [x] Create Next.js app with TypeScript in project root (`nextjs-app/`)
- [x] Add `next.config.ts` with API proxy to Symfony (localhost:8080)
- [x] Verify dev server starts on port 3000

## Group 2: Login Page
- [x] Create Login page at `/login` with a link/button to Symfony `/login`
- [x] Add a "logged in" check via `/api/me` to redirect to Profile if authenticated
- [x] Handle post-login redirect back to SPA

## Group 3: Profile Page
- [x] Create Profile page at `/profile`
- [x] Fetch `/api/me` from Symfony on mount
- [x] Display user info (username, email, roles)
- [x] Handle unauthenticated state (redirect to Login)

## Group 4: Protected Page (ADMIN)
- [x] Create Protected page at `/protected`
- [x] Fetch `/api/protected` from Symfony on mount
- [x] Display response or error message
- [x] Handle 403/unauthorized gracefully

## Group 5: Navigation & Layout
- [x] Create a shared layout with nav bar
- [x] Add navigation links: Home (Login), Profile, Protected
- [x] Add Logout button linking to Symfony `/logout` (redirects to localhost:3000 via Symfony config)
- [x] Handle post-logout redirect back to SPA

## Group 6: End-to-End Verification
- [x] Start full stack (Docker Compose + Next.js dev server)
- [x] Verify login redirects to Keycloak and back
- [x] Verify Profile page shows user data
- [x] Verify Protected page works for admin1, returns error for user1
- [x] Verify logout clears session

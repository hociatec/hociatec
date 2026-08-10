# E2E coverage

This suite is meant to harden the beta with stable route and auth coverage across:

- public pages
- authenticated client pages
- admin pages

## Stable accounts

Seed the backend e2e data before running Playwright:

```bash
cd /home/ubuntu/hociatec/backend
php bin/console app:e2e:seed
```

Default seeded accounts:

- client: `e2e.client@hociatec.local`
- admin: `e2e.admin@hociatec.local`
- password: `E2ePassword123`

You can override them for a dedicated environment with:

```bash
export PLAYWRIGHT_E2E_CLIENT_EMAIL='...'
export PLAYWRIGHT_E2E_ADMIN_EMAIL='...'
export PLAYWRIGHT_E2E_PASSWORD='...'
```

## Run

```bash
cd /home/ubuntu/hociatec/frontend
npm run test:e2e:coverage
```

The setup project creates authenticated storage states in `frontend/e2e/.auth/`.

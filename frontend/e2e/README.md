# E2E coverage

This suite is meant to harden the beta with stable route and auth coverage across:

- public pages
- authenticated client pages
- admin pages

## Stable accounts

Seed the backend e2e data before running Playwright:

```bash
cd /home/ubuntu/hociatec/backend
APP_E2E=1 php bin/console app:e2e:purge
APP_E2E=1 php bin/console app:e2e:seed
```

Both commands are blocked in `prod`, and also blocked unless `APP_E2E=1` or `APP_ENV=e2e` is set.
Keep them on a local or dedicated non-production environment.

Default seeded accounts:

- client: `e2e.client@hociatec.local`
- admin: `e2e.admin@hociatec.local`
- password: `E2ePassword123`

You can override them for a dedicated environment with:

```bash
export PLAYWRIGHT_E2E_ALLOWED='1'
export PLAYWRIGHT_E2E_CLIENT_EMAIL='...'
export PLAYWRIGHT_E2E_ADMIN_EMAIL='...'
export PLAYWRIGHT_E2E_PASSWORD='...'
```

## Run

```bash
cd /home/ubuntu/hociatec/frontend
export PLAYWRIGHT_E2E_ALLOWED='1'
npm run test:e2e:coverage
```

The suite is blocked unless `PLAYWRIGHT_E2E_ALLOWED=1` is set explicitly.
The setup project auto-purges and auto-seeds only when Playwright targets `localhost` or `127.0.0.1`.
For any remote `PLAYWRIGHT_BASE_URL`, provision and clean a dedicated environment manually instead of seeding from the test runner.

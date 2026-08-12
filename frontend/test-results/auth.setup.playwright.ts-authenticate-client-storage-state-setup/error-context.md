# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: auth.setup.playwright.ts >> authenticate client storage state
- Location: e2e/auth.setup.playwright.ts:26:1

# Error details

```
Error: E2E login failed for e2e.client@hociatec.local with status 401. Run `APP_E2E=1 php bin/console app:e2e:purge && APP_E2E=1 php bin/console app:e2e:seed` in /home/ubuntu/hociatec/backend before running Playwright.
```

# Test source

```ts
  1   | import { execFileSync } from 'node:child_process';
  2   | import { mkdirSync } from 'node:fs';
  3   | import { dirname, resolve } from 'node:path';
  4   | import { fileURLToPath } from 'node:url';
  5   | import type { APIRequestContext } from '@playwright/test';
  6   | 
  7   | const helperDir = dirname(fileURLToPath(import.meta.url));
  8   | const backendDir = resolve(helperDir, '../../../backend');
  9   | 
  10  | export const CLIENT_STORAGE_STATE = resolve(helperDir, '../.auth/client.json');
  11  | export const ADMIN_STORAGE_STATE = resolve(helperDir, '../.auth/admin.json');
  12  | 
  13  | const DEFAULT_CLIENT_EMAIL = 'e2e.client@hociatec.local';
  14  | const DEFAULT_ADMIN_EMAIL = 'e2e.admin@hociatec.local';
  15  | const DEFAULT_PASSWORD = 'E2ePassword123';
  16  | const DEFAULT_BACKEND_ENV = 'dev';
  17  | const E2E_ALLOWED_FLAG = '1';
  18  | 
  19  | export const E2E_CLIENT_EMAIL =
  20  |   process.env.PLAYWRIGHT_E2E_CLIENT_EMAIL ?? DEFAULT_CLIENT_EMAIL;
  21  | export const E2E_ADMIN_EMAIL =
  22  |   process.env.PLAYWRIGHT_E2E_ADMIN_EMAIL ?? DEFAULT_ADMIN_EMAIL;
  23  | export const E2E_PASSWORD = process.env.PLAYWRIGHT_E2E_PASSWORD ?? DEFAULT_PASSWORD;
  24  | export const E2E_BACKEND_ENV =
  25  |   process.env.PLAYWRIGHT_E2E_BACKEND_ENV ?? DEFAULT_BACKEND_ENV;
  26  | export const PLAYWRIGHT_E2E_ALLOWED = process.env.PLAYWRIGHT_E2E_ALLOWED ?? '0';
  27  | 
  28  | const parseBaseUrl = () => {
  29  |   const value = process.env.PLAYWRIGHT_BASE_URL;
  30  |   if (!value) {
  31  |     return null;
  32  |   }
  33  | 
  34  |   try {
  35  |     return new URL(value);
  36  |   } catch {
  37  |     return null;
  38  |   }
  39  | };
  40  | 
  41  | export const shouldAutoSeedE2eData = () => {
  42  |   const url = parseBaseUrl();
  43  |   if (!url) {
  44  |     return true;
  45  |   }
  46  | 
  47  |   return ['127.0.0.1', 'localhost'].includes(url.hostname);
  48  | };
  49  | 
  50  | export const assertE2eExecutionAllowed = () => {
  51  |   if (PLAYWRIGHT_E2E_ALLOWED !== E2E_ALLOWED_FLAG) {
  52  |     throw new Error(
  53  |       'Playwright E2E is blocked unless PLAYWRIGHT_E2E_ALLOWED=1 is set. ' +
  54  |         'Use a dedicated environment, then rerun the suite explicitly.',
  55  |     );
  56  |   }
  57  | };
  58  | 
  59  | export const ensureStorageStateDirectory = (storageStatePath: string) => {
  60  |   mkdirSync(dirname(storageStatePath), { recursive: true });
  61  | };
  62  | 
  63  | export const seedE2eData = () => {
  64  |   runBackendE2eCommand('app:e2e:seed');
  65  | };
  66  | 
  67  | export const purgeE2eData = () => {
  68  |   runBackendE2eCommand('app:e2e:purge');
  69  | };
  70  | 
  71  | export const loginByApi = async (
  72  |   request: APIRequestContext,
  73  |   {
  74  |     email,
  75  |     password = E2E_PASSWORD,
  76  |     storageStatePath,
  77  |   }: {
  78  |     email: string;
  79  |     password?: string;
  80  |     storageStatePath: string;
  81  |   },
  82  | ) => {
  83  |   ensureStorageStateDirectory(storageStatePath);
  84  | 
  85  |   const response = await request.post('/api/auth/login', {
  86  |     data: { email, password },
  87  |   });
  88  | 
  89  |   if (!response.ok()) {
> 90  |     throw new Error(
      |           ^ Error: E2E login failed for e2e.client@hociatec.local with status 401. Run `APP_E2E=1 php bin/console app:e2e:purge && APP_E2E=1 php bin/console app:e2e:seed` in /home/ubuntu/hociatec/backend before running Playwright.
  91  |       `E2E login failed for ${email} with status ${response.status()}. ` +
  92  |         'Run `APP_E2E=1 php bin/console app:e2e:purge && APP_E2E=1 php bin/console app:e2e:seed` ' +
  93  |         'in /home/ubuntu/hociatec/backend before running Playwright.',
  94  |     );
  95  |   }
  96  | 
  97  |   await request.storageState({ path: storageStatePath });
  98  | };
  99  | 
  100 | const runBackendE2eCommand = (commandName: 'app:e2e:seed' | 'app:e2e:purge') => {
  101 |   execFileSync('php', ['bin/console', commandName, `--env=${E2E_BACKEND_ENV}`], {
  102 |     cwd: backendDir,
  103 |     stdio: 'pipe',
  104 |     env: {
  105 |       ...process.env,
  106 |       APP_E2E: E2E_ALLOWED_FLAG,
  107 |     },
  108 |   });
  109 | };
  110 | 
```
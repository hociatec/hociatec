import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import type { APIRequestContext } from '@playwright/test';

const helperDir = dirname(fileURLToPath(import.meta.url));
const backendDir = resolve(helperDir, '../../../backend');

export const CLIENT_STORAGE_STATE = resolve(helperDir, '../.auth/client.json');
export const ADMIN_STORAGE_STATE = resolve(helperDir, '../.auth/admin.json');

const DEFAULT_CLIENT_EMAIL = 'e2e.client@hociatec.local';
const DEFAULT_ADMIN_EMAIL = 'e2e.admin@hociatec.local';
const DEFAULT_PASSWORD = 'E2ePassword123';
const DEFAULT_BACKEND_ENV = 'dev';
const E2E_ALLOWED_FLAG = '1';

export const E2E_CLIENT_EMAIL =
  process.env.PLAYWRIGHT_E2E_CLIENT_EMAIL ?? DEFAULT_CLIENT_EMAIL;
export const E2E_ADMIN_EMAIL =
  process.env.PLAYWRIGHT_E2E_ADMIN_EMAIL ?? DEFAULT_ADMIN_EMAIL;
export const E2E_PASSWORD = process.env.PLAYWRIGHT_E2E_PASSWORD ?? DEFAULT_PASSWORD;
export const E2E_BACKEND_ENV =
  process.env.PLAYWRIGHT_E2E_BACKEND_ENV ?? DEFAULT_BACKEND_ENV;
export const PLAYWRIGHT_E2E_ALLOWED = process.env.PLAYWRIGHT_E2E_ALLOWED ?? '0';

const parseBaseUrl = () => {
  const value = process.env.PLAYWRIGHT_BASE_URL;
  if (!value) {
    return null;
  }

  try {
    return new URL(value);
  } catch {
    return null;
  }
};

export const shouldAutoSeedE2eData = () => {
  const url = parseBaseUrl();
  if (!url) {
    return true;
  }

  return ['127.0.0.1', 'localhost'].includes(url.hostname);
};

export const assertE2eExecutionAllowed = () => {
  if (PLAYWRIGHT_E2E_ALLOWED !== E2E_ALLOWED_FLAG) {
    throw new Error(
      'Playwright E2E is blocked unless PLAYWRIGHT_E2E_ALLOWED=1 is set. ' +
        'Use a dedicated environment, then rerun the suite explicitly.',
    );
  }
};

export const ensureStorageStateDirectory = (storageStatePath: string) => {
  mkdirSync(dirname(storageStatePath), { recursive: true });
};

export const seedE2eData = () => {
  runBackendE2eCommand('app:e2e:seed');
};

export const purgeE2eData = () => {
  runBackendE2eCommand('app:e2e:purge');
};

export const loginByApi = async (
  request: APIRequestContext,
  {
    email,
    password = E2E_PASSWORD,
    storageStatePath,
  }: {
    email: string;
    password?: string;
    storageStatePath: string;
  },
) => {
  ensureStorageStateDirectory(storageStatePath);

  const response = await request.post('/api/auth/login', {
    data: { email, password },
  });

  if (!response.ok()) {
    throw new Error(
      `E2E login failed for ${email} with status ${response.status()}. ` +
        'Run `APP_E2E=1 php bin/console app:e2e:purge && APP_E2E=1 php bin/console app:e2e:seed` ' +
        'in /home/ubuntu/hociatec/backend before running Playwright.',
    );
  }

  await request.storageState({ path: storageStatePath });
};

const runBackendE2eCommand = (commandName: 'app:e2e:seed' | 'app:e2e:purge') => {
  execFileSync('php', ['bin/console', commandName, `--env=${E2E_BACKEND_ENV}`], {
    cwd: backendDir,
    stdio: 'pipe',
    env: {
      ...process.env,
      APP_E2E: E2E_ALLOWED_FLAG,
    },
  });
};

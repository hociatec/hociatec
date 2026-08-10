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

export const E2E_CLIENT_EMAIL =
  process.env.PLAYWRIGHT_E2E_CLIENT_EMAIL ?? DEFAULT_CLIENT_EMAIL;
export const E2E_ADMIN_EMAIL =
  process.env.PLAYWRIGHT_E2E_ADMIN_EMAIL ?? DEFAULT_ADMIN_EMAIL;
export const E2E_PASSWORD = process.env.PLAYWRIGHT_E2E_PASSWORD ?? DEFAULT_PASSWORD;

export const ensureStorageStateDirectory = (storageStatePath: string) => {
  mkdirSync(dirname(storageStatePath), { recursive: true });
};

export const seedE2eData = () => {
  execFileSync('php', ['bin/console', 'app:e2e:seed', '--env=prod'], {
    cwd: backendDir,
    stdio: 'pipe',
  });
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
        'Run `php bin/console app:e2e:seed` in /home/ubuntu/hociatec/backend before running Playwright.',
    );
  }

  await request.storageState({ path: storageStatePath });
};

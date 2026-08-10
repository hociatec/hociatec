import { test as setup } from '@playwright/test';

import {
  ADMIN_STORAGE_STATE,
  CLIENT_STORAGE_STATE,
  E2E_ADMIN_EMAIL,
  E2E_CLIENT_EMAIL,
  loginByApi,
  seedE2eData,
} from './helpers/auth';

setup('seed repeatable e2e data', async () => {
  seedE2eData();
});

setup('authenticate client storage state', async ({ request }) => {
  await loginByApi(request, {
    email: E2E_CLIENT_EMAIL,
    storageStatePath: CLIENT_STORAGE_STATE,
  });
});

setup('authenticate admin storage state', async ({ request }) => {
  await loginByApi(request, {
    email: E2E_ADMIN_EMAIL,
    storageStatePath: ADMIN_STORAGE_STATE,
  });
});

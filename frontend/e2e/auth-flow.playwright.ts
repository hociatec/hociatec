import { expect, test } from '@playwright/test';

import { E2E_CLIENT_EMAIL, E2E_PASSWORD } from './helpers/auth';
import { expectHealthyPage } from './helpers/site';

test('client can sign in from the UI and reach the dashboard', async ({ page }) => {
  await page.goto('/login', { waitUntil: 'networkidle' });
  await page.getByRole('textbox', { name: 'Email' }).fill(E2E_CLIENT_EMAIL);
  await page.getByRole('textbox', { name: 'Mot de passe' }).fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Se connecter' }).click();

  await expect(page).toHaveURL(/\/mon-espace$/);
  await expect(page.locator('main[aria-labelledby="client-dashboard-title"]')).toBeVisible();
  await expect(page.getByRole('heading', { level: 1 })).toContainText(/votre espace en un coup d'oeil/i);
});

test('authenticated user can open the main account entry points cleanly', async ({ page }) => {
  await page.goto('/login', { waitUntil: 'networkidle' });
  await page.getByRole('textbox', { name: 'Email' }).fill(E2E_CLIENT_EMAIL);
  await page.getByRole('textbox', { name: 'Mot de passe' }).fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Se connecter' }).click();

  for (const route of ['/mon-espace', '/profile', '/orders/me', '/quotes/me']) {
    await expectHealthyPage(page, route);
  }
});

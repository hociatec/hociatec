import { expect, test } from '@playwright/test';

test('public home page renders without console errors', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text());
    }
  });

  await page.goto('/');
  await expect(page).toHaveTitle(/Hociatec/i);
  await expect(page.getByRole('banner')).toBeVisible();
  expect(consoleErrors).toEqual([]);
});

test('@visual public home page keeps a stable first viewport', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveScreenshot('home-first-viewport.png', {
    fullPage: false,
    animations: 'disabled',
  });
});

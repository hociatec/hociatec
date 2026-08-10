import { expect, type ConsoleMessage, type Page, type Response } from '@playwright/test';

export const INTERNAL_ERROR_PATTERN =
  /Une erreur interne est survenue|La page n'a pas pu etre affich(?:ee|e|ée) correctement/i;

const isExpectedConsoleError = (message: string) => /ResizeObserver loop/i.test(message);

export const watchRuntimeErrors = (page: Page) => {
  const consoleErrors: string[] = [];
  const pageErrors: string[] = [];
  const apiFailures: string[] = [];

  const onConsole = (message: ConsoleMessage) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text());
    }
  };

  const onPageError = (error: Error) => {
    pageErrors.push(error.message);
  };

  const onResponse = (response: Response) => {
    if (response.url().includes('/api/') && response.status() >= 500) {
      apiFailures.push(`${response.status()} ${response.url()}`);
    }
  };

  page.on('console', onConsole);
  page.on('pageerror', onPageError);
  page.on('response', onResponse);

  return {
    apiFailures,
    consoleErrors,
    pageErrors,
    dispose: () => {
      page.off('console', onConsole);
      page.off('pageerror', onPageError);
      page.off('response', onResponse);
    },
  };
};

export const expectNoUnexpectedErrors = (
  errors: ReturnType<typeof watchRuntimeErrors>,
) => {
  expect(errors.apiFailures).toEqual([]);
  expect(errors.consoleErrors.filter((message) => !isExpectedConsoleError(message))).toEqual([]);
  expect(errors.pageErrors).toEqual([]);
};

export const expectHealthyPage = async (page: Page, route: string) => {
  const errors = watchRuntimeErrors(page);

  try {
    await page.goto(route, { waitUntil: 'networkidle' });
    await expect(page.locator('#root')).toBeAttached();
    await expect(page.locator('.site-header').first()).toBeVisible();
    await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
    expectNoUnexpectedErrors(errors);
  } finally {
    errors.dispose();
  }
};

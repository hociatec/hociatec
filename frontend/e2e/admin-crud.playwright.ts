import { expect, test, type Locator } from '@playwright/test';

import { ADMIN_STORAGE_STATE } from './helpers/auth';

test.use({ storageState: ADMIN_STORAGE_STATE });

const clickAfterScroll = async (locator: Locator) => {
  await locator.scrollIntoViewIfNeeded();
  await locator.evaluate((element: HTMLElement) => element.click());
};

test.describe('admin CRUD workflows', () => {
  test('admin can create, update and delete a catalog brand', async ({ page }) => {
    const brandName = `Marque E2E ${Date.now()}`;
    const updatedBrandName = `${brandName} Mod`;

    await page.goto('/admin/catalog/brands/new', { waitUntil: 'networkidle' });
    await page.getByLabel('Nom').fill(brandName);

    const createResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/admin/catalog/brands') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /^Créer$/i }).click();
    const createPayload = await (await createResponse).json();
    const brandId = Number(createPayload?.data?.id);
    expect(Number.isFinite(brandId)).toBeTruthy();

    await page.waitForURL('**/admin/catalog/brands');
    const searchInput = page.getByPlaceholder('Rechercher une marque...');
    await searchInput.fill(brandName);
    const brandRow = page.locator('tbody tr').filter({ hasText: brandName }).first();
    await expect(brandRow).toBeVisible();

    await page.goto(`/admin/catalog/brands/${brandId}/edit`, { waitUntil: 'networkidle' });
    const nameInput = page.getByLabel('Nom');
    await expect(nameInput).toHaveValue(brandName);
    await nameInput.fill(updatedBrandName);

    const updateResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/catalog/brands/${brandId}`) &&
        response.request().method() === 'PUT' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /Mettre à jour/i }).click();
    await updateResponse;
    await page.waitForURL('**/admin/catalog/brands');

    await searchInput.fill(updatedBrandName);
    const updatedRow = page.locator('tbody tr').filter({ hasText: updatedBrandName }).first();
    await expect(updatedRow).toBeVisible();

    await clickAfterScroll(
      updatedRow.getByRole('button', { name: new RegExp(`Supprimer la marque ${updatedBrandName}`) }),
    );
    const deleteDialog = page.getByRole('alertdialog');
    await expect(deleteDialog).toBeVisible();

    const deleteResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/catalog/brands/${brandId}`) &&
        response.request().method() === 'DELETE' &&
        response.status() < 400,
    );
    await clickAfterScroll(deleteDialog.getByRole('button', { name: /^Supprimer$/i }));
    await deleteResponse;
    await expect(page.locator('tbody tr').filter({ hasText: updatedBrandName })).toHaveCount(0);
  });

  test('admin can create, update and delete an appointment motif', async ({ page }) => {
    const prestationName = `Motif E2E ${Date.now()}`;
    const updatedPrestationName = `${prestationName} Mod`;

    await page.goto('/admin/appointments/motifs/new', { waitUntil: 'networkidle' });
    await page.getByLabel('Nom').fill(prestationName);
    await page.getByLabel('Durée (minutes)').fill('45');
    await page.getByLabel('Prix (EUR)').fill('89');

    const createResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/admin/appointments/prestations') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /^Créer$/i }).click();
    const createPayload = await (await createResponse).json();
    const prestationId = Number(createPayload?.data?.id);
    expect(Number.isFinite(prestationId)).toBeTruthy();

    await page.waitForURL('**/admin/appointments/motifs');
    const prestationRow = page.locator('tbody tr').filter({ hasText: prestationName }).first();
    await expect(prestationRow).toBeVisible();
    await expect(prestationRow).toContainText('45 min');
    await expect(prestationRow).toContainText('89,00');

    await page.goto(`/admin/appointments/motifs/${prestationId}/edit`, { waitUntil: 'networkidle' });
    const nameInput = page.getByLabel('Nom');
    await expect(nameInput).toHaveValue(prestationName);
    await nameInput.fill(updatedPrestationName);
    await page.getByLabel('Durée (minutes)').fill('60');
    await page.getByLabel('Prix (EUR)').fill('99');

    const updateResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/appointments/prestations/${prestationId}`) &&
        response.request().method() === 'PUT' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /Mettre à jour/i }).click();
    await updateResponse;
    await page.waitForURL('**/admin/appointments/motifs');

    const updatedRow = page.locator('tbody tr').filter({ hasText: updatedPrestationName }).first();
    await expect(updatedRow).toBeVisible();
    await expect(updatedRow).toContainText('60 min');
    await expect(updatedRow).toContainText('99,00');

    await clickAfterScroll(
      updatedRow.getByRole('button', {
        name: new RegExp(`Supprimer la prestation ${updatedPrestationName}`),
      }),
    );
    const deleteDialog = page.getByRole('alertdialog');
    await expect(deleteDialog).toBeVisible();

    const deleteResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/appointments/prestations/${prestationId}`) &&
        response.request().method() === 'DELETE' &&
        response.status() < 400,
    );
    await clickAfterScroll(deleteDialog.getByRole('button', { name: /^Supprimer$/i }));
    await deleteResponse;
    await expect(page.locator('tbody tr').filter({ hasText: updatedPrestationName })).toHaveCount(0);
  });

  test('admin can create, update and delete a news article', async ({ page }) => {
    const title = `Actualite E2E ${Date.now()}`;
    const updatedTitle = `${title} Mod`;
    const initialCategory = 'Tests';
    const updatedCategory = 'Tests QA';

    await page.goto('/admin/news/new', { waitUntil: 'networkidle' });
    await page.getByLabel('Titre').fill(title);
    await page.getByLabel('Catégorie').fill(initialCategory);
    await page.getByLabel('Résumé').fill('Résumé E2E pour la création d’une actualité admin.');
    await page.getByLabel('Contenu').fill('Contenu E2E pour vérifier le cycle CRUD complet côté admin.');

    const createResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/admin/news') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /^Enregistrer$/i }).click();
    const createdArticle = (await (await createResponse).json())?.data?.article;
    const articleId = Number(createdArticle?.id);
    const articleSlug = String(createdArticle?.slug ?? '').trim();
    expect(Number.isFinite(articleId)).toBeTruthy();
    expect(articleSlug).toBeTruthy();

    await page.waitForURL('**/admin/news');
    const articleRow = page.locator('tbody tr').filter({ hasText: title }).first();
    await expect(articleRow).toBeVisible();
    await expect(articleRow).toContainText(initialCategory);
    await expect(articleRow).toContainText('Publiée');

    await page.goto(`/admin/news/${articleId}/edit`, { waitUntil: 'networkidle' });
    await expect(page.getByLabel('Titre')).toHaveValue(title);
    await page.getByLabel('Titre').fill(updatedTitle);
    await page.getByLabel('Catégorie').fill(updatedCategory);
    await page.getByLabel('Résumé').fill('Résumé mis à jour pour le scénario E2E admin.');
    await page.getByLabel('Contenu').fill('Contenu mis à jour pour confirmer la modification de l’actualité.');

    const updateResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/news/${articleId}`) &&
        response.request().method() === 'PUT' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /^Enregistrer$/i }).click();
    await updateResponse;
    await page.waitForURL('**/admin/news');

    const updatedRow = page.locator('tbody tr').filter({ hasText: updatedTitle }).first();
    await expect(updatedRow).toBeVisible();
    await expect(updatedRow).toContainText(updatedCategory);

    await clickAfterScroll(updatedRow.getByRole('button', { name: /^Supprimer$/i }));
    const deleteDialog = page.getByRole('alertdialog');
    await expect(deleteDialog).toBeVisible();

    const deleteResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/news/${articleId}`) &&
        response.request().method() === 'DELETE' &&
        response.status() < 400,
    );
    await clickAfterScroll(deleteDialog.getByRole('button', { name: /^Supprimer$/i }));
    await deleteResponse;
    await expect(page.locator('tbody tr').filter({ hasText: updatedTitle })).toHaveCount(0);
  });

  test('admin can create, update and delete a voucher', async ({ page }) => {
    const name = `Bon E2E ${Date.now()}`;
    const updatedName = `${name} Mod`;
    const code = `E2E${Date.now().toString().slice(-6)}`;
    const updatedCode = `${code}X`;

    await page.goto('/admin/vouchers/new', { waitUntil: 'networkidle' });
    await page.getByLabel('Nom').fill(name);
    await page.getByLabel('Code').fill(code);
    await page.getByLabel('Description').fill('Description E2E du bon de réduction.');
    await page.getByLabel('Type de remise').selectOption('percent');
    await page.getByLabel(/Valeur/i).fill('15');

    const createResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/admin/vouchers') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /Créer le bon/i }).click();
    const createdVoucher = (await (await createResponse).json())?.data?.voucher;
    const voucherId = Number(createdVoucher?.id);
    expect(Number.isFinite(voucherId)).toBeTruthy();

    await page.waitForURL('**/admin/vouchers');
    const voucherRow = page.locator('tbody tr').filter({ hasText: name }).first();
    await expect(voucherRow).toBeVisible();
    await expect(voucherRow).toContainText(code);
    await expect(voucherRow).toContainText('15%');

    await page.goto(`/admin/vouchers/${voucherId}/edit`, { waitUntil: 'networkidle' });
    await expect(page.getByLabel('Nom')).toHaveValue(name);
    await page.getByLabel('Nom').fill(updatedName);
    await page.getByLabel('Code').fill(updatedCode);
    await page.getByLabel(/Valeur/i).fill('20');

    const updateResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/vouchers/${voucherId}`) &&
        response.request().method() === 'PUT' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /Mettre à jour le bon/i }).click();
    await updateResponse;
    await page.waitForURL('**/admin/vouchers');

    const updatedRow = page.locator('tbody tr').filter({ hasText: updatedName }).first();
    await expect(updatedRow).toBeVisible();
    await expect(updatedRow).toContainText(updatedCode);
    await expect(updatedRow).toContainText('20%');

    await clickAfterScroll(updatedRow.getByRole('button', { name: /Supprimer le bon/i }));
    const deleteDialog = page.getByRole('alertdialog');
    await expect(deleteDialog).toBeVisible();

    const deleteResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/vouchers/${voucherId}`) &&
        response.request().method() === 'DELETE' &&
        response.status() < 400,
    );
    await clickAfterScroll(deleteDialog.getByRole('button', { name: /^Supprimer$/i }));
    await deleteResponse;
    await expect(page.locator('tbody tr').filter({ hasText: updatedName })).toHaveCount(0);
  });

  test('admin can create, update and delete a promotion', async ({ page }) => {
    const name = `Promotion E2E ${Date.now()}`;
    const slug = `promotion-e2e-${Date.now()}`;
    const updatedName = `${name} Mod`;
    const updatedSlug = `${slug}-mod`;

    await page.goto('/admin/promotions/new', { waitUntil: 'networkidle' });
    await page.getByLabel('Nom').fill(name);
    await page.getByLabel('Slug').fill(slug);
    await page.getByLabel('Description').fill('Promotion E2E pour couvrir le workflow admin.');
    await page.getByLabel('Type de remise').selectOption('percent');
    await page.getByLabel(/Valeur/i).fill('12');
    await page.getByLabel('Audience').selectOption('all_users');
    await page.getByLabel('Panier minimum en euros').fill('50');

    const createResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/admin/promotions') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /^Créer$/i }).click();
    const createdPromotion = (await (await createResponse).json())?.data?.promotion;
    const promotionId = Number(createdPromotion?.id);
    expect(Number.isFinite(promotionId)).toBeTruthy();

    await page.waitForURL('**/admin/promotions');
    const promotionRow = page.locator('tbody tr').filter({ hasText: name }).first();
    await expect(promotionRow).toBeVisible();
    await expect(promotionRow).toContainText(slug);
    await expect(promotionRow).toContainText('12%');
    await expect(promotionRow).toContainText('Active');

    await page.goto(`/admin/promotions/${promotionId}/edit`, { waitUntil: 'networkidle' });
    await expect(page.getByLabel('Nom')).toHaveValue(name);
    await page.getByLabel('Nom').fill(updatedName);
    await page.getByLabel('Slug').fill(updatedSlug);
    await page.getByLabel(/Valeur/i).fill('18');
    await page.getByLabel('Audience').selectOption('inactive_customers');
    await page.getByLabel('Inactivité depuis X jours').fill('120');

    const updateResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/promotions/${promotionId}`) &&
        response.request().method() === 'PUT' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /Mettre à jour/i }).click();
    await updateResponse;
    await page.waitForURL('**/admin/promotions');

    const updatedRow = page.locator('tbody tr').filter({ hasText: updatedName }).first();
    await expect(updatedRow).toBeVisible();
    await expect(updatedRow).toContainText(updatedSlug);
    await expect(updatedRow).toContainText('18%');

    await clickAfterScroll(updatedRow.getByRole('button', { name: /Supprimer la promotion/i }));
    const deleteDialog = page.getByRole('alertdialog');
    await expect(deleteDialog).toBeVisible();

    const deleteResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/promotions/${promotionId}`) &&
        response.request().method() === 'DELETE' &&
        response.status() < 400,
    );
    await clickAfterScroll(deleteDialog.getByRole('button', { name: /^Supprimer$/i }));
    await deleteResponse;
    await expect(page.locator('tbody tr').filter({ hasText: updatedName })).toHaveCount(0);
  });

  test('admin can create, update and delete a service', async ({ page }) => {
    const title = `Service E2E ${Date.now()}`;
    const updatedTitle = `${title} Mod`;
    const imageUrl = 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80';

    await page.goto('/admin/services/new', { waitUntil: 'networkidle' });
    await page.getByLabel('Titre').fill(title);
    await page.getByLabel('Description').fill('Description E2E pour le service admin.');
    await page.getByLabel('Mode de facturation').selectOption({ index: 1 });
    await page.getByRole('checkbox', { name: /Mettre en avant sur la page d'accueil/i }).check();
    await page.getByLabel(/Ou URL d'illustration/i).fill(imageUrl);
    await page.getByLabel(/Texte alternatif de l'image/i).fill(`Illustration ${title}`);
    await page.getByLabel('Durée estimée').fill('2');
    await page.getByLabel('Unité de durée').selectOption('day');
    await page.getByLabel(/Prix HT \(EUR\)/i).fill('149');
    await page.getByLabel(/TVA \(%\)/i).fill('20');

    const createResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/admin/services') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /^Créer$/i }).click();
    const createdService = (await (await createResponse).json())?.data;
    const serviceId = Number(createdService?.id);
    expect(Number.isFinite(serviceId)).toBeTruthy();

    await page.goto(`/admin/services/${serviceId}/edit`, { waitUntil: 'networkidle' });
    await expect(page.getByLabel('Titre')).toHaveValue(title);
    await expect(page.getByLabel('Durée estimée')).toHaveValue('2');
    await expect(page.getByLabel(/Prix HT \(EUR\)/i)).toHaveValue(/149(?:\.00)?/);
    await expect(
      page.getByRole('checkbox', { name: /Mettre en avant sur la page d'accueil/i }),
    ).toBeChecked();

    await page.getByLabel('Titre').fill(updatedTitle);
    await page.getByLabel('Durée estimée').fill('3');
    await page.getByLabel('Unité de durée').selectOption('hour');
    await page.getByLabel(/Prix HT \(EUR\)/i).fill('199');
    await page.getByRole('checkbox', { name: /Mettre en avant sur la page d'accueil/i }).uncheck();

    const updateResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/services/${serviceId}`) &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /Mettre à jour/i }).click();
    await updateResponse;
    await expect(page.getByLabel('Titre')).toHaveValue(updatedTitle);
    await expect(page.getByLabel('Durée estimée')).toHaveValue('3');
    await expect(page.getByLabel(/Prix HT \(EUR\)/i)).toHaveValue(/199(?:\.00)?/);
    await expect(
      page.getByRole('checkbox', { name: /Mettre en avant sur la page d'accueil/i }),
    ).not.toBeChecked();

    const deleteResult = await page.evaluate(async (id) => {
      const csrfResponse = await fetch('/api/csrf-token', {
        credentials: 'include',
        headers: { Accept: 'application/json' },
      });
      const csrfPayload = await csrfResponse.json();
      const csrfToken = String(csrfPayload?.data?.token ?? '').trim();

      const response = await fetch(`/api/admin/services/${id}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': csrfToken,
        },
      });

      return {
        ok: response.ok,
        status: response.status,
        body: await response.text(),
      };
    }, serviceId);
    expect(
      deleteResult.ok,
      `Service deletion failed with ${deleteResult.status}: ${deleteResult.body}`,
    ).toBeTruthy();

    await page.goto(`/admin/services/${serviceId}/edit`, { waitUntil: 'networkidle' });
    await expect(page.getByText(/Chargement impossible|introuvable|Chargement du service/i).first()).toBeVisible();
  });

  test('admin can update an order status from the orders list', async ({ page }, testInfo) => {
    await page.goto('/admin/orders', { waitUntil: 'networkidle' });
    const projectOrderIndex = testInfo.project.name === 'mobile-chrome' ? 1 : 0;
    const orderRows = page
      .locator('tbody tr')
      .filter({ has: page.getByRole('button', { name: /Modifier le statut/i }) })
      .filter({ hasNotText: /ORD-E2E-/i });
    test.skip((await orderRows.count()) <= projectOrderIndex, 'No distinct modifiable order available for this project.');
    const orderRow = orderRows.nth(projectOrderIndex);
    await expect(orderRow).toBeVisible();
    const orderNumber = ((await orderRow.getByRole('rowheader').textContent()) ?? '').trim();
    expect(orderNumber).toBeTruthy();
    const currentStatusCell = orderRow.locator('td').nth(5);
    const currentStatusLabel = ((await currentStatusCell.textContent()) ?? '').trim();

    await clickAfterScroll(orderRow.getByRole('button', { name: /Modifier le statut/i }));
    const dialog = page.getByRole('alertdialog');
    await expect(dialog).toBeVisible();

    const selectedOption = dialog.locator('input[type="radio"]:checked');
    const nextOption = dialog.locator('input[type="radio"]').filter({ hasNot: selectedOption }).first();
    test.skip((await nextOption.count()) === 0, 'No alternate status option available in dialog.');
    const nextStatus = await nextOption.getAttribute('value');
    expect(nextStatus).toBeTruthy();
    await nextOption.check({ force: true });
    const statusResponse = await Promise.all([
      page.waitForResponse(
        (response) =>
          response.url().includes('/api/admin/orders/') &&
          response.url().includes('/status') &&
          response.request().method() === 'PATCH' &&
          response.status() < 500,
      ),
      clickAfterScroll(dialog.getByRole('button', { name: /^Enregistrer$/i })),
    ]);
    const updatedOrderPayload = await statusResponse[0].json();
    const updatedStatusLabel = String(updatedOrderPayload?.data?.order?.statusLabel ?? '').trim();
    expect(updatedStatusLabel).toBeTruthy();

    await page.reload({ waitUntil: 'networkidle' });
    const updatedOrderRow = page.locator('tbody tr').filter({ hasText: orderNumber }).first();
    await expect(updatedOrderRow).toBeVisible();
    await expect(updatedOrderRow).toContainText(updatedStatusLabel);
    if (updatedStatusLabel !== currentStatusLabel) {
      await expect(updatedOrderRow).not.toContainText(currentStatusLabel);
    }
    await expect(dialog).toHaveCount(0);
  });
});

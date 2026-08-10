import { expect, test, type APIRequestContext, type Locator } from '@playwright/test';

import { ADMIN_STORAGE_STATE, CLIENT_STORAGE_STATE } from './helpers/auth';

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const getApiData = async <T>(request: APIRequestContext, url: string) => {
  const response = await request.get(url);
  expect(response.ok()).toBeTruthy();
  const payload = await response.json();

  return payload.data as T;
};

const clickAfterScroll = async (locator: Locator) => {
  await locator.scrollIntoViewIfNeeded();
  await locator.evaluate((element: HTMLElement) => element.click());
};

test('public user can add a sale product to cart and see it in the basket', async ({ page, request }) => {
  const catalog = await getApiData<{ items: Array<{ slug: string; stock: number; name: string }> }>(
    request,
    '/api/public/catalog/products?sellingType=sale&inStock=1&perPage=12',
  );
  const product = catalog.items.find((item) => item.stock > 0);
  test.skip(!product, 'No in-stock sale product available for cart journey.');

  await page.goto(`/catalogue/produits/${product!.slug}`, { waitUntil: 'networkidle' });
  await page.locator('.catalog-cart-button').first().click();
  await expect(page.getByText(/Produit ajouté au panier/i)).toBeVisible();

  await page.goto('/panier', { waitUntil: 'networkidle' });
  await expect(page.getByRole('heading', { name: /Mon panier/i })).toBeVisible();
  await expect(page.getByText(/Votre panier est vide pour le moment/i)).toHaveCount(0);
  await expect(page.getByRole('link', { name: new RegExp(escapeRegExp(product!.name), 'i') }).first()).toBeVisible();
});

test('public user can submit a trade-in request', async ({ page }) => {
  const productName = `MacBook Pro E2E ${Date.now()}`;

  await page.goto('/reprise', { waitUntil: 'networkidle' });

  await page.getByLabel('Prénom').fill('E2E');
  await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('TradeIn');
  await page.getByLabel('Email').fill(`e2e.tradein.${Date.now()}@example.test`);
  await page.getByLabel('Téléphone').fill('0601020304');
  await page.getByLabel('Nom du produit / modèle').fill(productName);
  await page.getByLabel('Prix payé à l’achat (€)').fill('1499');
  await page.getByLabel("Année d’achat").fill('2024');
  await page.getByLabel('Marque').fill('Apple');
  await page.getByLabel('Description et défauts constatés').fill(
    'Machine fonctionnelle avec quelques micro-rayures. Chargeur inclus.',
  );
  await page.locator('input[type="file"]').setInputFiles(
    '/home/ubuntu/hociatec/backend/var/private/trade-ins/b41937535b59e34738ab852371b8b5a0cd988d7a9e142c5e.pdf',
  );
  await page.getByRole('checkbox', {
    name: /J’accepte que Hociatec utilise ces informations/i,
  }).check();
  await page.getByRole('button', { name: /Obtenir mon estimation/i }).click();

  await expect(page.getByRole('heading', { name: /Demande envoyée/i })).toBeVisible();
  await expect(page.getByText(/a bien été enregistrée/i).first()).toBeVisible();
  await expect(page.getByText(/estimation indicative/i).first()).toBeVisible();
});

test('public trade-in request becomes visible in admin backoffice', async ({ page, browser }) => {
  const productName = `MacBook Pro Admin E2E ${Date.now()}`;

  await page.goto('/reprise', { waitUntil: 'networkidle' });
  await page.getByLabel('Prénom').fill('E2E');
  await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('TradeInAdmin');
  await page.getByLabel('Email').fill(`e2e.tradein.admin.${Date.now()}@example.test`);
  await page.getByLabel('Téléphone').fill('0601020304');
  await page.getByLabel('Nom du produit / modèle').fill(productName);
  await page.getByLabel('Prix payé à l’achat (€)').fill('1599');
  await page.getByLabel("Année d’achat").fill('2024');
  await page.getByLabel('Marque').fill('Apple');
  await page.getByLabel('Description et défauts constatés').fill(
    'Demande de reprise destinée à la vérification du backoffice admin.',
  );
  await page.locator('input[type="file"]').setInputFiles(
    '/home/ubuntu/hociatec/backend/var/private/trade-ins/b41937535b59e34738ab852371b8b5a0cd988d7a9e142c5e.pdf',
  );
  await page.getByRole('checkbox', {
    name: /J’accepte que Hociatec utilise ces informations/i,
  }).check();
  await page.getByRole('button', { name: /Obtenir mon estimation/i }).click();

  await expect(page.getByRole('heading', { name: /Demande envoyée/i })).toBeVisible();

  const adminContext = await browser.newContext({ storageState: ADMIN_STORAGE_STATE });
  const adminPage = await adminContext.newPage();

  try {
    await adminPage.goto('/admin/trade-ins', { waitUntil: 'networkidle' });
    await expect(adminPage.getByText(productName)).toBeVisible();
  } finally {
    await adminContext.close();
  }
});

test('public user can use global search to find a service', async ({ page, request }) => {
  const services = await getApiData<{ items: Array<{ id: number; title: string }> }>(
    request,
    '/api/public/services',
  );
  const service = services.items[0];
  test.skip(!service, 'No public service available for global search journey.');

  const searchTerm = service!.title.trim().split(/\s+/)[0]?.slice(0, 8) ?? '';
  test.skip(searchTerm.length < 2, 'Service title too short to trigger global search.');

  await page.goto('/recherche', { waitUntil: 'networkidle' });
  await page.locator('#global-search').fill(searchTerm);
  await page.locator('#main-content').getByRole('button', { name: /^Rechercher$/i }).click();

  await expect(page).toHaveURL(new RegExp(`/recherche\\?q=${encodeURIComponent(searchTerm)}`));
  await expect(page.getByRole('heading', { name: /^Services$/i })).toBeVisible();

  const serviceLink = page.getByRole('link', { name: new RegExp(escapeRegExp(service!.title), 'i') }).first();
  await expect(serviceLink).toBeVisible();

  await serviceLink.click();
  await expect(page).toHaveURL(new RegExp(`/services/${service!.id}$`));
});

test('client can publish a news comment and admin can delete it from the article page', async ({
  browser,
  request,
}) => {
  test.setTimeout(60_000);
  const news = await getApiData<{ items: Array<{ slug: string; title: string }> }>(
    request,
    '/api/public/news?page=1&perPage=1',
  );
  const article = news.items[0];
  test.skip(!article, 'No published news article available for comment moderation journey.');

  const commentText = `Commentaire actualite E2E ${Date.now()}`;
  const clientContext = await browser.newContext({ storageState: CLIENT_STORAGE_STATE });
  const clientPage = await clientContext.newPage();

  let createdCommentId = 0;

  try {
    await clientPage.goto(`/actualites/${article!.slug}`, { waitUntil: 'networkidle' });
    await expect(
      clientPage.getByRole('heading', { name: new RegExp(escapeRegExp(article!.title), 'i') }),
    ).toBeVisible();
    await clientPage.getByLabel('Ajouter un commentaire').fill(commentText);

    const createCommentResponse = await Promise.all([
      clientPage.waitForResponse(
        (response) =>
          response.url().includes(`/api/public/news/${article!.slug}/comments`) &&
          response.request().method() === 'POST' &&
          response.status() < 500,
      ),
      clientPage.getByRole('button', { name: /Publier le commentaire/i }).click(),
    ]);
    const createdCommentPayload = await createCommentResponse[0].json();
    createdCommentId = Number(createdCommentPayload?.data?.comment?.id ?? 0);
    expect(createdCommentId).toBeGreaterThan(0);
    await expect(clientPage.getByText(commentText)).toBeVisible();
  } finally {
    await clientContext.close().catch(() => undefined);
  }

  const adminContext = await browser.newContext({ storageState: ADMIN_STORAGE_STATE });
  const adminPage = await adminContext.newPage();

  try {
    await adminPage.goto(`/actualites/${article!.slug}`, { waitUntil: 'networkidle' });
    const commentArticle = adminPage.locator('article').filter({ hasText: commentText }).first();
    await expect(commentArticle).toBeVisible();
    const deleteButton = commentArticle.getByRole('button', { name: /Supprimer le commentaire/i });
    await expect(deleteButton).toBeVisible();
    await clickAfterScroll(deleteButton);
    const confirmDialog = adminPage.getByRole('alertdialog');
    await expect(confirmDialog).toBeVisible();
    const deleteResponsePromise = adminPage.waitForResponse(
      (response) =>
        response.url().includes(`/api/admin/news/comments/${createdCommentId}`) &&
        response.request().method() === 'DELETE' &&
        response.status() < 500,
    );
    await clickAfterScroll(confirmDialog.getByRole('button', { name: /^Supprimer$/i }));
    const deletedCommentPayload = await (await deleteResponsePromise).json();
    expect(deletedCommentPayload?.data?.deleted).toBeTruthy();
    await adminPage.reload({ waitUntil: 'networkidle' });
    await expect(adminPage.locator('article').filter({ hasText: commentText })).toHaveCount(0);
  } finally {
    await adminContext.close().catch(() => undefined);
  }

  const clientVerificationContext = await browser.newContext({ storageState: CLIENT_STORAGE_STATE });
  const clientVerificationPage = await clientVerificationContext.newPage();

  try {
    await clientVerificationPage.goto(`/actualites/${article!.slug}`, { waitUntil: 'networkidle' });
    await expect(clientVerificationPage.getByText(commentText)).toHaveCount(0);
  } finally {
    await clientVerificationContext.close().catch(() => undefined);
  }
});

test.describe('authenticated client journeys', () => {
  test.use({ storageState: CLIENT_STORAGE_STATE });

  test('client can submit an audit request', async ({ page }) => {
    const auditUrl = `https://example.test/audit-e2e-${Date.now()}`;

    await page.goto('/audits/request', { waitUntil: 'networkidle' });
    await page.getByLabel('URL ou accès').fill(auditUrl);
    await page.getByLabel('Objectifs et points d\'attention').fill(
      'Vérifier accessibilité, performance et structure générale du parcours.',
    );
    await page.getByRole('button', { name: /Envoyer la demande/i }).click();

    await expect(page.getByText(/Dossier créé:/i)).toBeVisible();

    await page.goto('/audits/me', { waitUntil: 'networkidle' });
    await expect(page.getByText(auditUrl)).toBeVisible();
  });

  test('client can book an appointment when slots are available', async ({ page, request }, testInfo) => {
    const prestationsPayload = await getApiData<{ items: Array<{ id: number; name: string }> }>(
      request,
      '/api/public/appointments/prestations',
    );
    const prestations = prestationsPayload.items;
    const projectSlotOffset = testInfo.project.name === 'mobile-chrome' ? 1 : 0;

    let selectedPrestation: { id: number; name: string } | null = null;
    let candidateSlots: Array<{ start: string; end: string }> = [];
    for (const prestation of prestations) {
      const availability = await getApiData<{ slots: Array<{ start: string; end: string }> }>(
        request,
        `/api/public/appointments/availability?prestationId=${prestation.id}&start=${encodeURIComponent(new Date().toISOString())}&end=${encodeURIComponent(new Date(Date.now() + 1000 * 60 * 60 * 24 * 31).toISOString())}`,
      );

      if (availability.slots.length > projectSlotOffset) {
        selectedPrestation = prestation;
        candidateSlots = availability.slots.slice(projectSlotOffset, projectSlotOffset + 3);
        break;
      }
    }

    test.skip(
      !selectedPrestation || candidateSlots.length === 0,
      'No distinct appointment slot available in the next month for this project.',
    );

    let bookingSucceeded = false;
    let lastBookingError = '';

    for (const selectedSlot of candidateSlots) {
      await page.goto('/appointments/book', { waitUntil: 'networkidle' });
      await page.getByRole('radio', { name: new RegExp(escapeRegExp(selectedPrestation!.name), 'i') }).check();
      await page.getByRole('button', { name: /^Suivant$/i }).click();

      const selectedSlotStart = new Date(selectedSlot.start);
      const selectedSlotHour = String(selectedSlotStart.getHours()).padStart(2, '0');
      const selectedSlotMinute = String(selectedSlotStart.getMinutes()).padStart(2, '0');
      const selectedSlotLabel = `${selectedSlotHour}:${selectedSlotMinute}`;
      const selectedDayOfMonth = String(selectedSlotStart.getDate());
      const availableDayButton = page
        .locator('button.booking-calendar__day:not([disabled])')
        .filter({ hasText: new RegExp(`^${escapeRegExp(selectedDayOfMonth)}$`) })
        .first();

      if ((await availableDayButton.count()) === 0) {
        lastBookingError = `Selected day ${selectedDayOfMonth} is no longer available in the UI.`;
        continue;
      }

      await expect(availableDayButton).toBeVisible();
      await availableDayButton.click();
      await expect(page.getByRole('heading', { name: /Étape 3 — Choisissez un créneau/i })).toBeVisible();

      const targetSlot = page.locator('.slot-card').filter({ hasText: selectedSlotLabel }).first();
      if ((await targetSlot.count()) === 0) {
        lastBookingError = `Selected slot ${selectedSlotLabel} is no longer available in the UI.`;
        continue;
      }

      await expect(targetSlot).toBeVisible();
      await targetSlot.click();
      await expect(page.getByRole('heading', { name: /Récapitulatif du rendez-vous/i })).toBeVisible();

      const confirmButton = page.getByRole('button', { name: /Confirmer/i });
      const bookingResponse = page.waitForResponse(
        (response) =>
          response.url().includes('/api/appointments') &&
          response.request().method() === 'POST' &&
          response.status() < 500,
      );
      await confirmButton.scrollIntoViewIfNeeded();
      await confirmButton.evaluate((button: HTMLButtonElement) => button.click());

      const bookingApiResponse = await bookingResponse;
      if (!bookingApiResponse.ok()) {
        lastBookingError = `Appointment booking failed with status ${bookingApiResponse.status()}: ${await bookingApiResponse.text()}`;
        continue;
      }

      await expect(page.getByRole('heading', { name: /Rendez-vous confirmé/i })).toBeVisible();
      bookingSucceeded = true;
      break;
    }

    expect(bookingSucceeded, lastBookingError || 'No appointment slot could be booked.').toBeTruthy();
  });

  test('client can cancel an upcoming appointment from account page', async ({ page }) => {
    await page.goto('/appointments/me', { waitUntil: 'networkidle' });
    const cancelButtons = page.getByRole('button', { name: /Annuler le rendez-vous/i });
    const readAppointmentsMeta = () =>
      page.evaluate(async () => {
        const response = await fetch('/api/appointments/me', {
          credentials: 'include',
          headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        return {
          upcomingTotal: Number(payload?.data?.meta?.upcomingTotal ?? 0),
          pastTotal: Number(payload?.data?.meta?.pastTotal ?? 0),
        };
      });
    const appointmentCard = page.locator('li').filter({
      has: page.getByRole('button', { name: /Annuler le rendez-vous/i }),
    }).first();
    test.skip((await appointmentCard.count()) === 0, 'No cancellable appointment available.');
    const initialAppointmentsPayload = await readAppointmentsMeta();
    const initialUpcomingTotal = Number(initialAppointmentsPayload?.upcomingTotal ?? 0);
    const initialPastTotal = Number(initialAppointmentsPayload?.pastTotal ?? 0);
    expect(initialUpcomingTotal).toBeGreaterThan(0);

    await expect(appointmentCard).toBeVisible();
    const cancelButton = appointmentCard.getByRole('button', { name: /Annuler le rendez-vous/i });
    await cancelButton.scrollIntoViewIfNeeded();
    await cancelButton.click();
    const cancellationResponse = await Promise.all([
      page.waitForResponse(
        (response) =>
          response.url().includes('/api/appointments/') &&
          response.url().includes('/status') &&
          response.request().method() === 'PATCH' &&
          response.status() < 500,
      ),
      page.getByRole('button', { name: /^Annuler le rendez-vous$/i }).last().click(),
    ]);
    const cancellationPayload = await cancellationResponse[0].json();
    expect(String(cancellationPayload?.data?.appointment?.statusCode ?? '')).toBe('cancelled');
    await expect.poll(readAppointmentsMeta).toEqual({
      upcomingTotal: initialUpcomingTotal - 1,
      pastTotal: initialPastTotal + 1,
    });
    await expect(page.getByText(/Annulé/i).first()).toBeVisible();
  });

  test('client audit request can be processed by admin and status becomes visible to client', async ({ page, browser }) => {
    const auditUrl = `https://example.test/audit-lifecycle-${Date.now()}`;

    await page.goto('/audits/request', { waitUntil: 'networkidle' });
    await page.getByLabel('URL ou accès').fill(auditUrl);
    await page.getByLabel('Objectifs et points d\'attention').fill(
      'Vérifier le cycle complet client/admin du traitement de la demande.',
    );
    await page.getByRole('button', { name: /Envoyer la demande/i }).click();
    await expect(page.getByText(/Dossier créé:/i)).toBeVisible();

    await page.goto('/audits/me', { waitUntil: 'networkidle' });
    const auditRow = page.locator('li').filter({ hasText: auditUrl }).first();
    await expect(auditRow).toBeVisible();
    const detailLink = auditRow.getByRole('link', { name: /Détails/i });
    const href = await detailLink.getAttribute('href');
    expect(href).toBeTruthy();

    await detailLink.click();
    await expect(page.getByText(auditUrl)).toBeVisible();

    const auditId = Number((href ?? '').split('/').pop());
    expect(Number.isFinite(auditId)).toBeTruthy();

    const adminContext = await browser.newContext({ storageState: ADMIN_STORAGE_STATE });
    const adminPage = await adminContext.newPage();

    try {
      await adminPage.goto(`/admin/audits/${auditId}`, { waitUntil: 'networkidle' });
      await expect(adminPage.getByText(auditUrl)).toBeVisible();
      const statusUpdatePromise = adminPage.waitForResponse(
        (response) =>
          response.url().includes(`/api/admin/audits/${auditId}/status`) &&
          response.request().method() === 'PUT' &&
          response.status() < 400,
      );
      await adminPage.locator('select').first().selectOption('in_progress');
      await statusUpdatePromise;
      await expect(adminPage.locator('select').first()).toHaveValue('in_progress');
    } finally {
      await adminContext.close();
    }

    await page.reload({ waitUntil: 'networkidle' });
    await expect(page.getByText(/Statut :/i)).toContainText(/cours|progress/i);
  });

  test('client can create a quote from a suggested service', async ({ page, request }) => {
    const services = await getApiData<{ items: Array<{ id: number; title: string }> }>(
      request,
      '/api/public/services',
    );
    const service = services.items[0];
    test.skip(!service, 'No public service available for quote journey.');

    const searchTerm = service!.title.trim().slice(0, Math.min(6, service!.title.trim().length));
    test.skip(searchTerm.length < 2, 'Service title too short to trigger quote search.');

    await page.goto('/devis/nouveau', { waitUntil: 'networkidle' });
    await page.getByLabel('Rechercher dans le catalogue').fill(searchTerm);
    await expect(page.getByRole('heading', { name: /Services suggérés/i })).toBeVisible();
    await page.getByRole('button', { name: /^Ajouter$/i }).first().click();
    await page.getByRole('button', { name: /Enregistrer dans mon espace/i }).click();

    await expect(page.getByText(/devis a bien été enregistré|devis enregistré/i).first()).toBeVisible();
  });

  test('client can update communication preferences', async ({ page }) => {
    await page.goto('/profile/communication-preferences', { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Modifier mes préférences/i }).click();

    const checkboxes = page.locator('input[type="checkbox"]');
    const checkboxCount = await checkboxes.count();
    test.skip(checkboxCount === 0, 'No communication preference available.');

    await checkboxes.first().check();
    if (checkboxCount > 1) {
      await checkboxes.nth(1).check();
    }

    const savePreferencesButton = page.getByRole('button', { name: /Enregistrer mes préférences/i });
    await savePreferencesButton.scrollIntoViewIfNeeded();
    await savePreferencesButton.evaluate((button: HTMLButtonElement) => button.click());
    await expect(page.getByText(/Préférences enregistrées/i)).toBeVisible();
  });

  test('client can create, update and delete an address', async ({ page }) => {
    const addressName = `Adresse E2E ${Date.now()}`;
    const updatedAddressName = `${addressName} modifiée`;

    await page.goto('/profile/addresses', { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Ajouter une adresse/i }).click();
    const dialog = page.getByRole('dialog', { name: /Ajouter une adresse/i });
    await dialog.getByRole('textbox', { name: 'Nom', exact: true }).fill(addressName);
    await dialog.getByRole('textbox', { name: 'Adresse', exact: true }).fill('10 rue de test');
    await dialog.getByRole('textbox', { name: 'Code postal', exact: true }).fill('75001');
    await dialog.getByRole('textbox', { name: 'Ville', exact: true }).fill('Paris');
    await dialog.getByRole('textbox', { name: 'Société', exact: true }).fill('Hociatec QA');
    const defaultAddressCheckbox = dialog.getByRole('checkbox', { name: /Définir comme adresse par défaut/i });
    await defaultAddressCheckbox.scrollIntoViewIfNeeded();
    await defaultAddressCheckbox.evaluate((input: HTMLInputElement) => input.click());
    const createAddressButton = dialog.getByRole('button', { name: /Ajouter l’adresse/i });
    await createAddressButton.scrollIntoViewIfNeeded();
    await createAddressButton.evaluate((button: HTMLButtonElement) => button.click());
    await expect(page.getByText(/Adresse ajoutée/i)).toBeVisible();

    const addressCard = page.locator('li').filter({ hasText: addressName }).first();
    await expect(addressCard).toBeVisible();
    await expect(addressCard.getByText('Par défaut', { exact: true })).toBeVisible();

    await addressCard.getByRole('button', { name: /Modifier/i }).click();
    const editDialog = page.getByRole('dialog', { name: /Modifier l’adresse/i });
    await editDialog.getByRole('textbox', { name: 'Nom', exact: true }).fill(updatedAddressName);
    const saveAddressButton = editDialog.getByRole('button', { name: /^Enregistrer$/i });
    await saveAddressButton.scrollIntoViewIfNeeded();
    await saveAddressButton.evaluate((button: HTMLButtonElement) => button.click());
    await expect(page.getByText(/Adresse mise à jour/i)).toBeVisible();

    const updatedCard = page.locator('li').filter({ hasText: updatedAddressName }).first();
    await expect(updatedCard).toBeVisible();
    await updatedCard.getByRole('button', { name: /Supprimer/i }).click();
    await expect(page.getByText(/Adresse supprimée/i)).toBeVisible();
    await expect(page.locator('li').filter({ hasText: updatedAddressName })).toHaveCount(0);
  });

  test('client can add a product to favorites and remove it from favorites page', async ({ page, request }) => {
    const catalog = await getApiData<{ items: Array<{ slug: string; stock: number }> }>(
      request,
      '/api/public/catalog/products?perPage=12',
    );
    const product = catalog.items[0];
    test.skip(!product, 'No catalog product available for favorites journey.');

    await page.goto(`/catalogue/produits/${product!.slug}`, { waitUntil: 'networkidle' });
    const favoriteButton = page.getByRole('button', { name: /Ajouter aux favoris|Retirer des favoris/i });

    if (await page.getByRole('button', { name: /Retirer des favoris/i }).count()) {
      await clickAfterScroll(page.getByRole('button', { name: /Retirer des favoris/i }));
      await expect(page.getByText(/Produit retiré de vos favoris/i)).toBeVisible();
    }

    await clickAfterScroll(favoriteButton);
    await expect(page.getByText(/Produit ajouté à vos favoris|déjà présent dans vos favoris/i)).toBeVisible();

    await page.goto('/favorites', { waitUntil: 'networkidle' });
    const removeButtons = page.getByRole('button', { name: /Retirer/i });
    test.skip((await removeButtons.count()) === 0, 'No removable favorite found.');
    await clickAfterScroll(removeButtons.first());
    await expect(page.getByText(/Produit retiré de vos favoris/i)).toBeVisible();
  });

  test('client can create and delete a quote from their account', async ({ page, request }) => {
    const services = await getApiData<{ items: Array<{ id: number; title: string }> }>(
      request,
      '/api/public/services',
    );
    const service = services.items[0];
    test.skip(!service, 'No public service available for quote lifecycle.');

    const searchTerm = service!.title.trim().slice(0, Math.min(6, service!.title.trim().length));
    test.skip(searchTerm.length < 2, 'Service title too short to trigger quote search.');

    await page.goto('/devis/nouveau', { waitUntil: 'networkidle' });
    await page.getByLabel('Rechercher dans le catalogue').fill(searchTerm);
    await expect(page.getByRole('heading', { name: /Services suggérés/i })).toBeVisible();
    await page.getByRole('button', { name: /^Ajouter$/i }).first().click();
    const createQuoteResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/public/quotes') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /Enregistrer dans mon espace/i }).click();
    const createQuotePayload = await (await createQuoteResponse).json();
    await expect(page.getByText(/devis a bien été enregistré|devis enregistré/i).first()).toBeVisible();

    const createdQuoteNumber = String(createQuotePayload?.data?.number ?? '').trim();
    expect(createdQuoteNumber).toBeTruthy();

    await page.goto('/quotes/me', { waitUntil: 'networkidle' });
    const quoteRow = page.locator('tbody tr').filter({ hasText: createdQuoteNumber }).first();
    await expect(quoteRow).toBeVisible();
    await quoteRow.getByRole('button', { name: /Supprimer/i }).click();
    await page.getByRole('button', { name: /Oui, supprimer/i }).click();
    await expect(page.locator('tbody tr').filter({ hasText: createdQuoteNumber })).toHaveCount(0);
  });

  test('client can update their profile information', async ({ page }) => {
    const updatedPhoneNumber = `06${String(Date.now()).slice(-8)}`;

    await page.goto('/profile', { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /^Modifier$/i }).click();

    const dialog = page.getByRole('dialog', { name: /Modifier le profil/i });
    await expect(dialog).toBeVisible();
    await dialog.getByLabel('Numéro de téléphone').fill(updatedPhoneNumber);
    await dialog.getByRole('combobox', { name: 'Sexe' }).selectOption('homme');

    const saveButton = dialog.getByRole('button', { name: /^Enregistrer$/i });
    await saveButton.scrollIntoViewIfNeeded();
    await saveButton.evaluate((button: HTMLButtonElement) => button.click());

    await expect(dialog).toHaveCount(0);
    await expect(page.getByText(updatedPhoneNumber)).toBeVisible();
  });

  test('client can open order details from their account', async ({ page }) => {
    await page.goto('/orders/me', { waitUntil: 'networkidle' });

    const orderRow = page.locator('tbody tr').filter({ hasText: 'ORD-E2E-CONFIRMED' }).first();
    test.skip((await orderRow.count()) === 0, 'No order available for details journey.');
    const orderNumber = (await orderRow.getByRole('rowheader').textContent())?.trim();
    expect(orderNumber).toBeTruthy();

    await clickAfterScroll(orderRow.getByRole('button', { name: /Voir le détail/i }));
    const dialogTitle = page.getByRole('heading', {
      name: new RegExp(`Commande\\s+${escapeRegExp(orderNumber!)}`, 'i'),
    });
    await expect(dialogTitle).toBeVisible();
    await expect(page.getByRole('heading', { name: /Livraison/i })).toBeVisible();
    await expect(page.getByRole('heading', { name: /Articles/i })).toBeVisible();
    const closeButton = page.getByRole('button', { name: /^Fermer$/i });
    await closeButton.scrollIntoViewIfNeeded();
    await closeButton.evaluate((button: HTMLButtonElement) => button.click());
    await expect(dialogTitle).toHaveCount(0);
  });

  test('client can submit a product review from a delivered order and see it on the public product page', async ({
    page,
    request,
  }, testInfo) => {
    const reviewComment = `Avis E2E ${Date.now()}`;
    const deliveredOrderNumber =
      testInfo.project.name === 'mobile-chrome'
        ? 'ORD-E2E-DELIVERED-MOBILE'
        : 'ORD-E2E-DELIVERED-CHROMIUM';

    await page.goto('/orders/me', { waitUntil: 'networkidle' });

    const pendingReviewsPayload = await page.evaluate(async () => {
      const response = await fetch(`${window.location.origin}/api/orders/me/pending-reviews`, {
        credentials: 'include',
      });

      return {
        ok: response.ok,
        status: response.status,
        body: await response.json(),
      };
    });
    expect(
      pendingReviewsPayload.ok,
      `Pending reviews fetch failed with status ${pendingReviewsPayload.status}.`,
    ).toBeTruthy();

    const reviewTarget = (pendingReviewsPayload.body?.data?.items ?? []).find(
      (item: { orderNumber?: string; product?: { id?: number; name?: string } | null }) =>
        item.orderNumber === deliveredOrderNumber && item.product?.id && item.product?.name,
    );
    test.skip(!reviewTarget, 'No delivered E2E order with pending review available.');

    const productSearch = await getApiData<{ items: Array<{ id: number; slug: string }> }>(
      request,
      `/api/public/catalog/products?q=${encodeURIComponent(String(reviewTarget.product.name))}&perPage=12`,
    );
    const product = productSearch.items.find((item) => item.id === reviewTarget.product.id);
    test.skip(!product, 'Reviewed product not found in public catalog.');

    await page.goto(`/orders/${reviewTarget.orderId}`, { waitUntil: 'networkidle' });
    await expect(page.getByText(deliveredOrderNumber)).toBeVisible();
    await expect(page.getByText(/Évaluer ce produit/i).first()).toBeVisible();

    await clickAfterScroll(
      page.getByRole('button', {
        name: new RegExp(`Attribuer 5 etoiles au produit ${escapeRegExp(String(reviewTarget.product.name))}`, 'i'),
      }).first(),
    );
    await page.getByPlaceholder(/Partagez votre experience/i).fill(reviewComment);
    const submitReviewResponse = page.waitForResponse(
      (response) =>
        response.url().includes(`/api/orders/${reviewTarget.orderId}/items/`) &&
        response.url().includes('/review') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await clickAfterScroll(
      page.getByRole('button', {
        name: new RegExp(`Envoyer l'evaluation du produit ${escapeRegExp(String(reviewTarget.product.name))}`, 'i'),
      }).first(),
    );
    await submitReviewResponse;

    await expect(page.getByText(reviewComment)).toBeVisible();
    await expect(page.getByText(/Évaluer ce produit/i)).toHaveCount(0);

    await page.goto(`/catalogue/produits/${product!.slug}`, { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: /Avis clients/i })).toBeVisible();
    await expect(page.getByText(reviewComment)).toBeVisible();
    await expect(page.getByText(/Client E\./i).first()).toBeVisible();
  });

  test('client can cancel a pending order when one is available', async ({ page }, testInfo) => {
    await page.goto('/orders/me', { waitUntil: 'networkidle' });

    const pendingOrderNumber =
      testInfo.project.name === 'mobile-chrome'
        ? 'ORD-E2E-PENDING-MOBILE'
        : 'ORD-E2E-PENDING-CHROMIUM';
    const orderRow = page.locator('tbody tr').filter({ hasText: pendingOrderNumber }).first();
    test.skip((await orderRow.count()) === 0, 'No cancellable pending order available.');
    await expect(orderRow).toBeVisible();
    const orderNumber = (await orderRow.getByRole('rowheader').textContent())?.trim();
    expect(orderNumber).toBeTruthy();

    await clickAfterScroll(orderRow.getByRole('button', { name: /Voir le détail/i }));
    const detailHeading = page.getByRole('heading', {
      name: new RegExp(`Commande\\s+${escapeRegExp(orderNumber!)}`, 'i'),
    });
    await expect(detailHeading).toBeVisible();
    const cancelButton = page.getByRole('button', { name: /Annuler la commande/i });
    if ((await cancelButton.count()) > 0) {
      await expect(cancelButton).toBeVisible();
      await clickAfterScroll(cancelButton);
      const confirmationDialog = page.getByRole('alertdialog');
      await expect(confirmationDialog).toBeVisible();
      await clickAfterScroll(confirmationDialog.getByRole('button', { name: /Oui, annuler/i }));
      await expect(confirmationDialog).toHaveCount(0);
    } else {
      await page.goto('/orders/me', { waitUntil: 'networkidle' });
      const fallbackOrderRow = page.locator('tbody tr').filter({ hasText: orderNumber! }).first();
      await expect(fallbackOrderRow).toBeVisible();
      const fallbackCancelButton = fallbackOrderRow.getByRole('button', { name: /^Annuler$/i });
      await expect(fallbackCancelButton).toBeVisible();
      await clickAfterScroll(fallbackCancelButton);
      const confirmationDialog = page.getByRole('alertdialog');
      await expect(confirmationDialog).toBeVisible();
      await clickAfterScroll(confirmationDialog.getByRole('button', { name: /Oui, annuler/i }));
      await expect(confirmationDialog).toHaveCount(0);
    }

    await page.goto('/orders/me', { waitUntil: 'networkidle' });
    const updatedOrderRow = page.locator('tbody tr').filter({ hasText: orderNumber! }).first();
    await expect(updatedOrderRow).toBeVisible();
    await expect(updatedOrderRow).toContainText(/Annulée/i);
    await expect(updatedOrderRow.getByRole('button', { name: /^Annuler$/i })).toHaveCount(0);
  });

  test('client can activate a beta profile and submit a linked bug report after admin approval', async ({
    page,
    browser,
  }) => {
    test.setTimeout(90_000);
    const reportTitle = `Signalement bêta E2E ${Date.now()}`;

    await page.goto('/beta', { waitUntil: 'networkidle' });
    await expect(page.getByText(/En attente|Accepté/i).first()).toBeVisible();

    const adminContext = await browser.newContext({ storageState: ADMIN_STORAGE_STATE });
    const adminPage = await adminContext.newPage();

    try {
      await adminPage.goto('/admin/beta-testers', { waitUntil: 'networkidle' });
      await adminPage.getByPlaceholder(/Rechercher un nom ou un e-mail/i).fill('e2e.client@hociatec.local');
      const testerRow = adminPage.locator('tbody tr').filter({ hasText: 'e2e.client@hociatec.local' }).first();
      await expect(testerRow).toBeVisible();

      const statusSelect = testerRow.locator('select').first();
      await statusSelect.selectOption('accepted');
      await expect(statusSelect).toHaveValue('accepted');

      await page.reload({ waitUntil: 'networkidle' });
      await expect(page.getByText(/Accepté/i).first()).toBeVisible();

      const campaignCardButton = page.getByRole('button', { name: /campagne première/i }).first();
      await expect(campaignCardButton).toBeVisible();
      await clickAfterScroll(campaignCardButton);
      const createReportButton = page.getByRole('button', { name: /^Envoyer un signalement$/i }).first();
      await clickAfterScroll(createReportButton);

      await page.getByLabel(/Titre du signalement/i).fill(reportTitle);
      await page.getByLabel(/Description détaillée/i).fill(
        'Signalement E2E créé après validation admin du profil bêta.',
      );
      await page.getByLabel(/Résultat attendu/i).fill('Le parcours doit rester stable.');
      await page.getByLabel(/Résultat constaté/i).fill('Parcours vérifié en automatisation.');
      await page.getByLabel(/Niveau de gravité/i).selectOption('normal');
      await clickAfterScroll(page.getByRole('button', { name: /Envoyer le signalement/i }));
      await expect(page.getByText(/signalement a été transmis avec succès/i)).toBeVisible();

      const reportsPayload = await adminPage.evaluate(async (title) => {
        const response = await fetch(`/api/admin/beta-reports?search=${encodeURIComponent(title)}`, {
          credentials: 'include',
        });

        return {
          ok: response.ok,
          status: response.status,
          body: await response.json(),
        };
      }, reportTitle);
      expect(reportsPayload.ok, `Admin beta reports search failed with status ${reportsPayload.status}`).toBeTruthy();
      const reportItems = reportsPayload.body?.data?.items ?? [];
      expect(
        reportItems.some((item: { title?: string }) => item.title === reportTitle),
        `Created beta report "${reportTitle}" was not returned by admin API.`,
      ).toBeTruthy();
    } finally {
      await adminContext.close().catch(() => undefined);
    }
  });

  test('admin can create a customer voucher and client can see it in their account', async ({
    page,
    browser,
  }) => {
    const voucherName = `Bon client E2E ${Date.now()}`;
    const voucherCode = `CLI${Date.now().toString().slice(-6)}`;

    const adminContext = await browser.newContext({ storageState: ADMIN_STORAGE_STATE });
    const adminPage = await adminContext.newPage();

    try {
      await adminPage.goto('/admin/customers', { waitUntil: 'networkidle' });
      await adminPage
        .getByPlaceholder(/Rechercher un client/i)
        .fill('e2e.client@hociatec.local');
      const customerRow = adminPage.locator('tbody tr').filter({ hasText: 'e2e.client@hociatec.local' }).first();
      await expect(customerRow).toBeVisible();
      await customerRow.getByRole('link', { name: /Fiche client/i }).click();
      await adminPage.getByRole('link', { name: /Gérer les bons de réduction/i }).click();

      await adminPage.getByLabel('Nom').fill(voucherName);
      await adminPage.getByLabel('Code').fill(voucherCode);
      await adminPage.getByLabel('Type').selectOption('fixed_cents');
      await adminPage.getByLabel(/Valeur/i).fill('25');
      await adminPage.getByLabel('Description').fill('Bon E2E visible dans l’espace client.');

      const createVoucherResponse = adminPage.waitForResponse(
        (response) =>
          response.url().includes('/api/admin/customers/') &&
          response.url().includes('/vouchers') &&
          response.request().method() === 'POST' &&
          response.status() < 400,
      );
      await adminPage.getByRole('button', { name: /Créer le bon/i }).click();
      await createVoucherResponse;
      await expect(adminPage.getByText(voucherName)).toBeVisible();
      await expect(adminPage.getByText(new RegExp(`Code\\s+${escapeRegExp(voucherCode)}`, 'i'))).toBeVisible();
    } finally {
      await adminContext.close();
    }

    await page.goto('/vouchers/me', { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: /Mes bons de réduction/i })).toBeVisible();
    await expect(page.getByText(voucherName)).toBeVisible();
    await expect(page.getByText(voucherCode)).toBeVisible();
    await expect(page.getByText(/25,00/).first()).toBeVisible();
  });

  test('admin can create a training and session, then client can enroll and open the enrollment details', async ({
    page,
    browser,
  }) => {
    const trainingTitle = `Formation E2E ${Date.now()}`;
    const sessionStartDate = '2026-08-17';
    const sessionEndDate = '2026-08-19';

    const adminContext = await browser.newContext({ storageState: ADMIN_STORAGE_STATE });
    const adminPage = await adminContext.newPage();

    let trainingSlug = '';

    try {
      await adminPage.goto('/admin/trainings/new', { waitUntil: 'networkidle' });
      await adminPage.getByLabel('Titre').fill(trainingTitle);
      await adminPage.getByLabel('Description courte').fill(
        'Formation créée pour vérifier le flux complet admin/client.',
      );
      await adminPage.getByLabel('Objectif').fill('Valider la réservation de formation en E2E.');
      await adminPage.getByLabel('Public concerné').fill('Clients en test automatisé.');
      await adminPage.getByLabel('Prix en euros').fill('0');
      await adminPage
        .getByLabel('Feuille de route, une étape par ligne')
        .fill('Découverte\nRéservation\nConsultation du détail');

      const createTrainingResponse = adminPage.waitForResponse(
        (response) =>
          response.url().includes('/api/admin/trainings') &&
          response.request().method() === 'POST' &&
          response.status() < 400,
      );
      await adminPage.getByRole('button', { name: /^Enregistrer$/i }).click();
      const createdTraining = (await (await createTrainingResponse).json())?.data;
      trainingSlug = String(createdTraining?.slug ?? '').trim();
      expect(trainingSlug).toBeTruthy();

      await adminPage.goto('/admin/trainings/sessions/new', { waitUntil: 'networkidle' });
      await adminPage.locator('select').first().selectOption({ label: trainingTitle });
      await adminPage.locator('select').nth(1).selectOption('remote');
      await adminPage.getByLabel('Date de début de disponibilité').fill(sessionStartDate);
      await adminPage.getByLabel('Date de fin de disponibilité').fill(sessionEndDate);
      await adminPage.getByLabel('Réservable chaque jour à partir de').fill('09:00');
      await adminPage.getByLabel('Réservable chaque jour jusqu’à').fill('18:00');
      await adminPage.getByLabel('Lien de visioconférence').fill('https://example.test/e2e-training');
      await adminPage.getByLabel(/Nombre maximum de participants/i).fill('3');

      const createSessionResponse = adminPage.waitForResponse(
        (response) =>
          response.url().includes('/api/admin/training-sessions') &&
          response.request().method() === 'POST' &&
          response.status() < 400,
      );
      await adminPage.getByRole('button', { name: /^Enregistrer$/i }).click();
      await createSessionResponse;
    } finally {
      await adminContext.close();
    }

    await page.goto(`/formations/${trainingSlug}`, { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: trainingTitle })).toBeVisible();
    await page.getByLabel('Date souhaitée').fill('2026-08-17');
    await page.getByLabel('Heure de début souhaitée').fill('09:00');

    const enrollResponse = page.waitForResponse(
      (response) =>
        response.url().includes('/api/trainings/enrollments') &&
        response.request().method() === 'POST' &&
        response.status() < 400,
    );
    await page.getByRole('button', { name: /^Réserver$/i }).click();
    await enrollResponse;

    await page.goto('/trainings/me', { waitUntil: 'networkidle' });
    const enrollmentRow = page.locator('tbody tr').filter({ hasText: trainingTitle }).first();
    await expect(enrollmentRow).toBeVisible();
    await enrollmentRow.getByRole('link', { name: /Détail/i }).click();
    await expect(page.getByRole('heading', { name: trainingTitle })).toBeVisible();
    await expect(page.getByText(/https:\/\/example\.test\/e2e-training/i)).toBeVisible();
  });
});

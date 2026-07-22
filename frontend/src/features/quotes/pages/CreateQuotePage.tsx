import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useRef, useState } from 'react';
import { createPublicQuote, fetchPublicQuoteServices, generateMyQuotePdf, type QuoteDto, type QuoteInput, type QuoteServiceDto } from '@/features/quotes/api';
import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useToast } from '@/shared/components/ui/toast';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { PublicQuoteSelectionList } from '@/features/quotes/components/PublicQuoteSelectionList';
import { formatEuroCents } from '@/shared/lib/formatters';
import {
  createDefaultQuoteValidity,
  DEFAULT_QUOTE_CONDITIONS,
  formatQuoteDate,
  formatQuotePrice,
  type QuoteItem,
} from '@/features/quotes/utils/quoteFormUtils';

type QuoteDraft = QuoteInput & {
  items: QuoteItem[];
};

export const CreateQuotePage = () => {
  useDocumentTitle('Créer un devis');
  const { user, status } = useAuth();
  const toast = useToast();
  const [form, setForm] = useState<QuoteDraft>({
    customer: {},
    items: [],
    discountCents: 0,
    shippingCents: 0,
    conditions: DEFAULT_QUOTE_CONDITIONS,
    ...createDefaultQuoteValidity(),
  });
  const [message, setMessage] = useState<string | null>(null);
  const [savedQuote, setSavedQuote] = useState<QuoteDto | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const [searchQuery, setSearchQuery] = useState('');
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [productLoading, setProductLoading] = useState(false);
  const productDebounce = useRef<number | undefined>(undefined);
  const [rentalDialogOpen, setRentalDialogOpen] = useState(false);
  const [rentalCandidate, setRentalCandidate] = useState<CatalogProduct | null>(null);

  const [allServices, setAllServices] = useState<QuoteServiceDto[]>([]);
  const filteredServices = useMemo(
    () => allServices.filter((s) => s.title.toLowerCase().includes(searchQuery.trim().toLowerCase())).slice(0, 20),
    [allServices, searchQuery],
  );

  useEffect(() => {
    void fetchPublicQuoteServices().then(setAllServices).catch(() => void 0);
  }, []);

  useEffect(() => {
    if (status === 'authenticated' && user) {
      setForm((f) => ({
        ...f,
        customer: {
          ...f.customer,
          name: [user.firstName, user.lastName].filter(Boolean).join(' ').trim(),
          email: user.email,
          address: [user.address, [user.postalCode, user.city].filter(Boolean).join(' ')].filter(Boolean).join(' ').trim(),
        },
      }));
    }
  }, [status, user]);

  useEffect(() => {
    const q = searchQuery.trim();
    if (productDebounce.current) {
      window.clearTimeout(productDebounce.current);
    }
    if (q.length < 2) {
      setProducts([]);
      return;
    }
    setProductLoading(true);
    productDebounce.current = window.setTimeout(() => {
      void fetchPublicProducts({ q, perPage: 48, sort: 'relevance' })
        .then((items) => setProducts(items))
        .finally(() => setProductLoading(false));
    }, 300);
  }, [searchQuery]);

  const totals = useMemo(() => {
    let ht = 0;
    let vat = 0;
    for (const it of form.items ?? []) {
      const isRental = it.sellingType === 'rental';
      const months = isRental ? Math.max(1, it.rentalMonths ?? 1) : 1;
      const line = Math.max(0, (it.unitPriceCents ?? 0) * (it.quantity ?? 1) * months - (it.discountCents ?? 0));
      ht += line;
      vat += Math.round(line * ((it.vatRate ?? 0) / 100));
    }
    ht = Math.max(0, ht - (form.discountCents ?? 0));
    return { ht, vat, ttc: ht + vat + (form.shippingCents ?? 0) };
  }, [form]);

  const addProductLineFromProduct = (p: CatalogProduct) => {
    if (!p) return;
    setForm((f) => ({
      ...f,
      items: [
        ...f.items,
        {
          type: 'product',
          productId: p.id,
          name: p.name,
          sellingType: p.sellingType,
          quantity: 1,
          unitPriceCents: p.effectivePriceCents ?? p.priceCents,
          vatRate: 20,
          discountCents: 0,
          rentalMonths: p.sellingType === 'rental' ? 1 : undefined,
        },
      ],
    }));
  };

  const addServiceLine = (serviceId: number) => {
    const s = allServices.find((x) => x.id === serviceId);
    if (!s) return;
    setForm((f) => ({
      ...f,
      items: [
        ...f.items,
        {
          type: 'service',
          serviceId: s.id,
          name: s.title,
          quantity: 1,
          unitPriceCents: s.priceCents,
          vatRate: Number(s.vatRate ?? 0),
          discountCents: 0,
        },
      ],
    }));
  };

  const updateItem = (index: number, patch: Partial<QuoteItem>) => {
    setForm((f) => ({ ...f, items: f.items.map((it, i: number) => (i === index ? { ...it, ...patch } : it)) }));
  };

  const removeItem = (index: number) => {
    setForm((f) => ({ ...f, items: f.items.filter((_, i: number) => i !== index) }));
  };

  const findProductItemIndex = (productId: number) =>
    (form.items ?? []).findIndex((it) => it.type === 'product' && it.productId === productId);

  const submit = async () => {
    if (status !== 'authenticated' || !user) {
      toast.show('Connectez-vous pour enregistrer ce devis dans votre espace client.', { variant: 'info' });
      return;
    }
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      const created = await createPublicQuote(form);
      setSavedQuote(created ?? null);
      toast.show('Devis enregistré. Vous pouvez le retrouver dans votre espace client.', { variant: 'success' });
      setMessage('Devis enregistré. Vous pouvez le retrouver dans votre espace client.');
    } catch (e) {
      const messageText = getHttpErrorMessage(e, "Le devis n'a pas pu être créé. Vérifiez les informations saisies puis réessayez.");
      toast.show(messageText, { variant: 'error' });
      setError(messageText);
    } finally {
      setSaving(false);
    }
  };

  const handleDownloadPdf = async () => {
    if (status !== 'authenticated' || !user) {
      toast.show('Connectez-vous pour générer et télécharger votre devis en PDF.', { variant: 'info' });
      return;
    }

    if ((form.items ?? []).length === 0) {
      toast.show('Ajoutez au moins un produit ou service avant de générer le PDF.', { variant: 'info' });
      return;
    }

    try {
      let quote = savedQuote;
      if (!quote) {
        setSaving(true);
        setError(null);
        setMessage(null);
        quote = await createPublicQuote(form);
        setSavedQuote(quote ?? null);
        setMessage('Devis enregistré. Le PDF est en cours de préparation.');
      }

      const pdf = await generateMyQuotePdf(quote.id);
      const fileName = `${quote.number ?? 'devis'}.pdf`;
      const blobUrl = window.URL.createObjectURL(pdf);
      const link = window.document.createElement('a');
      link.href = blobUrl;
      link.download = fileName;
      window.document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(blobUrl);
    } catch (e) {
      const messageText = await getHttpErrorMessageAsync(e, "Le PDF n'a pas pu être généré. Réessayez dans quelques instants.");
      toast.show(messageText, { variant: 'error' });
      setError(messageText);
    } finally {
      setSaving(false);
    }
  };

  return (
    <SiteLayout>
      <PageContainer size="wide" title="Créer un devis">
        {error && <FeedbackMessage>{error}</FeedbackMessage>}
        {message && (
          <FeedbackMessage variant="success">
            {message}
          </FeedbackMessage>
        )}

        <div className="quote-builder grid grid-cols-1 gap-8 md:grid-cols-3">
          <div className="md:col-span-2 space-y-6">
            <section>
              <h3 className="font-semibold mb-2">Coordonnées du demandeur</h3>
              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <label className="register-form__field">
                  <span>Nom complet</span>
                  <input placeholder="Ex. Camille Martin" value={form.customer?.name ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, name: e.target.value } })} disabled={status === 'authenticated'} />
                </label>
                <label className="register-form__field">
                  <span>Email de contact</span>
                  <input placeholder="Ex. camille@entreprise.fr" value={form.customer?.email ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, email: e.target.value } })} disabled={status === 'authenticated'} />
                </label>
                <label className="register-form__field">
                  <span>Entreprise <span className="text-stone-500">(facultatif)</span></span>
                  <input placeholder="Ex. Hociatec" value={form.customer?.company ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, company: e.target.value } })} />
                </label>
                <label className="register-form__field">
                  <span>Adresse de facturation <span className="text-stone-500">(facultatif)</span></span>
                  <input placeholder="Rue, code postal et ville" value={form.customer?.address ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, address: e.target.value } })} disabled={status === 'authenticated'} />
                </label>
              </div>
            </section>

            <section>
              <h3 className="font-semibold mb-2">Produits et services à chiffrer</h3>
              <div className="mb-4">
                <label className="register-form__field">
                  <span>Rechercher dans le catalogue</span>
                  <input
                    type="search"
                    placeholder="Tapez au moins 2 caractères: iPhone, audit, formation..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                </label>
              </div>
              {productLoading && (
                <p className="text-sm text-stone-500">Recherche des produits et services correspondants...</p>
              )}
              <div className="space-y-6">
                {searchQuery.trim().length >= 2 && filteredServices.length > 0 && (
                  <div>
                    <h2 className="text-lg font-semibold mb-2">Services suggérés ({filteredServices.length})</h2>
                    <div className="space-y-2 max-h-64 overflow-auto">
                      {filteredServices.map((s) => (
                        <div key={s.id} className="rounded border border-brand-100 p-2">
                          <div className="flex items-center justify-between">
                            <div>
                              <div className="text-sm font-semibold">{s.title}</div>
                              <div className="text-xs text-stone-500">{formatEuroCents(s.priceCents ?? 0)}</div>
                            </div>
                            <button
                              type="button"
                            className={(form.items ?? []).some((it) => it.type === 'service' && it.serviceId === s.id)
                                ? 'catalog-admin-actions__delete'
                                : 'register-form__submit quote-builder__small-button'}
                              onClick={() => {
                                const exists = (form.items ?? []).some((it) => it.type === 'service' && it.serviceId === s.id);
                                if (exists) {
                                  setForm((f) => ({
                                    ...f,
                                    items: f.items.filter((it) => !(it.type === 'service' && it.serviceId === s.id)),
                                  }));
                                } else {
                                  addServiceLine(s.id);
                                }
                              }}
                            >
                              {(form.items ?? []).some((it) => it.type === 'service' && it.serviceId === s.id) ? 'Retirer' : 'Ajouter'}
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
                {products.length > 0 && (
                  <div>
                    <h2 className="text-lg font-semibold mb-2">Produits disponibles ({Math.min(products.length, 20)})</h2>
                    <div className="space-y-2 max-h-64 overflow-auto">
                      {products.slice(0, 20).map((p) => (
                        <div key={p.id} className="rounded border border-brand-100 p-2">
                          <div className="flex items-center justify-between gap-3">
                            <div>
                              <div className="text-sm font-semibold">{p.name}</div>
                              <div className="text-xs text-stone-500">Référence: {p.sku}</div>
                              <div className="text-xs text-stone-500">{formatEuroCents((p.effectivePriceCents ?? p.priceCents) ?? 0)}{p.sellingType === 'rental' ? ' / mois' : ''}</div>
                            </div>
                            {p.sellingType === 'rental' ? (
                              <button
                                type="button"
                                className="register-form__submit quote-builder__small-button"
                                onClick={() => {
                                  const exists = (form.items ?? []).some(
                                    (it) => it.type === 'product' && it.productId === p.id,
                                  );
                                  if (exists) {
                                    setRentalCandidate(p);
                                    setRentalDialogOpen(true);
                                  } else {
                                    addProductLineFromProduct(p);
                                  }
                                }}
                              >
                                Ajouter
                              </button>
                            ) : findProductItemIndex(p.id) >= 0 ? (
                              <div className="inline-flex items-center gap-2">
                                <button
                                  type="button"
                                  className="px-2 py-1 border rounded"
                                  aria-label={`Diminuer la quantité de ${p.name}`}
                                  onClick={() => {
                                    const index = findProductItemIndex(p.id);
                                    if (index < 0) return;
                                    const currentQuantity = Math.max(1, form.items[index]?.quantity ?? 1);
                                    if (currentQuantity <= 1) {
                                      removeItem(index);
                                      return;
                                    }
                                    updateItem(index, { quantity: currentQuantity - 1 });
                                  }}
                                >
                                  -
                                </button>
                                <input
                                  type="number"
                                  min={0}
                                  className="w-16 text-center border rounded py-1"
                                  aria-label={`Quantité de ${p.name}`}
                                  value={Math.max(1, form.items[findProductItemIndex(p.id)]?.quantity ?? 1)}
                                  onChange={(e) => {
                                    const index = findProductItemIndex(p.id);
                                    if (index < 0) return;
                                    const nextQuantity = Number.parseInt(e.target.value, 10);
                                    if (Number.isNaN(nextQuantity) || nextQuantity <= 0) {
                                      removeItem(index);
                                      return;
                                    }
                                    updateItem(index, { quantity: nextQuantity });
                                  }}
                                />
                                <button
                                  type="button"
                                  className="px-2 py-1 border rounded"
                                  aria-label={`Augmenter la quantité de ${p.name}`}
                                  onClick={() => {
                                    const index = findProductItemIndex(p.id);
                                    if (index < 0) return;
                                    const currentQuantity = Math.max(1, form.items[index]?.quantity ?? 1);
                                    updateItem(index, { quantity: currentQuantity + 1 });
                                  }}
                                >
                                  +
                                </button>
                              </div>
                            ) : (
                              <button
                                type="button"
                                className="register-form__submit quote-builder__small-button"
                                onClick={() => addProductLineFromProduct(p)}
                              >
                                Ajouter
                              </button>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
              <div className="mt-8 space-y-3">
                <div>
                  <h3 className="text-lg font-semibold text-brand-900">Votre sélection</h3>
                  <p className="mt-1 text-sm text-stone-600">
                    Ajustez les quantités ou les durées avant d’enregistrer votre devis.
                  </p>
                </div>

                <PublicQuoteSelectionList
                  items={form.items}
                  onUpdateItem={updateItem}
                  onRemoveItem={removeItem}
                />
              </div>
            </section>
          </div>

          <div className="space-y-6">
            <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
              <h3 className="font-semibold text-brand-900">Estimation</h3>
              <p className="mt-1 text-sm text-stone-600">Montants calculés à partir de votre sélection.</p>
              <div className="mt-4 space-y-2 border-b border-brand-100 pb-4 text-sm text-stone-600">
                <div className="flex justify-between gap-4"><span>Début de validité</span><strong>{formatQuoteDate(form.validFrom)}</strong></div>
                <div className="flex justify-between gap-4"><span>Fin de validité</span><strong>{formatQuoteDate(form.validUntil)}</strong></div>
                {(form.discountCents ?? 0) > 0 && (
                  <div className="flex justify-between gap-4"><span>Remise globale</span><strong>{formatQuotePrice(form.discountCents ?? 0)}</strong></div>
                )}
              </div>
              <div className="mt-4 space-y-2">
                <div className="flex justify-between gap-4 text-sm text-stone-600"><span>Total HT</span><strong>{formatQuotePrice(totals.ht)}</strong></div>
                <div className="flex justify-between gap-4 text-sm text-stone-600"><span>TVA</span><strong>{formatQuotePrice(totals.vat)}</strong></div>
                <div className="flex justify-between gap-4 border-t border-brand-100 pt-3 text-lg text-brand-900"><span>Total TTC</span><strong>{formatQuotePrice(totals.ttc)}</strong></div>
              </div>
            </section>

            <div className="grid gap-3">
              <button type="button" className="register-form__submit" onClick={() => void submit()} disabled={saving || (form.items ?? []).length === 0}>
                {saving ? 'Enregistrement...' : 'Enregistrer dans mon espace'}
              </button>
              <button type="button" className="hero__button hero__button--ghost" onClick={() => void handleDownloadPdf()} disabled={saving || (form.items ?? []).length === 0}>
                Télécharger le PDF
              </button>
            </div>
          </div>
        </div>

        <ConfirmDialog
          open={rentalDialogOpen && Boolean(rentalCandidate)}
          title="Ajouter une location au devis ?"
          description={
            <div>
              Voulez-vous vraiment ajouter le produit en location à <strong>{rentalCandidate?.name ?? ''}</strong> à votre devis ?
            </div>
          }
          confirmLabel="Oui, ajouter"
          cancelLabel="Non"
          onCancel={() => {
            setRentalDialogOpen(false);
            setRentalCandidate(null);
          }}
          onConfirm={() => {
            if (rentalCandidate) {
              addProductLineFromProduct(rentalCandidate);
            }
            setRentalDialogOpen(false);
            setRentalCandidate(null);
          }}
        />
      </PageContainer>
    </SiteLayout>
  );
};

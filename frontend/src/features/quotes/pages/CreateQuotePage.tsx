import { useEffect, useMemo, useRef, useState } from 'react';
import { createPublicQuote, fetchPublicQuoteServices, generateMyQuotePdf } from '@/features/quotes/api';
import { fetchPublicProducts } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useToast } from '@/shared/components/ui/toast';

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(cents / 100);

const DEFAULT_QUOTE_CONDITIONS = `Le présent devis constitue une offre valable jusqu'à la date de fin de validité qui y figure. Il devient contractuel à compter de son acceptation expresse par le client.
Le devis est établi sur la base des informations communiquées par le client. Toute prestation, fourniture ou demande complémentaire non prévue au devis initial fera l'objet d'un accord écrit complémentaire ou d'un avenant.
Sauf stipulation particulière, les délais d'exécution ou de livraison sont indicatifs et courent à compter de la réception de l'acceptation du devis et, le cas échéant, de l'acompte prévu.
Sauf mention contraire, les prix sont exprimés en euros. Les taxes applicables sont celles en vigueur au jour de la facturation.
Pour les clients professionnels uniquement, tout retard de paiement pourra entraîner l'application de pénalités de retard exigibles sans rappel, calculées au taux de refinancement de la BCE majoré de 10 points, ainsi qu'une indemnité forfaitaire de 40 euros pour frais de recouvrement.
Pour les clients consommateurs, les garanties légales applicables demeurent celles prévues par la loi.`;

const toDateInputValue = (date: Date) => date.toISOString().slice(0, 10);

const createDefaultValidity = () => {
  const validFrom = new Date();
  const validUntil = new Date(validFrom);
  validUntil.setDate(validUntil.getDate() + 30);

  return {
    validFrom: toDateInputValue(validFrom),
    validUntil: toDateInputValue(validUntil),
  };
};

const formatDate = (value?: string | null) => {
  if (!value) return '-';

  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return '-';

  return date.toLocaleDateString('fr-FR');
};

export const CreateQuotePage = () => {
  useDocumentTitle('Créer un devis');
  const { user, status } = useAuth();
  const toast = useToast();
  const [form, setForm] = useState<any>({
    customer: {},
    items: [],
    discountCents: 0,
    shippingCents: 0,
    conditions: DEFAULT_QUOTE_CONDITIONS,
    ...createDefaultValidity(),
  });
  const [message, setMessage] = useState<string | null>(null);
  const [savedQuote, setSavedQuote] = useState<any | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  // Recherche dynamique produits / services
  const [searchQuery, setSearchQuery] = useState('');
  const [products, setProducts] = useState<any[]>([]);
  const [productLoading, setProductLoading] = useState(false);
  const productDebounce = useRef<number | undefined>(undefined);
  const [rentalDialogOpen, setRentalDialogOpen] = useState(false);
  const [rentalCandidate, setRentalCandidate] = useState<any | null>(null);

  const [allServices, setAllServices] = useState<any[]>([]);
  const filteredServices = useMemo(
    () => allServices.filter((s) => s.title.toLowerCase().includes(searchQuery.trim().toLowerCase())).slice(0, 20),
    [allServices, searchQuery],
  );

  useEffect(() => {
    void fetchPublicQuoteServices().then(setAllServices).catch(() => void 0);
  }, []);

  // Auto-fill and lock customer info when authenticated
  useEffect(() => {
    if (status === 'authenticated' && user) {
      setForm((f: any) => ({
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
      void fetchPublicProducts({ q })
        .then((items) => setProducts(items))
        .finally(() => setProductLoading(false));
    }, 300);
  }, [searchQuery]);

  const totals = useMemo(() => {
    let ht = 0;
    let vat = 0;
    for (const it of form.items ?? []) {
      const isRental = it.sellingType === 'rental' || it.sellingType === 'location';
      const months = isRental ? Math.max(1, (it as any).rentalMonths ?? 1) : 1;
      const line = Math.max(0, (it.unitPriceCents ?? 0) * (it.quantity ?? 1) * months - (it.discountCents ?? 0));
      ht += line;
      vat += Math.round(line * ((it.vatRate ?? 0) / 100));
    }
    ht = Math.max(0, ht - (form.discountCents ?? 0));
    return { ht, vat, ttc: ht + vat + (form.shippingCents ?? 0) };
  }, [form]);

  const addProductLineFromProduct = (p: any) => {
    if (!p) return;
    setForm((f: any) => ({
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
          rentalMonths: (p.sellingType === 'rental' || p.sellingType === 'location') ? 1 : undefined,
        },
      ],
    }));
  };

  const addServiceLine = (serviceId: number) => {
    const s = allServices.find((x) => x.id === serviceId);
    if (!s) return;
    setForm((f: any) => ({
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

  const updateItem = (index: number, patch: any) => {
    setForm((f: any) => ({ ...f, items: f.items.map((it: any, i: number) => (i === index ? { ...it, ...patch } : it)) }));
  };

  const removeItem = (index: number) => {
    setForm((f: any) => ({ ...f, items: f.items.filter((_: any, i: number) => i !== index) }));
  };

  const findProductItemIndex = (productId: number) =>
    (form.items ?? []).findIndex((it: any) => it.type === 'product' && it.productId === productId);

  const submit = async () => {
    if (status !== 'authenticated' || !user) {
      toast.show('Veuillez vous connecter pour enregistrer votre devis.', { variant: 'info' });
      return;
    }
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      const created = await createPublicQuote(form);
      setSavedQuote(created ?? null);
      toast.show('Devis enregistré dans votre espace.', { variant: 'success' });
      setMessage('Devis enregistré dans votre espace.');
    } catch (e: any) {
      toast.show((e?.message ?? 'Échec de la création du devis.'), { variant: 'error' });
      setError(e?.message ?? 'Échec de la création du devis.');
    } finally {
      setSaving(false);
    }
  };

  const handleDownloadPdf = async () => {
    if (status !== 'authenticated' || !user) {
      toast.show('Veuillez vous connecter pour télécharger votre devis en PDF.', { variant: 'info' });
      return;
    }

    if ((form.items ?? []).length === 0) {
      toast.show('Ajoutez au moins un élément avant de télécharger le PDF.', { variant: 'info' });
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
        setMessage('Devis enregistré dans votre espace.');
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
    } catch (e: any) {
      const messageText = e?.message ?? 'Échec de la génération du PDF.';
      toast.show(messageText, { variant: 'error' });
      setError(messageText);
    } finally {
      setSaving(false);
    }
  };

  return (
    <SiteLayout>
      <PageContainer title="Créer un devis">
        {error && <div className="register-form__alert">{error}</div>}
        {message && (
          <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
            {message}
          </div>
        )}

        <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
          <div className="md:col-span-2 space-y-6">
            <section>
              <h3 className="font-semibold mb-2">Vos informations</h3>
              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <input placeholder="Nom" value={form.customer?.name ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, name: e.target.value } })} disabled={status === 'authenticated'} />
                <input placeholder="Email" value={form.customer?.email ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, email: e.target.value } })} disabled={status === 'authenticated'} />
                <input placeholder="Entreprise (optionnel)" value={form.customer?.company ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, company: e.target.value } })} />
                <input placeholder="Adresse (optionnel)" value={form.customer?.address ?? ''} onChange={(e) => setForm({ ...form, customer: { ...form.customer, address: e.target.value } })} disabled={status === 'authenticated'} />
              </div>
            </section>

            <section>
              <h3 className="font-semibold mb-2">éléments du devis</h3>
              <div className="mb-4">
                <input
                  type="search"
                  placeholder="Rechercher un produit ou un service..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
              </div>
              {productLoading && (
                <p className="text-sm text-slate-500">Recherche des produits...</p>
              )}
              <div className="space-y-6">
                {searchQuery.trim().length >= 2 && filteredServices.length > 0 && (
                  <div>
                    <h2 className="text-lg font-semibold mb-2">Services disponibles ({filteredServices.length})</h2>
                    <div className="space-y-2 max-h-64 overflow-auto">
                      {filteredServices.map((s) => (
                        <div key={s.id} className="rounded border border-slate-200 p-2">
                          <div className="flex items-center justify-between">
                            <div>
                              <div className="text-sm font-semibold">{s.title}</div>
                              <div className="text-xs text-slate-500">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format((s.priceCents ?? 0) / 100)}</div>
                            </div>
                            <button
                              type="button"
                              className={(form.items ?? []).some((it: any) => it.type === 'service' && it.serviceId === s.id)
                                ? 'catalog-admin-actions__delete'
                                : 'register-form__submit'}
                              onClick={() => {
                                const exists = (form.items ?? []).some((it: any) => it.type === 'service' && it.serviceId === s.id);
                                if (exists) {
                                  setForm((f: any) => ({
                                    ...f,
                                    items: f.items.filter((it: any) => !(it.type === 'service' && it.serviceId === s.id)),
                                  }));
                                } else {
                                  addServiceLine(s.id);
                                }
                              }}
                            >
                              {(form.items ?? []).some((it: any) => it.type === 'service' && it.serviceId === s.id) ? 'Retirer' : 'Ajouter'}
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
                {products.length > 0 && (
                  <div>
                    <h2 className="text-lg font-semibold mb-2">Produits trouvés ({Math.min(products.length, 20)})</h2>
                    <div className="space-y-2 max-h-64 overflow-auto">
                      {products.slice(0, 20).map((p) => (
                        <div key={p.id} className="rounded border border-slate-200 p-2">
                          <div className="flex items-center justify-between gap-3">
                            <div>
                              <div className="text-sm font-semibold">{p.name}</div>
                              <div className="text-xs text-slate-500">Référence: {p.sku}</div>
                              <div className="text-xs text-slate-500">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(((p.effectivePriceCents ?? p.priceCents) ?? 0) / 100)}{(p.sellingType === 'rental' || p.sellingType === 'location') ? ' / mois' : ''}</div>
                            </div>
                            {p.sellingType === 'rental' || p.sellingType === 'location' ? (
                              <button
                                type="button"
                                className="register-form__submit"
                                onClick={() => {
                                  const exists = (form.items ?? []).some(
                                    (it: any) => it.type === 'product' && it.productId === p.id,
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
                                className="register-form__submit"
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
              <table className="catalog-admin-table">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Quantité</th>
                    <th>Prix HT</th>
                    <th>TVA %</th>
                    <th>Remise</th>
                    <th>Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {form.items.map((it: any, index: number) => {
                    const isRental = it.sellingType === 'rental' || it.sellingType === 'location';
                    const months = isRental ? Math.max(1, (it as any).rentalMonths ?? 1) : 1;
                    const line = Math.max(0, (it.unitPriceCents ?? 0) * (it.quantity ?? 1) * months - (it.discountCents ?? 0));
                    return (
                      <tr key={index}>
                        <td>{it.name}</td>
                        <td>
                          <div className="flex flex-col items-start gap-2">
                            <div className="inline-flex items-center gap-2">
                            <button
                              type="button"
                              aria-label="Diminuer la quantité"
                              className="px-2 py-1 border rounded"
                              onClick={() =>
                                updateItem(index, { quantity: Math.max(1, (it.quantity ?? 1) - 1) })
                              }
                            >
                              -
                            </button>
                            <input
                              type="number"
                              min={1}
                              className="w-16 text-center border rounded py-1"
                              value={it.quantity ?? 1}
                              onChange={(e) => {
                                const v = parseInt(e.target.value, 10);
                                updateItem(index, { quantity: Number.isNaN(v) ? 1 : Math.max(1, v) });
                              }}
                            />
                            <button
                              type="button"
                              aria-label="Augmenter la quantité"
                              className="px-2 py-1 border rounded"
                              onClick={() =>
                                updateItem(index, { quantity: Math.max(1, (it.quantity ?? 1) + 1) })
                              }
                            >
                              +
                            </button>
                            </div>
                            {isRental && (
                              <div className="inline-flex items-center gap-2 text-sm text-slate-600">
                                <span>Mois</span>
                                <button
                                  type="button"
                                  aria-label="Diminuer le nombre de mois"
                                  className="px-2 py-1 border rounded"
                                  onClick={() =>
                                    updateItem(index, { rentalMonths: Math.max(1, ((it as any).rentalMonths ?? 1) - 1) })
                                  }
                                >
                                  -
                                </button>
                                <input
                                  type="number"
                                  min={1}
                                  className="w-16 text-center border rounded py-1"
                                  value={Math.max(1, (it as any).rentalMonths ?? 1)}
                                  onChange={(e) => {
                                    const v = parseInt(e.target.value, 10);
                                    updateItem(index, { rentalMonths: Number.isNaN(v) ? 1 : Math.max(1, v) });
                                  }}
                                />
                                <button
                                  type="button"
                                  aria-label="Augmenter le nombre de mois"
                                  className="px-2 py-1 border rounded"
                                  onClick={() =>
                                    updateItem(index, { rentalMonths: Math.max(1, ((it as any).rentalMonths ?? 1) + 1) })
                                  }
                                >
                                  +
                                </button>
                              </div>
                            )}
                          </div>
                        </td>
                        <td>
                          {formatPrice(it.unitPriceCents)}
                          {(it.sellingType === 'rental' || it.sellingType === 'location') ? ' / mois' : ''}
                        </td>
                        <td>{(it.vatRate ?? 0).toString()}%</td>
                        <td>{formatPrice(it.discountCents ?? 0)}</td>
                        <td>
                          {formatPrice(line)}
                        </td>
                        <td>
                          <button type="button" className="catalog-admin-actions__delete" onClick={() => removeItem(index)}>
                            Retirer
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </section>
          </div>

          <div className="space-y-6">
            <section>
              <h3 className="font-semibold mb-2">Total</h3>
              <div className="space-y-1 mb-3 text-sm text-slate-600">
                <div className="flex justify-between"><span>Début de validité</span><strong>{formatDate(form.validFrom)}</strong></div>
                <div className="flex justify-between"><span>Fin de validité</span><strong>{formatDate(form.validUntil)}</strong></div>
              </div>
              <div className="flex justify-between"><span>Remise globale</span><strong>{formatPrice(form.discountCents ?? 0)}</strong></div>
              <div className="space-y-1">
                <div className="flex justify-between"><span>Total HT</span><strong>{formatPrice(totals.ht)}</strong></div>
                <div className="flex justify-between"><span>TVA</span><strong>{formatPrice(totals.vat)}</strong></div>
                <div className="flex justify-between"><span>TTC</span><strong>{formatPrice(totals.ttc)}</strong></div>
              </div>
            </section>

            <div className="flex items-center gap-3">
              <button type="button" className="register-form__submit" onClick={() => void submit()} disabled={saving}>
                {saving ? 'Enregistrement...' : 'Enregistrer'}
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


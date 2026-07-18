import { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createAdminQuote,
  fetchAdminQuote,
  fetchAdminQuoteServices,
  updateAdminQuote,
  generateAdminQuotePdf,
} from '@/features/quotes/api';
import { fetchAdminProducts } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

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

type Item = {
  id?: number;
  type: 'service' | 'product' | 'custom';
  productId?: number | null;
  serviceId?: number | null;
  name: string;
  description?: string | null;
  unit?: string | null;
  quantity: number;
  unitPriceCents: number;
  vatRate: number;
  discountCents?: number;
  rentalMonths?: number; // UI-only for rental duration
};


const adaptQuoteForSave = (source: any) => {
  if (!source) return source;
  const items = (source.items as Item[] | undefined) ?? [];
  return {
    ...source,
    items: items.map((item) => {
      if (item.type !== 'product' || !item.rentalMonths) {
        const { rentalMonths, ...rest } = item;
        return rest;
      }
      const months = Math.max(1, item.rentalMonths);
      const baseDescription = item.description?.trim();
      const { rentalMonths, ...rest } = item;
      return {
        ...rest,
        description: baseDescription && baseDescription.length > 0
          ? `${baseDescription} - Durée: ${months} mois`
          : `Durée: ${months} mois`,
        unit: item.unit ?? 'mois',
        quantity: Math.max(1, item.quantity ?? 1) * months,
      };
    }),
  };
};


export const QuoteFormPage = () => {
  const toast = useToast();
  useDocumentTitle('Admin - Devis');
  const params = useParams();
  const navigate = useNavigate();

  const isNew = params.quoteId === 'new' || !params.quoteId;
  const [quote, setQuote] = useState<any | null>(null);
  const [services, setServices] = useState<any[]>([]);
  const [products, setProducts] = useState<any[]>([]);
  const [rentalDialogOpen, setRentalDialogOpen] = useState(false);
  const [rentalCandidate, setRentalCandidate] = useState<any | null>(null);
  // Recherche unifiée produits / services
  const [searchQuery, setSearchQuery] = useState('');
  const trimmedSearchQuery = searchQuery.trim().toLowerCase();
  const filteredServices = useMemo(() => {
    if (trimmedSearchQuery === '') return [];
    return services
      .filter((s: any) => s.title.toLowerCase().includes(trimmedSearchQuery))
      .slice(0, 20);
  }, [services, trimmedSearchQuery]);
  const filteredProducts = useMemo(() => {
    if (trimmedSearchQuery === '') return [];
    return products
      .filter((p: any) => p.name.toLowerCase().includes(trimmedSearchQuery))
      .slice(0, 20);
  }, [products, trimmedSearchQuery]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [svc, prods, q] = await Promise.all([
        fetchAdminQuoteServices(),
        fetchAdminProducts(),
        isNew ? Promise.resolve(null) : fetchAdminQuote(Number(params.quoteId)),
      ]);
      setServices(svc);
      setProducts(prods);
      setQuote(
        q
          ? {
              ...createDefaultValidity(),
              ...q,
              conditions: q.conditions ?? DEFAULT_QUOTE_CONDITIONS,
              validFrom: q.validFrom ?? createDefaultValidity().validFrom,
              validUntil: q.validUntil ?? createDefaultValidity().validUntil,
            }
          : {
              status: 'draft',
              customer: {},
              items: [],
              discountCents: 0,
              shippingCents: 0,
              conditions: DEFAULT_QUOTE_CONDITIONS,
              ...createDefaultValidity(),
            },
      );
    } catch (e: any) {
      const msg = e?.message ?? 'Échec de sauvegarde.';
      setError(msg);
      try { toast.show(msg, { variant: 'error' }); } catch {}
    } finally {
      setLoading(false);
    }
  }, [isNew, params.quoteId]);

  useEffect(() => {
    void load();
  }, [load]);

  const total = useMemo(() => {
    if (!quote) return { ht: 0, vat: 0, ttc: 0 };
    let ht = 0;
    let vat = 0;
    for (const it of quote.items as Item[]) {
      const isRental = it.type === 'product' && products.some((p: any) => p.id === it.productId && (p.sellingType === 'rental' || p.sellingType === 'location'));
      const months = isRental ? Math.max(1, it.rentalMonths ?? 1) : 1;
      const line = Math.max(0, it.unitPriceCents * it.quantity * months - (it.discountCents ?? 0));
      ht += line;
      vat += Math.round(line * (it.vatRate / 100));
    }
    ht = Math.max(0, ht - (quote.discountCents ?? 0));
    const ttc = ht + vat + (quote.shippingCents ?? 0);
    return { ht, vat, ttc };
  }, [quote, products]);

  const addItemFromService = (serviceId: number) => {
    const svc = services.find((s) => s.id === serviceId);
    if (!svc) return;
    setQuote((q: any) => {
      const idx = q.items.findIndex((it: Item) => it.type === 'service' && it.serviceId === svc.id);
      if (idx >= 0) {
        const next = [...q.items];
        next[idx] = { ...next[idx], quantity: (next[idx].quantity ?? 1) + 1 };
        return { ...q, items: next };
      }
      const it: Item = {
        type: 'service',
        serviceId: svc.id,
        name: svc.title,
        description: svc.description ?? undefined,
        unit: svc.unit ?? undefined,
        quantity: 1,
        unitPriceCents: svc.priceCents,
        vatRate: Number(svc.vatRate ?? 0),
        discountCents: 0,
      };
      return { ...q, items: [...q.items, it] };
    });
  };

  const addItemFromProduct = (productId: number) => {
    const p = products.find((x: any) => x.id === productId);
    if (!p) return;
    setQuote((q: any) => {
      const isRental = (p.sellingType === 'rental' || p.sellingType === 'location');
      if (isRental) {
        const it: Item = {
          type: 'product',
          productId: p.id,
          name: p.name,
          description: p.shortDescription ?? undefined,
          unit: undefined,
          quantity: 1,
          unitPriceCents: (p.effectivePriceCents ?? p.priceCents),
          vatRate: 20,
          discountCents: 0,
          rentalMonths: 1,
        };
        return { ...q, items: [...q.items, it] };
      }
      const idx = q.items.findIndex((it: Item) => it.type === 'product' && it.productId === p.id);
      if (idx >= 0) {
        const next = [...q.items];
        next[idx] = { ...next[idx], quantity: (next[idx].quantity ?? 1) + 1 };
        return { ...q, items: next };
      }
      const it: Item = {
        type: 'product',
        productId: p.id,
        name: p.name,
        description: p.shortDescription ?? undefined,
        unit: undefined,
        quantity: 1,
        unitPriceCents: (p.effectivePriceCents ?? p.priceCents),
        vatRate: 20, // par défaut si TVA non définie dans le produit
        discountCents: 0,
      };
      return { ...q, items: [...q.items, it] };
    });
  };

  const updateItem = (index: number, patch: Partial<Item>) => {
    setQuote((q: any) => ({
      ...q,
      items: q.items.map((it: Item, i: number) => (i === index ? { ...it, ...patch } : it)),
    }));
  };

  const removeItem = (index: number) => {
    setQuote((q: any) => ({
      ...q,
      items: q.items.filter((_: any, i: number) => i !== index),
    }));
  };

  const save = async () => {
    if (!quote) return;
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      let saved: any;
      const payload = adaptQuoteForSave(quote);
      if (isNew) {
        saved = await createAdminQuote(payload);
        navigate(`/admin/quotes/${saved.id}/edit`, { replace: true });
      } else {
        saved = await updateAdminQuote(Number(params.quoteId), payload);
      }
      setQuote(saved);
      const emailNotificationSent = saved?.emailNotificationSent === true;
      const emailNotificationError = typeof saved?.emailNotificationError === 'string' ? saved.emailNotificationError : null;
      const successMessage = emailNotificationSent
        ? 'Devis enregistré. Email automatique envoyé au client.'
        : emailNotificationError
          ? `Devis enregistré. Email automatique non envoyé : ${emailNotificationError}`
          : 'Devis enregistré.';
      setMessage(successMessage);
      try { toast.show(successMessage, { variant: emailNotificationSent ? 'success' : emailNotificationError ? 'info' : 'success' }); } catch {}
    } catch (e: any) {
      const msg = e?.message ?? 'Échec de sauvegarde.';
      setError(msg);
      try { toast.show(msg, { variant: 'error' }); } catch {}
    } finally {
      setSaving(false);
    }
  };

  const handleGeneratePdf = async () => {
    if (!quote?.id) return;
    try {
      const blob = await generateAdminQuotePdf(quote.id);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${quote.number ?? 'devis'}.pdf`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    } catch (e: any) {
      alert(e?.message ?? "Impossible de générer le PDF.");
    }
  };

  return (
    <PageContainer
      title={quote?.number ? `Devis ${quote.number}` : 'Nouveau devis'}
      headerActions={
        !isNew ? (
          <div className="catalog-admin-actions">
            <button type="button" className="catalog-admin-actions__edit" onClick={() => void handleGeneratePdf()}>
              Télécharger
            </button>
          </div>
        ) : undefined
      }
    >
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading || !quote ? (
        <p className="muted">Chargement...</p>
      ) : (
        <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
          <div className="md:col-span-2 space-y-6">
            <section>
              <h3 className="font-semibold mb-2">Client</h3>
              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <input placeholder="Nom" value={quote.customer?.name ?? ''} onChange={(e) => setQuote({ ...quote, customer: { ...quote.customer, name: e.target.value } })} />
                <input placeholder="Email" value={quote.customer?.email ?? ''} onChange={(e) => setQuote({ ...quote, customer: { ...quote.customer, email: e.target.value } })} />
                <input placeholder="Entreprise" value={quote.customer?.company ?? ''} onChange={(e) => setQuote({ ...quote, customer: { ...quote.customer, company: e.target.value } })} />
                <input placeholder="Adresse" value={quote.customer?.address ?? ''} onChange={(e) => setQuote({ ...quote, customer: { ...quote.customer, address: e.target.value } })} />
              </div>
            </section>

            <section>
              <h3 className="font-semibold mb-2">Éléments du devis</h3>
              <div className="mb-4 space-y-2">
                <input
                  type="search"
                  placeholder="Rechercher un produit ou un service..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
                {trimmedSearchQuery === '' && (
                  <p className="text-sm text-slate-500">
                    Lance une recherche pour afficher les produits et services à ajouter au devis.
                  </p>
                )}
              </div>
              <div className="space-y-6">
                {filteredServices.length > 0 && (
                  <div>
                    <h2 className="text-lg font-semibold mb-2">Services ({filteredServices.length})</h2>
                    <div className="space-y-2 max-h-64 overflow-auto">
                      {filteredServices.map((s: any) => (
                        <div key={s.id} className="rounded border border-slate-200 p-2">
                          <div className="flex items-center justify-between">
                            <div>
                              <div className="text-sm font-semibold">{s.title}</div>
                              <div className="text-xs text-slate-500">{(s.priceCents != null) ? `${(new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(s.priceCents / 100))}` : ''}</div>
                            </div>
                            {quote?.items?.some((it: Item) => it.type === 'service' && it.serviceId === s.id) ? (
                              <button
                                type="button"
                                className="catalog-admin-actions__delete"
                                onClick={() =>
                                  setQuote((q: any) => ({
                                    ...q,
                                    items: q.items.filter(
                                      (it: Item) => !(it.type === 'service' && it.serviceId === s.id),
                                    ),
                                  }))
                                }
                              >
                                Retirer
                              </button>
                            ) : (
                              <button
                                type="button"
                                className="catalog-admin-actions__edit"
                                onClick={() => addItemFromService(s.id)}
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
                {filteredProducts.length > 0 && (
                  <div>
                    <h2 className="text-lg font-semibold mb-2">Produits ({filteredProducts.length})</h2>
                    <div className="space-y-2 max-h-64 overflow-auto">
                      {filteredProducts.map((p: any) => (
                        <div key={p.id} className="rounded border border-slate-200 p-2">
                          <div className="flex items-center justify-between">
                            <div>
                              <div className="text-sm font-semibold">{p.name}</div>
                              <div className="text-xs text-slate-500">Référence: {p.sku}</div>
                              <div className="text-xs text-slate-500">
                                {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format((p.effectivePriceCents ?? p.priceCents) / 100)}
                                {(p.sellingType === 'rental' || p.sellingType === 'location') ? ' / mois' : ''}
                              </div>
                            </div>
                            {(p.sellingType === 'rental' || p.sellingType === 'location') ? (
                              <button
                                type="button"
                                className="catalog-admin-actions__edit"
                                onClick={() => {
                                  const exists = quote?.items?.some((it: Item) => it.type === 'product' && it.productId === p.id);
                                  if (exists) {
                                    setRentalCandidate(p);
                                    setRentalDialogOpen(true);
                                  } else {
                                    // Première fois : ajouter directement une nouvelle ligne
                                    setQuote((q: any) => ({
                                      ...q,
                                      items: [
                                        ...q.items,
                                        {
                                          type: 'product',
                                          productId: p.id,
                                          name: p.name,
                                          description: p.shortDescription ?? undefined,
                                          unit: undefined,
                                          quantity: 1,
                                          unitPriceCents: (p.effectivePriceCents ?? p.priceCents),
                                          vatRate: 20,
                                          discountCents: 0,
                                          rentalMonths: 1,
                                        } as Item,
                                      ],
                                    }));
                                  }
                                }}
                              >
                                Ajouter
                              </button>
                            ) : quote?.items?.some((it: Item) => it.type === 'product' && it.productId === p.id) ? (
                              <button
                                type="button"
                                className="catalog-admin-actions__delete"
                                onClick={() =>
                                  setQuote((q: any) => ({
                                    ...q,
                                    items: q.items.filter(
                                      (it: Item) => !(it.type === 'product' && it.productId === p.id),
                                    ),
                                  }))
                                }
                              >
                                Retirer
                              </button>
                            ) : (
                              <button
                                type="button"
                                className="catalog-admin-actions__edit"
                                onClick={() => addItemFromProduct(p.id)}
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

              <div className="overflow-x-auto">
              <table className="catalog-admin-table">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Quantité</th>
                    <th>Prix HT</th>
                    <th>TVA %</th>
                    <th>Remise</th>
                    <th>Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {quote.items.map((it: Item, index: number) => {
                    const isRental =
                      it.type === 'product' && products.some((p: any) => p.id === it.productId && (p.sellingType === 'rental' || p.sellingType === 'location'));
                    const months = isRental ? Math.max(1, (it as any).rentalMonths ?? 1) : 1;
                    const line = Math.max(0, it.unitPriceCents * it.quantity * months - (it.discountCents ?? 0));
                    return (
                      <tr key={index}>
  <td>
    {it.name}
    {isRental && (
      <span className="catalog-badge" style={{ marginLeft: 6 }}>Location</span>
    )}
  </td>
  <td>{it.description ?? ''}</td>
  <td>
    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start', gap: 6 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
        <button
          type="button"
          className="catalog-admin-actions__edit"
        onClick={() => {
          const next = (it.quantity ?? 1) - 1;
          if (next <= 0) {
            removeItem(index);
          } else {
            updateItem(index, { quantity: Math.min(9999, next) });
          }
        }}
        aria-label="Diminuer la quantité"
      >
        -
      </button>
      <input
        type="number"
        min={0}
        step={1}
        max={9999}
        value={it.quantity}
        onChange={(e) => {
          const val = Number.parseInt(e.target.value, 10);
          if (Number.isNaN(val) || val <= 0) {
            removeItem(index);
          } else {
            updateItem(index, { quantity: Math.min(9999, val) });
          }
        }}
        style={{ width: 64 }}
      />
      <button
        type="button"
        className="catalog-admin-actions__edit"
        onClick={() => updateItem(index, { quantity: Math.min(9999, (it.quantity ?? 1) + 1) })}
        aria-label="Augmenter la quantité"
      >
        +
      </button>
      </div>
      {isRental && (
        <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
          <span className="muted">Mois</span>
          <button
            type="button"
            className="catalog-admin-actions__edit"
            onClick={() => updateItem(index, { rentalMonths: Math.max(1, ((it as any).rentalMonths ?? 1) - 1) })}
            aria-label="Diminuer le nombre de mois"
          >
            -
          </button>
          <input
            type="number"
            min={1}
            step={1}
            max={120}
            value={Math.max(1, (it as any).rentalMonths ?? 1)}
            onChange={(e) => {
              const val = Number.parseInt(e.target.value, 10);
              updateItem(index, { rentalMonths: Number.isNaN(val) ? 1 : Math.max(1, Math.min(120, val)) } as any);
            }}
            style={{ width: 64 }}
          />
          <button
            type="button"
            className="catalog-admin-actions__edit"
            onClick={() => updateItem(index, { rentalMonths: Math.min(120, ((it as any).rentalMonths ?? 1) + 1) })}
            aria-label="Augmenter le nombre de mois"
          >
            +
          </button>
        </div>
      )}
    </div>
  </td>
  <td>
    {formatPrice(it.unitPriceCents)}{isRental ? ' / mois' : ''}
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
              </div>
            </section>
          </div>

          <div className="space-y-6">
            <section>
              <h3 className="font-semibold mb-2">Paramètres</h3>
              <div className="space-y-2">
                <label className="flex items-center gap-2">
                  Statut
                  <select value={quote.status} onChange={(e) => setQuote({ ...quote, status: e.target.value })}>
                    <option value="draft">Brouillon</option>
                    <option value="sent">Envoyé</option>
                    <option value="accepted">Accepté</option>
                    <option value="refused">Refusé</option>
                    <option value="expired">Expiré</option>
                  </select>
                </label>
                <label className="flex items-center gap-2">
                  Remise globale
                  <input
                    type="number"
                    min={0}
                    step="0.01"
                    value={((quote.discountCents ?? 0) / 100).toFixed(2)}
                    onChange={(e) => setQuote({ ...quote, discountCents: Math.max(0, Math.round(Number(e.target.value.replace(',', '.')) * 100)) })}
                  />
                </label>
                <label className="flex items-center gap-2">
                  Frais de port
                  <input
                    type="number"
                    min={0}
                    step="0.01"
                    value={((quote.shippingCents ?? 0) / 100).toFixed(2)}
                    onChange={(e) => setQuote({ ...quote, shippingCents: Math.max(0, Math.round(Number(e.target.value.replace(',', '.')) * 100)) })}
                  />
                </label>
                <label className="flex items-center gap-2">
                  Début de validité
                  <input
                    type="date"
                    value={quote.validFrom ?? ''}
                    onChange={(e) => setQuote({ ...quote, validFrom: e.target.value })}
                  />
                </label>
                <label className="flex items-center gap-2">
                  Fin de validité
                  <input
                    type="date"
                    value={quote.validUntil ?? ''}
                    onChange={(e) => setQuote({ ...quote, validUntil: e.target.value })}
                  />
                </label>
                <label className="flex flex-col gap-2">
                  Conditions
                  <textarea rows={7} value={quote.conditions ?? ''} onChange={(e) => setQuote({ ...quote, conditions: e.target.value })} />
                </label>
              </div>
            </section>

            <section>
              <h3 className="font-semibold mb-2">Total</h3>
              <div className="space-y-1">
                <div className="flex justify-between"><span>Total HT</span><strong>{formatPrice(total.ht)}</strong></div>
                <div className="flex justify-between"><span>TVA</span><strong>{formatPrice(total.vat)}</strong></div>
                <div className="flex justify-between"><span>TTC</span><strong>{formatPrice(total.ttc)}</strong></div>
              </div>
            </section>
          </div>
        </div>
      )}

      {!loading && quote ? (
        <div className="mt-8 flex items-center justify-end gap-3">
          {!isNew && (
            <button type="button" className="catalog-admin-actions__edit" onClick={() => void handleGeneratePdf()}>
              Télécharger
            </button>
          )}
          <button type="button" className="register-form__submit" onClick={() => void save()} disabled={saving}>
            {saving ? 'Sauvegarde...' : 'Enregistrer'}
          </button>
        </div>
      ) : null}

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
            // Ajouter une nouvelle ligne (pas d'incrément de quantité)
            const p = rentalCandidate;
            setQuote((q: any) => ({
              ...q,
              items: [
                ...q.items,
                {
                  type: 'product',
                  productId: p.id,
                  name: p.name,
                  description: p.shortDescription ?? undefined,
                  unit: undefined,
                  quantity: 1,
                  unitPriceCents: (p.effectivePriceCents ?? p.priceCents),
                  vatRate: 20,
                  discountCents: 0,
                  rentalMonths: 1,
                } as Item,
              ],
            }));
          }
          setRentalDialogOpen(false);
          setRentalCandidate(null);
        }}
      />
    </PageContainer>
  );
};









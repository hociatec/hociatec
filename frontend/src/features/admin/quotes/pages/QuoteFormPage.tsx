import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  createAdminQuote,
  fetchAdminQuote,
  fetchAdminQuoteServices,
  fetchAdminQuotes,
  sendAdminQuoteEmail,
  updateAdminQuote,
  generateAdminQuotePdf,
  deleteAdminQuote,
} from '@/features/quotes/api';
import { fetchAdminProducts } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(cents / 100);

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

export const QuoteFormPage = () => {
  const toast = useToast();
  useDocumentTitle('Admin - Devis');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const params = useParams();
  const navigate = useNavigate();

  const isNew = params.quoteId === 'new' || !params.quoteId;
  const [quote, setQuote] = useState<any | null>(null);
  const [services, setServices] = useState<any[]>([]);
  const [products, setProducts] = useState<any[]>([]);
  const [rentalDialogOpen, setRentalDialogOpen] = useState(false);
  const [rentalCandidate, setRentalCandidate] = useState<any | null>(null);
  const filteredServices = useMemo(
    () =>
      services
        .filter((s: any) => s.title.toLowerCase().includes(searchQuery.trim().toLowerCase()))
        .slice(0, 20),
    [services, searchQuery],
  );
  const filteredProducts = useMemo(
    () =>
      products
        .filter((p: any) => p.name.toLowerCase().includes(searchQuery.trim().toLowerCase()))
        .slice(0, 20),
    [products, searchQuery],
  );
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const adapted: any = {
        ...quote,
        items: (quote.items as Item[]).map((it) => {
          const isRental = it.type === 'product' && products.some((p: any) => p.id === it.productId && (p.sellingType === 'rental' || p.sellingType === 'location'));
          if (!isRental) return it as any;
          const months = Math.max(1, it.rentalMonths ?? 1);
          const desc = it.description ? `${it.description} — Durée: ${months} mois` : `Durée: ${months} mois`;
          return {
            ...it,
            description: desc,
            unit: 'mois',
            quantity: Math.max(1, it.quantity) * months,
          } as any;
        }),
      };
      const [svc, prods, q] = await Promise.all([
        fetchAdminQuoteServices(),
        fetchAdminProducts(),
        isNew ? Promise.resolve(null) : fetchAdminQuote(Number(params.quoteId)),
      ]);
      setServices(svc);
      setProducts(prods);
      setQuote(
        q ?? {
          status: 'draft',
          customer: {},
          items: [],
          discountCents: 0,
          shippingCents: 0,
          conditions: '',
        },
      );
    } catch (e: any) {
      const msg = e?.message ?? 'Echec de sauvegarde.';
      setError(msg);
      try { toast.show(msg, { variant: 'error' }); } catch {}
    } finally {
      setLoading(false);
    }
  }, [isNew, params.quoteId]);

  useEffect(() => {
    if (!isAdmin) return;
    void load();
  }, [isAdmin, load]);

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
        vatRate: 20, // par defaut si TVA non definie dans le produit
        discountCents: 0,
      };
      return { ...q, items: [...q.items, it] };
    });
  };
  // Recherche unifiÃ©e produits / services
  const [searchQuery, setSearchQuery] = useState('');

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
      if (isNew) {
        saved = await createAdminQuote(adapted);
        navigate(`/admin/quotes/${saved.id}/edit`, { replace: true });
      } else {
        saved = await updateAdminQuote(Number(params.quoteId), adapted);
      }
      setQuote(saved);
      setMessage('Enregistré.');
      try { toast.show('Devis enregistré.', { variant: 'success' }); } catch {}
    } catch (e: any) {
      const msg = e?.message ?? 'Echec de sauvegarde.';
      setError(msg);
      try { toast.show(msg, { variant: 'error' }); } catch {}
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!quote?.id) return;
    if (!window.confirm('Supprimer ce devis ?')) return;
    await deleteAdminQuote(quote.id);
    navigate('/admin/quotes');
  };

  const handleSendEmail = async () => {
    if (!quote?.id) return;
    const to = window.prompt("Destinataire (email)", quote.customer?.email ?? '') ?? undefined;
    try {
      const resp = await sendAdminQuoteEmail(quote.id, to);
      alert(resp?.message ?? 'E-mail envoyé.');
    } catch {}
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

  if (guardLoading) {
    return (
      <PageContainer title="Devis">
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }
  if (!isAdmin) {
    return (
      <PageContainer title="Devis">
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={quote?.number ? `Devis ${quote.number}` : 'Nouveau devis'}
      headerActions={
        <div className="catalog-admin-actions">
          <button type="button" className="catalog-admin-actions__edit" onClick={() => void save()} disabled={saving}>
            {saving ? 'Sauvegarde...' : 'Enregistrer'}
          </button>
          {!isNew && (
            <>
              <button type="button" className="catalog-admin-actions__edit" onClick={() => void handleGeneratePdf()}>
                Télécharger
              </button>
              
              <button type="button" className="catalog-admin-actions__delete" onClick={() => void handleDelete()}>
                Supprimer
              </button>
            </>
          )}
        </div>
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
              <h3 className="font-semibold mb-2">Ã‰lÃ©ments du devis</h3>
              <div className="mb-4">
                <input
                  type="search"
                  placeholder="Rechercher un produit ou un service..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
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
                              <div className="text-xs text-slate-500">RÃ©fÃ©rence: {p.sku}</div>
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
                <label className="flex flex-col gap-2">
                  Conditions
                  <textarea rows={5} value={quote.conditions ?? ''} onChange={(e) => setQuote({ ...quote, conditions: e.target.value })} />
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














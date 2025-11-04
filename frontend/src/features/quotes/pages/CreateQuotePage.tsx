import { useEffect, useMemo, useRef, useState } from 'react';
import { createPublicQuote, fetchPublicQuoteServices } from '@/features/quotes/api';
import { fetchPublicProducts } from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useToast } from '@/shared/components/ui/toast';

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(cents / 100);

export const CreateQuotePage = () => {
  useDocumentTitle('Créer un devis');
  const { user, status } = useAuth();
  const toast = useToast();
  const [form, setForm] = useState<any>({ customer: {}, items: [], discountCents: 0, shippingCents: 0 });
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
      toast.show('Devis enregistre dans votre espace.', { variant: 'success' });
      setMessage('Devis enregistrÃ© dans votre espace.');
    } catch (e: any) {
      toast.show((e?.message ?? 'Echec de la creation du devis.'), { variant: 'error' });
      setError(e?.message ?? 'Ã‰chec de la crÃ©ation du devis.');
    } finally {
      setSaving(false);
    }
  };

  const printPdf = () => {
    const data = {
      number: savedQuote?.number ?? '(Brouillon)',
      customer: form.customer ?? {},
      items: form.items ?? [],
      totals,
    } as any;

    const rows = (data.items as any[]).map((it) => {
      const isRental = it.sellingType === 'rental' || it.sellingType === 'location';
      const months = isRental ? Math.max(1, (it as any).rentalMonths ?? 1) : 1;
      const line = Math.max(0, (it.unitPriceCents ?? 0) * (it.quantity ?? 1) * months - (it.discountCents ?? 0));
      const price = formatPrice(it.unitPriceCents ?? 0) + (isRental ? ' / mois' : '');
      const qtyStr = `${it.quantity ?? 1}${isRental ? ' Ã— ' + months + 'm' : ''}`;
      const lineStr = formatPrice(line);
      return `<tr><td>${it.name ?? ''}</td><td style="text-align:center;">${qtyStr}</td><td style="text-align:right;">${price}</td><td style="text-align:right;">${(it.vatRate ?? 0)}%</td><td style="text-align:right;">${formatPrice(it.discountCents ?? 0)}</td><td style="text-align:right;">${lineStr}</td></tr>`;
    }).join('');

    const html = `<!doctype html>
<html><head><meta charset="utf-8"/><title>Devis ${data.number}</title>
<style>
  body{font-family:Arial,sans-serif;padding:24px;color:#111}
  h1{font-size:20px;margin:0 0 8px}
  .meta{color:#475569;margin-bottom:16px}
  table{width:100%;border-collapse:collapse;margin-top:12px}
  th,td{border:1px solid #e2e8f0;padding:8px;font-size:12px}
  th{background:#f8fafc;text-align:left}
  tfoot td{font-weight:bold}
</style></head><body>
  <h1>Devis ${data.number}</h1>
  <div class="meta">
    ${data.customer?.name ?? ''}${data.customer?.email ? ' - ' + data.customer.email : ''}
  </div>
  <table>
    <thead><tr><th>Nom</th><th>QtÃ©</th><th>Prix HT</th><th>TVA %</th><th>Remise</th><th>Total</th></tr></thead>
    <tbody>${rows}</tbody>
    <tfoot>
      <tr><td colspan="5">Total HT</td><td style="text-align:right;">${formatPrice(totals.ht)}</td></tr>
      <tr><td colspan="5">TVA</td><td style="text-align:right;">${formatPrice(totals.vat)}</td></tr>
      <tr><td colspan="5">TTC</td><td style="text-align:right;">${formatPrice(totals.ttc)}</td></tr>
    </tfoot>
  </table>
</body></html>`;

    const win = window.open('', '_blank');
    if (!win) return;
    win.document.open();
    win.document.write(html);
    win.document.close();
    win.focus();
    win.print();
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
              <div className="space-y-6">
                {filteredServices.length > 0 && (
                  <div>
                    <h2 className="text-lg font-semibold mb-2">Services ({filteredServices.length})</h2>
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
                    <h2 className="text-lg font-semibold mb-2">Produits ({Math.min(products.length, 20)})</h2>
                    <div className="space-y-2 max-h-64 overflow-auto">
                      {products.slice(0, 20).map((p) => (
                        <div key={p.id} className="rounded border border-slate-200 p-2">
                          <div className="flex items-center justify-between">
                            <div>
                              <div className="text-sm font-semibold">{p.name}</div>
                              <div className="text-xs text-slate-500">RÃ©fÃ©rence: {p.sku}</div>
                              <div className="text-xs text-slate-500">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(((p.effectivePriceCents ?? p.priceCents) ?? 0) / 100)}{(p.sellingType === 'rental' || p.sellingType === 'location') ? ' / mois' : ''}</div>
                            </div>
                            <button
                              type="button"
                              className={(p.sellingType === 'rental' || p.sellingType === 'location')
                                ? 'register-form__submit'
                                : ((form.items ?? []).some((it: any) => it.type === 'product' && it.productId === p.id)
                                    ? 'catalog-admin-actions__delete'
                                    : 'register-form__submit')}
                              onClick={() => {
                                if (p.sellingType === 'rental' || p.sellingType === 'location') {
                                  const exists = (form.items ?? []).some(
                                    (it: any) => it.type === 'product' && it.productId === p.id,
                                  );
                                  if (exists) {
                                    // Demander confirmation uniquement si une ligne pour ce produit existe dÃ©jÃ 
                                    setRentalCandidate(p);
                                    setRentalDialogOpen(true);
                                  } else {
                                    // PremiÃ¨re fois: ajouter directement une nouvelle ligne (pas de dialogue)
                                    addProductLineFromProduct(p);
                                  }
                                } else {
                                  const exists = (form.items ?? []).some((it: any) => it.type === 'product' && it.productId === p.id);
                                  if (exists) {
                                    setForm((f: any) => ({
                                      ...f,
                                      items: f.items.filter((it: any) => !(it.type === 'product' && it.productId === p.id)),
                                    }));
                                  } else {
                                    addProductLineFromProduct(p);
                                  }
                                }
                              }}
                            >
                              {(p.sellingType === 'rental' || p.sellingType === 'location')
                                ? 'Ajouter'
                                : ((form.items ?? []).some((it: any) => it.type === 'product' && it.productId === p.id)
                                    ? 'Retirer'
                                    : 'Ajouter')}
                            </button>
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
                              aria-label="Diminuer la quantitÃ©"
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
                              aria-label="Augmenter la quantitÃ©"
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
              <button type="button" className="hero__button hero__button--ghost" onClick={() => printPdf()} disabled={(form.items ?? []).length === 0}>
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
              Voulez-vous vraiment ajouter le produit en location Ã  <strong>{rentalCandidate?.name ?? ''}</strong> Ã  votre devis ?
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







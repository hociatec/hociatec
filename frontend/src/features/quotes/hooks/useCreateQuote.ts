import { useEffect, useMemo, useRef, useState } from 'react';

import {
  createPublicQuote,
  fetchPublicQuoteServices,
  generateMyQuotePdf,
} from '@/features/quotes/api/quotesApi';
import type { QuoteDto, QuoteInput, QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/api';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useToast } from '@/shared/components/ui/toast';
import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import {
  createDefaultQuoteValidity,
  DEFAULT_QUOTE_CONDITIONS,
  type QuoteItem,
} from '@/features/quotes/utils/quoteFormUtils';

export type QuoteDraft = QuoteInput & {
  items: QuoteItem[];
};

export const useCreateQuote = () => {
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
    () =>
      allServices
        .filter((s) => s.title.toLowerCase().includes(searchQuery.trim().toLowerCase()))
        .slice(0, 20),
    [allServices, searchQuery],
  );

  useEffect(() => {
    void fetchPublicQuoteServices()
      .then(setAllServices)
      .catch(() => void 0);
  }, []);

  useEffect(() => {
    if (status === 'authenticated' && user) {
      setForm((f) => ({
        ...f,
        customer: {
          ...f.customer,
          name: [user.firstName, user.lastName].filter(Boolean).join(' ').trim(),
          email: user.email,
          address: [user.address, [user.postalCode, user.city].filter(Boolean).join(' ')]
            .filter(Boolean)
            .join(' ')
            .trim(),
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
      const line = Math.max(
        0,
        (it.unitPriceCents ?? 0) * (it.quantity ?? 1) * months - (it.discountCents ?? 0),
      );
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
    setForm((f) => ({
      ...f,
      items: f.items.map((it, i: number) => (i === index ? { ...it, ...patch } : it)),
    }));
  };

  const removeItem = (index: number) => {
    setForm((f) => ({ ...f, items: f.items.filter((_, i: number) => i !== index) }));
  };

  const findProductItemIndex = (productId: number) =>
    (form.items ?? []).findIndex((it) => it.type === 'product' && it.productId === productId);

  const submit = async () => {
    if (status !== 'authenticated' || !user) {
      toast.show('Connectez-vous pour enregistrer ce devis dans votre espace client.', {
        variant: 'info',
      });
      return;
    }
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      const created = await createPublicQuote(form);
      setSavedQuote(created ?? null);
      toast.show('Devis enregistré. Vous pouvez le retrouver dans votre espace client.', {
        variant: 'success',
      });
      setMessage('Devis enregistré. Vous pouvez le retrouver dans votre espace client.');
    } catch (e) {
      const messageText = getHttpErrorMessage(
        e,
        "Le devis n'a pas pu être créé. Vérifiez les informations saisies puis réessayez.",
      );
      toast.show(messageText, { variant: 'error' });
      setError(messageText);
    } finally {
      setSaving(false);
    }
  };

  const handleDownloadPdf = async () => {
    if (status !== 'authenticated' || !user) {
      toast.show('Connectez-vous pour générer et télécharger votre devis en PDF.', {
        variant: 'info',
      });
      return;
    }

    if ((form.items ?? []).length === 0) {
      toast.show('Ajoutez au moins un produit ou service avant de générer le PDF.', {
        variant: 'info',
      });
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
      const messageText = await getHttpErrorMessageAsync(
        e,
        "Le PDF n'a pas pu être généré. Réessayez dans quelques instants.",
      );
      toast.show(messageText, { variant: 'error' });
      setError(messageText);
    } finally {
      setSaving(false);
    }
  };

  return {
    addProductLineFromProduct,
    addServiceLine,
    allServices,
    error,
    filteredServices,
    findProductItemIndex,
    form,
    handleDownloadPdf,
    message,
    products,
    productLoading,
    rentalCandidate,
    rentalDialogOpen,
    removeItem,
    savedQuote,
    saving,
    searchQuery,
    setForm,
    setRentalCandidate,
    setRentalDialogOpen,
    setSearchQuery,
    status,
    submit,
    totals,
    updateItem,
    user,
  };
};

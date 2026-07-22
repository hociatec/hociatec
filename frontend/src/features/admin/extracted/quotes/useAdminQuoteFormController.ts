import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { fetchAdminProducts, type CatalogProduct } from '@/features/catalog/api';
import {
  createAdminQuote,
  fetchAdminQuote,
  fetchAdminQuoteServices,
  generateAdminQuotePdf,
  updateAdminQuote,
  type QuoteDto,
  type QuoteInput,
  type QuoteServiceDto,
} from '@/features/quotes/api';
import {
  adaptQuoteForSave,
  createDefaultQuoteValidity,
  DEFAULT_QUOTE_CONDITIONS,
  type QuoteItem,
} from '@/features/quotes/utils/quoteFormUtils';
import { type AdminQuoteFormState } from '@/features/admin/extracted/quotes/AdminQuoteFormSections';
import { useToast } from '@/shared/components/ui/toast';

const toQuoteFormState = (quote: QuoteDto): AdminQuoteFormState => ({
  ...quote,
  status: quote.statusCode,
  items: quote.items.map((item) => ({
    id: item.id,
    type: item.type,
    productId: item.productId,
    serviceId: item.serviceId,
    name: item.name,
    description: item.description,
    unit: item.unit,
    quantity: item.quantity,
    unitPriceCents: item.unitPriceCents,
    vatRate: item.vatRate,
    discountCents: item.discountCents,
  })),
});

const createEmptyQuoteFormState = (): AdminQuoteFormState => ({
  status: 'draft',
  customer: {},
  items: [],
  discountCents: 0,
  shippingCents: 0,
  conditions: DEFAULT_QUOTE_CONDITIONS,
  ...createDefaultQuoteValidity(),
});

const createProductQuoteItem = (product: CatalogProduct): QuoteItem => ({
  type: 'product',
  productId: product.id,
  name: product.name,
  description: product.shortDescription ?? undefined,
  unit: undefined,
  quantity: 1,
  unitPriceCents: product.effectivePriceCents ?? product.priceCents,
  vatRate: 20,
  discountCents: 0,
  ...(product.sellingType === 'rental' ? { rentalMonths: 1 } : {}),
});

const createCustomQuoteItem = (): QuoteItem => ({
  type: 'custom',
  name: 'Ligne manuelle',
  description: '',
  unit: 'unité',
  quantity: 1,
  unitPriceCents: 0,
  vatRate: 20,
  discountCents: 0,
});

export const useAdminQuoteFormController = () => {
  const toast = useToast();
  const params = useParams();
  const navigate = useNavigate();
  const isNew = params.quoteId === 'new' || !params.quoteId;
  const [quote, setQuote] = useState<AdminQuoteFormState | null>(null);
  const [services, setServices] = useState<QuoteServiceDto[]>([]);
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [rentalDialogOpen, setRentalDialogOpen] = useState(false);
  const [rentalCandidate, setRentalCandidate] = useState<CatalogProduct | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const trimmedSearchQuery = searchQuery.trim().toLowerCase();
  const filteredServices = useMemo(() => {
    if (trimmedSearchQuery === '') return [];
    return services.filter((service) => service.title.toLowerCase().includes(trimmedSearchQuery)).slice(0, 20);
  }, [services, trimmedSearchQuery]);
  const filteredProducts = useMemo(() => {
    if (trimmedSearchQuery === '') return [];
    return products.filter((product) => product.name.toLowerCase().includes(trimmedSearchQuery)).slice(0, 20);
  }, [products, trimmedSearchQuery]);

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
              ...toQuoteFormState(q),
              conditions: q.conditions ?? DEFAULT_QUOTE_CONDITIONS,
              validFrom: q.validFrom ?? createDefaultQuoteValidity().validFrom,
              validUntil: q.validUntil ?? createDefaultQuoteValidity().validUntil,
            }
          : createEmptyQuoteFormState(),
      );
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Échec de sauvegarde.');
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
    for (const item of quote.items) {
      const isRental = item.type === 'product' && products.some((product) => product.id === item.productId && product.sellingType === 'rental');
      const months = isRental ? Math.max(1, item.rentalMonths ?? 1) : 1;
      const line = Math.max(0, item.unitPriceCents * item.quantity * months - (item.discountCents ?? 0));
      ht += line;
      vat += Math.round(line * (item.vatRate / 100));
    }
    ht = Math.max(0, ht - (quote.discountCents ?? 0));
    return { ht, vat, ttc: ht + vat + (quote.shippingCents ?? 0) };
  }, [quote, products]);

  const addItemFromService = (serviceId: number) => {
    const service = services.find((item) => item.id === serviceId);
    if (!service) return;
    setQuote((current) => {
      if (!current) return current;
      const index = current.items.findIndex((item) => item.type === 'service' && item.serviceId === service.id);
      if (index >= 0) {
        const next = [...current.items];
        next[index] = { ...next[index], quantity: (next[index].quantity ?? 1) + 1 };
        return { ...current, items: next };
      }
      return {
        ...current,
        items: [...current.items, {
          type: 'service',
          serviceId: service.id,
          name: service.title,
          description: service.description ?? undefined,
          unit: service.unit ?? undefined,
          quantity: 1,
          unitPriceCents: service.priceCents,
          vatRate: Number(service.vatRate ?? 0),
          discountCents: 0,
        }],
      };
    });
  };

  const addItemFromProduct = (productId: number) => {
    const product = products.find((item) => item.id === productId);
    if (!product) return;
    setQuote((current) => {
      if (!current) return current;
      if (product.sellingType === 'rental') {
        return { ...current, items: [...current.items, createProductQuoteItem(product)] };
      }
      const index = current.items.findIndex((item) => item.type === 'product' && item.productId === product.id);
      if (index >= 0) {
        const next = [...current.items];
        next[index] = { ...next[index], quantity: (next[index].quantity ?? 1) + 1 };
        return { ...current, items: next };
      }
      return { ...current, items: [...current.items, createProductQuoteItem(product)] };
    });
  };

  const addCustomItem = () => {
    setQuote((current) => current ? ({ ...current, items: [...current.items, createCustomQuoteItem()] }) : current);
  };

  const updateItem = (index: number, patch: Partial<QuoteItem>) => {
    setQuote((current) => current ? ({
      ...current,
      items: current.items.map((item, itemIndex) => (itemIndex === index ? { ...item, ...patch } : item)),
    }) : current);
  };

  const removeItem = (index: number) => {
    setQuote((current) => current ? ({ ...current, items: current.items.filter((_, itemIndex) => itemIndex !== index) }) : current);
  };

  const save = async () => {
    if (!quote) return;
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      const payload = adaptQuoteForSave(quote) as QuoteInput;
      const saved = isNew
        ? await createAdminQuote(payload)
        : await updateAdminQuote(Number(params.quoteId), payload);
      if (isNew) navigate(`/admin/quotes/${saved.id}/edit`, { replace: true });
      setQuote(toQuoteFormState(saved));
      const emailNotificationSent = saved.emailNotificationSent === true;
      const emailNotificationError = typeof saved.emailNotificationError === 'string' ? saved.emailNotificationError : null;
      const successMessage = emailNotificationSent
        ? 'Devis enregistré. Email automatique envoyé au client.'
        : emailNotificationError
          ? `Devis enregistré. Email automatique non envoyé : ${emailNotificationError}`
          : 'Devis enregistré.';
      setMessage(successMessage);
      try { toast.show(successMessage, { variant: emailNotificationSent ? 'success' : emailNotificationError ? 'info' : 'success' }); } catch {}
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Échec de sauvegarde.');
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
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `${quote.number ?? 'devis'}.pdf`;
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      window.URL.revokeObjectURL(url);
    } catch (e) {
      alert(getHttpErrorMessage(e, "Impossible de générer le PDF."));
    }
  };

  const confirmRentalAdd = () => {
    if (rentalCandidate) {
      const product = rentalCandidate;
      setQuote((current) => current ? ({ ...current, items: [...current.items, createProductQuoteItem(product)] }) : current);
    }
    setRentalDialogOpen(false);
    setRentalCandidate(null);
  };

  const cancelRentalAdd = () => {
    setRentalDialogOpen(false);
    setRentalCandidate(null);
  };

  return {
    addCustomItem,
    addItemFromProduct,
    addItemFromService,
    cancelRentalAdd,
    confirmRentalAdd,
    error,
    filteredProducts,
    filteredServices,
    handleGeneratePdf,
    isNew,
    loading,
    message,
    products,
    quote,
    removeItem,
    rentalCandidate,
    rentalDialogOpen,
    save,
    saving,
    searchQuery,
    setQuote,
    setRentalCandidate,
    setRentalDialogOpen,
    setSearchQuery,
    total,
    trimmedSearchQuery,
    updateItem,
  };
};

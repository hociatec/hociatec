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
} from '@/features/quotes/api/quotesApi';
import type { QuoteDto, QuoteInput, QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import {
  adaptQuoteForSave,
  createDefaultQuoteValidity,
  DEFAULT_QUOTE_CONDITIONS,
} from '@/features/quotes/utils/quoteFormUtils';
import { type AdminQuoteFormState } from '@/features/admin/quotes/components/AdminQuoteFormSections';
import { useAdminQuoteItems } from './useAdminQuoteItems';
import { useToast } from '@/shared/components/ui/toast';
import { downloadBlob } from '@/shared/lib/downloadFile';

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
    return services
      .filter((service) => service.title.toLowerCase().includes(trimmedSearchQuery))
      .slice(0, 20);
  }, [services, trimmedSearchQuery]);
  const filteredProducts = useMemo(() => {
    if (trimmedSearchQuery === '') return [];
    return products
      .filter((product) => product.name.toLowerCase().includes(trimmedSearchQuery))
      .slice(0, 20);
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
      try {
        toast.show(msg, { variant: 'error' });
      } catch {}
    } finally {
      setLoading(false);
    }
  }, [isNew, params.quoteId]);

  useEffect(() => {
    void load();
  }, [load]);

  const { addCustomItem, addItemFromProduct, addItemFromService, removeItem, total, updateItem } =
    useAdminQuoteItems({ products, quote, services, setQuote });

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
      const emailNotificationError =
        typeof saved.emailNotificationError === 'string' ? saved.emailNotificationError : null;
      const successMessage = emailNotificationSent
        ? 'Devis enregistré. Email automatique envoyé au client.'
        : emailNotificationError
          ? `Devis enregistré. Email automatique non envoyé : ${emailNotificationError}`
          : 'Devis enregistré.';
      setMessage(successMessage);
      try {
        toast.show(successMessage, {
          variant: emailNotificationSent ? 'success' : emailNotificationError ? 'info' : 'success',
        });
      } catch {}
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Échec de sauvegarde.');
      setError(msg);
      try {
        toast.show(msg, { variant: 'error' });
      } catch {}
    } finally {
      setSaving(false);
    }
  };

  const handleGeneratePdf = async () => {
    if (!quote?.id) return;
    try {
      const blob = await generateAdminQuotePdf(quote.id);
      downloadBlob(blob, `${quote.number ?? 'devis'}.pdf`);
    } catch (e) {
      alert(getHttpErrorMessage(e, 'Impossible de générer le PDF.'));
    }
  };

  const confirmRentalAdd = () => {
    if (rentalCandidate) {
      addItemFromProduct(rentalCandidate.id);
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

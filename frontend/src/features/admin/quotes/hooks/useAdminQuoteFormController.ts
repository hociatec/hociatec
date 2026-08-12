import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { fetchAdminProductsPage, type CatalogProduct } from '@/features/catalog/adminApi';
import {
  createAdminQuote,
  fetchAdminQuote,
  fetchAdminQuoteServicesPage,
  generateAdminQuotePdf,
  updateAdminQuote,
} from '@/features/quotes/publicApi';
import type { QuoteDto, QuoteInput, QuoteServiceDto } from '@/features/quotes/publicApi';
import {
  adaptQuoteForSave,
  createDefaultQuoteValidity,
} from '@/features/quotes/publicApi';
import { type AdminQuoteFormState } from '@/features/admin/quotes/types/adminQuoteFormTypes';
import { useAdminQuoteItems } from './useAdminQuoteItems';
import { useToast } from '@/shared/components/ui/toast';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { logger } from '@/shared/lib/logger';
import { adminQuoteQueryKeys } from '@/features/quotes/publicApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { normalizeSearchText } from '@/shared/lib/searchText';

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
  conditions: '',
  ...createDefaultQuoteValidity(),
});

export const useAdminQuoteFormController = () => {
  const toast = useToast();
  const params = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isNew = params.quoteId === 'new' || !params.quoteId;
  const quoteId = isNew ? null : parseNullablePositiveInteger(params.quoteId);
  const [quote, setQuote] = useState<AdminQuoteFormState | null>(null);
  const [rentalDialogOpen, setRentalDialogOpen] = useState(false);
  const [rentalCandidate, setRentalCandidate] = useState<CatalogProduct | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const debouncedSearchQuery = useDebounce(searchQuery.trim(), 250);
  const formOptionsQuery = useQuery({
    queryKey: adminQuoteQueryKeys.formOptions(quoteId),
    queryFn: async () => {
      const [loadedQuote] = await Promise.all([
        isNew || quoteId === null ? Promise.resolve(null) : fetchAdminQuote(quoteId),
      ]);
      return { loadedQuote };
    },
  });
  const servicesSearchQuery = useQuery({
    queryKey: [...adminQuoteQueryKeys.services(), { q: debouncedSearchQuery }],
    queryFn: () => fetchAdminQuoteServicesPage(1, 20, debouncedSearchQuery || undefined),
    enabled: debouncedSearchQuery.length >= 2,
  });
  const productsSearchQuery = useQuery({
    queryKey: ['admin', 'quotes', 'products-search', { q: debouncedSearchQuery }],
    queryFn: () =>
      fetchAdminProductsPage(
        omitUndefinedProperties({
          page: 1,
          perPage: 20,
          search: debouncedSearchQuery || undefined,
        }),
      ),
    enabled: debouncedSearchQuery.length >= 2,
  });
  const services: QuoteServiceDto[] = servicesSearchQuery.data?.items ?? [];
  const products: CatalogProduct[] = productsSearchQuery.data?.items ?? [];

  const trimmedSearchQuery = normalizeSearchText(searchQuery);
  const filteredServices = useMemo(
    () => (trimmedSearchQuery === '' ? [] : services),
    [services, trimmedSearchQuery],
  );
  const filteredProducts = useMemo(
    () => (trimmedSearchQuery === '' ? [] : products),
    [products, trimmedSearchQuery],
  );

  useEffect(() => {
    if (!formOptionsQuery.data) return;
    const q = formOptionsQuery.data.loadedQuote;
      setQuote(
        q
          ? {
              ...toQuoteFormState(q),
              conditions: q.conditions ?? '',
              validFrom: q.validFrom ?? createDefaultQuoteValidity().validFrom,
              validUntil: q.validUntil ?? createDefaultQuoteValidity().validUntil,
            }
          : createEmptyQuoteFormState(),
      );
  }, [formOptionsQuery.data]);

  const { addCustomItem, addItemFromProduct, addItemFromService, removeItem, total, updateItem } =
    useAdminQuoteItems({ products, quote, services, setQuote });

  const saveMutation = useMutation({
    mutationFn: (currentQuote: AdminQuoteFormState) => {
      const payload = adaptQuoteForSave(currentQuote) as QuoteInput;
      return isNew || quoteId === null
        ? createAdminQuote(payload)
        : updateAdminQuote(quoteId, payload);
    },
    onSuccess: (saved) => {
      if (isNew) navigate(`/admin/quotes/${saved.id}/edit`, { replace: true });
      void queryClient.invalidateQueries({ queryKey: adminQuoteQueryKeys.base() });
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
      } catch (toastError) {
        logger.warn('Unable to display quote save success toast.', { error: toastError });
      }
    },
    onError: (e) => {
      const msg = getHttpErrorMessage(e, 'Échec de sauvegarde.');
      setError(msg);
      try {
        toast.show(msg, { variant: 'error' });
      } catch (toastError) {
        logger.warn('Unable to display quote save error toast.', { error: toastError });
      }
    },
  });

  const pdfMutation = useMutation({
    mutationFn: (currentQuote: AdminQuoteFormState) => generateAdminQuotePdf(currentQuote.id ?? 0),
    onSuccess: (blob, currentQuote) => downloadBlob(blob, `${currentQuote.number ?? 'devis'}.pdf`),
    onError: (e) => {
      alert(getHttpErrorMessage(e, 'Impossible de générer le PDF.'));
    },
  });

  const save = async () => {
    if (!quote) return;
    setError(null);
    setMessage(null);
    saveMutation.mutate(quote);
  };

  const handleGeneratePdf = async () => {
    if (!quote?.id) return;
    pdfMutation.mutate(quote);
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
    error:
      error ??
      (formOptionsQuery.error ? getHttpErrorMessage(formOptionsQuery.error, 'Échec de chargement.') : null),
    filteredProducts,
    filteredServices,
    handleGeneratePdf,
    isNew,
    loading: formOptionsQuery.isLoading,
    message,
    products,
    quote,
    removeItem,
    rentalCandidate,
    rentalDialogOpen,
    save,
    saving: saveMutation.isPending,
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

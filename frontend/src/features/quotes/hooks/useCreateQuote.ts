import { useEffect, useMemo, useState } from 'react';

import {
  createPublicQuote,
  generateMyQuotePdf,
} from '@/features/quotes/api/quotesApi';
import type { QuoteDto, QuoteInput } from '@/features/quotes/types/quoteTypes';
import type { CatalogProduct } from '@/features/catalog/publicApi';
import { useAuth } from '@/features/auth/publicApi';
import { useToast } from '@/shared/components/ui/toast';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import {
  createDefaultQuoteValidity,
  calculateQuoteTotals,
  type QuoteItem,
} from '@/features/quotes/utils/quoteFormUtils';
import { useQuoteCatalogSearch } from './useQuoteCatalogSearch';

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
    conditions: '',
    ...createDefaultQuoteValidity(),
  });
  const [message, setMessage] = useState<string | null>(null);
  const [savedQuote, setSavedQuote] = useState<QuoteDto | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const { searchQuery, setSearchQuery, products, productLoading, allServices, filteredServices } =
    useQuoteCatalogSearch();
  const [rentalDialogOpen, setRentalDialogOpen] = useState(false);
  const [rentalCandidate, setRentalCandidate] = useState<CatalogProduct | null>(null);

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

  const totals = useMemo(() => calculateQuoteTotals(form), [form]);

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
          ...(p.sellingType === 'rental' ? { rentalMonths: 1 } : {}),
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
          vatRate: s.vatRate,
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
      const response = await createPublicQuote(form);
      setSavedQuote(response.data ?? null);
      toast.show(response.message ?? 'Votre devis a bien été enregistré.', {
        variant: 'success',
      });
      setMessage(response.message ?? 'Votre devis a bien été enregistré.');
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
        const response = await createPublicQuote(form);
        quote = response.data;
        setSavedQuote(response.data);
        setMessage(response.message ?? 'Votre devis a bien été enregistré. Le PDF est en cours de préparation.');
      }

      const pdf = await generateMyQuotePdf(quote.id);
      const fileName = `${quote.number ?? 'devis'}.pdf`;
      downloadBlob(pdf, fileName);
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

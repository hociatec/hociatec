import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createAdminQuoteService,
  fetchAdminQuoteMetadata,
  fetchAdminQuoteService,
  updateAdminQuoteService,
  type QuoteMetadataOption,
} from '@/features/quotes/publicApi';
import { normalizeServiceBillingMode } from '@/features/quotes/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroInputFromCents } from '@/shared/lib/formatters';
import {
  parseNonNegativeDecimal,
  parseNullablePositiveInteger,
} from '@/shared/lib/parsers';
import { ServiceFormFields, type ServiceFormState } from '@/features/admin/quotes/components/ServiceFormFields';
import { adminQuoteQueryKeys } from '@/features/quotes/publicApi';

type ServicePayload = {
  title: string;
  description: string;
  unit: string;
  isFeaturedHome: boolean;
  image?: File | null;
  imageUrl?: string;
  imageAlt?: string;
  durationValue: number | '';
  durationUnit: 'hour' | 'day' | '';
  price: number;
  vatRate: number;
};

const emptyForm: ServiceFormState = {
  title: '',
  description: '',
  unit: '',
  isFeaturedHome: false,
  imageUrl: '',
  imageAlt: '',
  imageFile: null,
  currentImageUrl: '',
  durationValue: '',
  durationUnit: 'hour',
  price: '0',
  vatRate: '20',
};

const fallbackBillingModeOptions: QuoteMetadataOption[] = [
  { value: 'prix fixe', label: 'Prix fixe' },
];

export const ServiceFormPage = () => {
  const params = useParams<{ serviceId?: string }>();
  const serviceId = parseNullablePositiveInteger(params.serviceId);
  const isEdit = serviceId !== null;
  useDocumentTitle(isEdit ? 'Admin - Modifier un service' : 'Admin - Nouveau service');
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [form, setForm] = useState<ServiceFormState>(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const metadataQuery = useQuery({
    queryKey: adminQuoteQueryKeys.metadata(),
    queryFn: fetchAdminQuoteMetadata,
  });
  const serviceQuery = useQuery({
    queryKey: adminQuoteQueryKeys.service(serviceId),
    queryFn: () => fetchAdminQuoteService(serviceId!),
    enabled: isEdit && serviceId !== null,
  });
  const billingModeOptions: QuoteMetadataOption[] =
    metadataQuery.data?.serviceBillingModes.length
      ? metadataQuery.data.serviceBillingModes
      : fallbackBillingModeOptions;

  useEffect(() => {
    if (!serviceQuery.data) return;
    const svc = serviceQuery.data;
        setForm({
          title: svc?.title ?? '',
          description: svc?.description ?? '',
          unit: normalizeServiceBillingMode(svc?.unit),
          isFeaturedHome: svc?.isFeaturedHome ?? false,
          imageUrl: svc?.imageUrl ?? '',
          imageAlt: svc?.imageAlt ?? '',
          imageFile: null,
          currentImageUrl: svc?.imageUrl ?? '',
          durationValue: svc?.durationValue ? String(svc.durationValue) : '',
          durationUnit: svc?.durationUnit === 'day' ? 'day' : 'hour',
          price: svc ? formatEuroInputFromCents(svc.priceCents) : '0',
          vatRate: svc ? String(svc.vatRate ?? 0) : '0',
        });
  }, [serviceQuery.data]);

  const buildPayload = (): ServicePayload | null => {
    const title = form.title.trim();
    if (!title) {
      setError('Veuillez renseigner un titre.');
      return null;
    }

    const price = parseNonNegativeDecimal(form.price, Number.NaN);
    if (Number.isNaN(price)) {
      setError('Veuillez renseigner un prix HT valide.');
      return null;
    }

    const vatRate = parseNonNegativeDecimal(form.vatRate, 0);
    if (Number.isNaN(vatRate)) {
      setError('Veuillez renseigner un taux de TVA valide.');
      return null;
    }

    const description = form.description.trim();
    const unit = normalizeServiceBillingMode(form.unit);
    const durationValue = form.durationValue.trim();
    const parsedDurationValue =
      durationValue === '' ? '' : parseNullablePositiveInteger(durationValue);

    if (durationValue !== '' && parsedDurationValue === null) {
      setError('Veuillez renseigner une durée estimée valide.');
      return null;
    }
    const parsedDurationUnit: ServicePayload['durationUnit'] =
      durationValue === '' ? '' : form.durationUnit;

    return {
      title,
      description,
      unit,
      isFeaturedHome: form.isFeaturedHome,
      image: form.imageFile,
      imageUrl: form.imageUrl.trim(),
      imageAlt: form.imageAlt.trim(),
      durationValue: parsedDurationValue,
      durationUnit: parsedDurationUnit,
      price,
      vatRate,
    };
  };

  const saveMutation = useMutation({
    mutationFn: (payload: ServicePayload) =>
      isEdit && serviceId !== null
        ? updateAdminQuoteService(serviceId, payload)
        : createAdminQuoteService(payload),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminQuoteQueryKeys.services() });
      setMessage(response.message ?? (isEdit ? 'Le service a bien été mis à jour.' : 'Le service a bien été créé.'));
      navigate('/admin/services');
    },
    onError: (e) => setError(getHttpErrorMessage(e, 'Échec de sauvegarde.')),
  });

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    setError(null);
    setMessage(null);

    const payload = buildPayload();
    if (!payload) {
      return;
    }

    saveMutation.mutate(payload);
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier un service' : 'Nouveau service'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/services')}
        >
          Retour à la liste
        </button>
      }
    >
      <p className="mb-4 text-sm text-stone-600">
        Renseignez ici les informations complètes du service, y compris sa durée estimée lorsqu’elle
        est connue.
      </p>
      {(error || serviceQuery.error) && (
        <FeedbackMessage>
          {error ?? getHttpErrorMessage(serviceQuery.error, 'Chargement impossible.')}
        </FeedbackMessage>
      )}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {serviceQuery.isLoading && isEdit ? (
        <LoadingState>Chargement du service...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <ServiceFormFields
            form={form}
            setForm={setForm}
            billingModeOptions={billingModeOptions}
          />

          <button type="submit" className="register-form__submit" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Sauvegarde...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};

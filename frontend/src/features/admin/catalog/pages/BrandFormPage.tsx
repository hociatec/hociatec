import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createBrand,
  fetchAdminBrand,
  updateBrand,
  type CatalogBrand,
  type UpsertBrandPayload,
} from '@/features/catalog/adminApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminCatalogQueryKeys } from '@/features/admin/catalog/queryKeys';
import { useDelayedNavigation } from '@/shared/hooks/useDelayedNavigation';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

type BrandFormState = {
  name: string;
};

const emptyForm: BrandFormState = {
  name: '',
};

export const BrandFormPage = () => {
  const { brandId } = useParams();
  const parsedBrandId = parseNullablePositiveInteger(brandId);
  const safeBrandId = parsedBrandId ?? 0;
  const isEdit = parsedBrandId !== null;
  const navigate = useNavigate();
  const navigateWithDelay = useDelayedNavigation(600);
  const queryClient = useQueryClient();

  useDocumentTitle(isEdit ? 'Admin - Modifier une marque' : 'Admin - Nouvelle marque');

  const [form, setForm] = useState<BrandFormState>(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const brandQuery = useQuery<CatalogBrand, Error>({
    queryKey: adminCatalogQueryKeys.brand(isEdit ? parsedBrandId : null),
    queryFn: () => fetchAdminBrand(safeBrandId),
    enabled: isEdit,
  });
  const saveMutation = useMutation({
    mutationFn: (payload: UpsertBrandPayload) =>
      isEdit ? updateBrand(safeBrandId, payload) : createBrand(payload),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminCatalogQueryKeys.brands() });
      setMessage(
        response.message ??
          (isEdit ? 'La marque a bien été mise à jour.' : 'La marque a bien été créée.'),
      );
      if (!isEdit) setForm(emptyForm);
      navigateWithDelay('/admin/catalog/brands');
    },
    onError: (err) => setError(getHttpErrorMessage(err, "Impossible d'enregistrer la marque.")),
  });

  useEffect(() => {
    if (brandQuery.data) populateForm(brandQuery.data);
  }, [brandQuery.data]);

  const populateForm = (brand: CatalogBrand) => {
    setForm({ name: brand.name });
  };

  const parsePayload = (): UpsertBrandPayload => ({
    name: form.name.trim(),
  });

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    const payload = parsePayload();

    if (!payload.name) {
      setError('Le nom de la marque est requis.');
      return;
    }

    setError(null);
    setMessage(null);
    saveMutation.mutate(payload);
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier une marque' : 'Nouvelle marque'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/catalog/brands')}
        >
          Retour à la liste
        </button>
      }
    >
      {(error || brandQuery.error) && (
        <FeedbackMessage>
          {error ?? getHttpErrorMessage(brandQuery.error, 'Impossible de charger la marque.')}
        </FeedbackMessage>
      )}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {brandQuery.isLoading ? (
        <LoadingState>Chargement de la marque...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <label className="register-form__field">
            <span className="register-form__label">Nom</span>
            <input
              className="register-form__input"
              name="name"
              value={form.name}
              onChange={(event) => setForm({ name: event.target.value })}
              maxLength={80}
              required
            />
          </label>

          <button className="register-form__submit" type="submit" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};

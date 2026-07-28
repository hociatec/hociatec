import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';

import {
  createBrand,
  fetchAdminBrand,
  updateBrand,
  type CatalogBrand,
  type UpsertBrandPayload,
} from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type BrandFormState = {
  name: string;
};

const emptyForm: BrandFormState = {
  name: '',
};

export const BrandFormPage = () => {
  const { brandId } = useParams();
  const isEdit = Boolean(brandId);
  const navigate = useNavigate();

  useDocumentTitle(isEdit ? 'Admin - Modifier une marque' : 'Admin - Nouvelle marque');

  const [form, setForm] = useState<BrandFormState>(emptyForm);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isEdit) {
      return;
    }

    const loadBrand = async () => {
      setInitialLoading(true);
      setError(null);

      try {
        const brand = await fetchAdminBrand(Number(brandId));
        populateForm(brand);
      } catch (err) {
        setError(getHttpErrorMessage(err, 'Impossible de charger la marque.'));
      } finally {
        setInitialLoading(false);
      }
    };

    void loadBrand();
  }, [brandId, isEdit]);

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

    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      if (isEdit) {
        const response = await updateBrand(Number(brandId), payload);
        setMessage(response.message ?? 'La marque a bien été mise à jour.');
      } else {
        const response = await createBrand(payload);
        setMessage(response.message ?? 'La marque a bien été créée.');
        setForm(emptyForm);
      }

      setTimeout(() => {
        navigate('/admin/catalog/brands');
      }, 600);
    } catch (err) {
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la marque."));
    } finally {
      setLoading(false);
    }
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
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading ? (
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

          <button className="register-form__submit" type="submit" disabled={loading}>
            {loading ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};

import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createBrand,
  fetchAdminBrand,
  updateBrand,
  type CatalogBrand,
  type UpsertBrandPayload,
} from '@/features/catalog/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type BrandFormState = {
  name: string;
};

const emptyForm: BrandFormState = {
  name: '',
};

export const BrandFormPage = () => {
  const { brandId } = useParams();
  const isEdit = useMemo(() => Boolean(brandId), [brandId]);
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
      } catch (err: any) {
        setError(err?.message ?? 'Impossible de charger la marque.');
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
        await updateBrand(Number(brandId), payload);
        setMessage('Marque mise à jour.');
      } else {
        await createBrand(payload);
        setMessage('Marque créée.');
        setForm(emptyForm);
      }

      setTimeout(() => {
        navigate('/admin/catalog/brands');
      }, 600);
    } catch (err: any) {
      setError(err?.message ?? "Impossible d'enregistrer la marque.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <PageContainer
      title={isEdit ? 'Modifier une marque' : 'Nouvelle marque'}
      headerActions={
        <button
          type="button"
          className="register-form__submit"
          style={{ background: '#e5e7eb', color: '#111827' }}
          onClick={() => navigate('/admin/catalog/brands')}
        >
          Retour à la liste
        </button>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {initialLoading ? (
        <p className="muted">Chargement de la marque...</p>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card" style={{ display: 'grid', gap: 16 }}>
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

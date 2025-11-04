import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { createAdminQuoteService, fetchAdminQuoteServices, updateAdminQuoteService } from '@/features/quotes/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const ServiceFormPage = () => {
  useDocumentTitle('Admin - Service');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const params = useParams();
  const navigate = useNavigate();
  const isNew = params.serviceId === 'new';

  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [form, setForm] = useState<{ title: string; description?: string; unit?: string; price: number; vatRate: number }>(
    { title: '', description: '', unit: '', price: 0, vatRate: 20 },
  );

  useEffect(() => {
    if (!isAdmin) return;
    if (!isNew && params.serviceId) {
      setLoading(true);
      setError(null);
      void fetchAdminQuoteServices()
        .then((items) => items.find((s: any) => s.id === Number(params.serviceId)))
        .then((svc) => {
          if (svc) {
            setForm({
              title: svc.title,
              description: svc.description ?? '',
              unit: svc.unit ?? '',
              price: svc.priceCents / 100,
              vatRate: Number(svc.vatRate ?? 0),
            });
          }
        })
        .finally(() => setLoading(false));
    }
  }, [isAdmin, isNew, params.serviceId]);

  const save = async () => {
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      if (isNew) {
        await createAdminQuoteService(form);
      } else {
        await updateAdminQuoteService(Number(params.serviceId), form);
      }
      setMessage('Enregistre.');
      navigate('/admin/quotes/services');
    } catch (e: any) {
      setError(e?.message ?? 'Echec de sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  if (guardLoading) {
    return (
      <PageContainer title="Service">
        <p className="muted">Verification des droits...</p>
      </PageContainer>
    );
  }
  if (!isAdmin) {
    return (
      <PageContainer title="Service">
        <div className="register-form__alert">Acces restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer title={isNew ? 'Nouveau service' : 'Modifier le service'}>
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <p className="muted">Chargement...</p>
      ) : (
        <div className="space-y-4">
          <input placeholder="Titre" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
          <input placeholder="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
          <input placeholder="Unite (ex: heure)" value={form.unit} onChange={(e) => setForm({ ...form, unit: e.target.value })} />
          <label>
            Prix HT
            <input
              type="number"
              min={0}
              step="0.01"
              value={form.price}
              onChange={(e) => setForm({ ...form, price: Math.max(0, Number(e.target.value)) })}
            />
          </label>
          <label>
            TVA (%)
            <input
              type="number"
              min={0}
              step="0.1"
              value={form.vatRate}
              onChange={(e) => setForm({ ...form, vatRate: Math.max(0, Number(e.target.value)) })}
            />
          </label>
          <button type="button" className="register-form__submit" onClick={() => void save()} disabled={saving}>
            {saving ? 'Sauvegarde...' : 'Enregistrer'}
          </button>
        </div>
      )}
    </PageContainer>
  );
};


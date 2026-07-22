import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createVoucher,
  fetchVoucher,
  updateVoucher,
  type Voucher,
  type VoucherPayload,
} from '@/features/admin/vouchers/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

type FormState = {
  name: string;
  code: string;
  description: string;
  discountType: 'percent' | 'fixed_cents';
  discountValue: string;
  isActive: boolean;
  startsAt: string;
  endsAt: string;
};

const emptyForm: FormState = {
  name: '',
  code: '',
  description: '',
  discountType: 'fixed_cents',
  discountValue: '',
  isActive: true,
  startsAt: '',
  endsAt: '',
};

const centsToEuro = (value: number) => (value / 100).toFixed(2);
const euroToCents = (value: string) => {
  const parsed = Number.parseFloat(value.replace(',', '.'));
  return Number.isFinite(parsed) ? Math.max(0, Math.round(parsed * 100)) : 0;
};

const generateCode = (name: string) => {
  const base =
    (name.trim() || 'BON')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toUpperCase()
      .replace(/[^A-Z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 16) || 'BON';
  return `${base}-${Math.random().toString(36).slice(2, 6).toUpperCase()}`;
};

export const VoucherFormPage = () => {
  const { voucherId } = useParams();
  const editingId = voucherId ? Number(voucherId) : null;
  const isEdit = Number.isFinite(editingId);
  useDocumentTitle(isEdit ? 'Admin - Modifier un bon' : 'Admin - Nouveau bon');
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isEdit || editingId === null) return;
    setLoading(true);
    setError(null);
    void fetchVoucher(editingId)
      .then((voucher: Voucher) => {
        setForm({
          name: voucher.name,
          code: voucher.code,
          description: voucher.description ?? '',
          discountType: voucher.discountType,
          discountValue:
            voucher.discountType === 'fixed_cents'
              ? centsToEuro(voucher.discountValue)
              : String(voucher.discountValue),
          isActive: voucher.isActive,
          startsAt: voucher.startsAt ? voucher.startsAt.slice(0, 16) : '',
          endsAt: voucher.endsAt ? voucher.endsAt.slice(0, 16) : '',
        });
      })
      .catch((err) => {
        const message = getHttpErrorMessage(err, 'Impossible de charger le bon.');
        setError(message);
        toast.show(message, { variant: 'error' });
      })
      .finally(() => setLoading(false));
  }, [editingId, isEdit, toast]);

  const payload = useMemo<VoucherPayload>(
    () => ({
      name: form.name.trim(),
      code: form.code.trim().toUpperCase(),
      description: form.description.trim() || null,
      discountType: form.discountType,
      discountValue:
        form.discountType === 'fixed_cents'
          ? euroToCents(form.discountValue)
          : Number.parseInt(form.discountValue, 10) || 0,
      isActive: form.isActive,
      startsAt: form.startsAt || null,
      endsAt: form.endsAt || null,
    }),
    [form],
  );

  const handleSave = async () => {
    setSaving(true);
    setError(null);
    try {
      const nextPayload = {
        ...payload,
        code: form.code.trim() || generateCode(form.name),
      };
      if (isEdit && editingId !== null) {
        await updateVoucher(editingId, nextPayload);
        toast.show('Bon de réduction mis à jour.', { variant: 'success' });
      } else {
        await createVoucher(nextPayload);
        toast.show('Bon de réduction créé.', { variant: 'success' });
      }
      navigate('/admin/vouchers');
    } catch (err) {
      const message = getHttpErrorMessage(err, 'Enregistrement impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageContainer size="admin"
      title={isEdit ? 'Modifier un bon' : 'Créer un bon'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/vouchers')}
        >
          Retour à la liste
        </button>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Formulaire dédié à la création et à la modification d’un bon de réduction.
        </p>
        <p className="text-sm text-stone-500">
          Si le code est vide, il sera généré automatiquement à l’enregistrement.
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      {loading ? (
        <LoadingState>Chargement...</LoadingState>
      ) : (
        <form
          className="register-form-card form-card-grid"
          onSubmit={(event) => {
            event.preventDefault();
            void handleSave();
          }}
        >
          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span className="register-form__label">Code</span>
              <input
                className="register-form__input"
                value={form.code}
                onChange={(event) =>
                  setForm((prev) => ({ ...prev, code: event.target.value.toUpperCase() }))
                }
              />
            </label>
            <label className="register-form__field">
              <span className="register-form__label">Nom</span>
              <input
                className="register-form__input"
                value={form.name}
                onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span className="register-form__label">Type de remise</span>
              <select
                className="register-form__input"
                value={form.discountType}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    discountType: event.target.value as 'percent' | 'fixed_cents',
                  }))
                }
              >
                <option value="fixed_cents">Montant fixe en euros</option>
                <option value="percent">Pourcentage</option>
              </select>
            </label>
            <label className="register-form__field">
              <span className="register-form__label">
                Valeur {form.discountType === 'percent' ? '(%)' : '(EUR)'}
              </span>
              <input
                className="register-form__input"
                type="number"
                min={1}
                step={form.discountType === 'percent' ? 1 : 0.01}
                value={form.discountValue}
                onChange={(event) =>
                  setForm((prev) => ({ ...prev, discountValue: event.target.value }))
                }
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span className="register-form__label">Description</span>
              <input
                className="register-form__input"
                value={form.description}
                onChange={(event) =>
                  setForm((prev) => ({ ...prev, description: event.target.value }))
                }
              />
            </label>
            <label className="register-form__field">
              <span className="register-form__label">Début</span>
              <input
                className="register-form__input"
                type="datetime-local"
                value={form.startsAt}
                onChange={(event) =>
                  setForm((prev) => ({ ...prev, startsAt: event.target.value }))
                }
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span className="register-form__label">Fin</span>
              <input
                className="register-form__input"
                type="datetime-local"
                value={form.endsAt}
                onChange={(event) => setForm((prev) => ({ ...prev, endsAt: event.target.value }))}
              />
            </label>
            <label className="booking__checkbox md:mt-8">
              <input
                type="checkbox"
                checked={form.isActive}
                onChange={(event) =>
                  setForm((prev) => ({ ...prev, isActive: event.target.checked }))
                }
              />
              Bon actif
            </label>
          </div>

          <div className="flex gap-3">
            <button type="submit" className="register-form__submit" disabled={saving}>
              {saving ? 'Enregistrement...' : isEdit ? 'Mettre à jour le bon' : 'Créer le bon'}
            </button>
          </div>
        </form>
      )}
    </PageContainer>
  );
};

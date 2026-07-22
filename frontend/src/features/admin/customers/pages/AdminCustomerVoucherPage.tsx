import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  createCustomerVoucher,
  fetchAdminCustomerById,
  type AdminCustomerDetailDto,
  type AdminCustomerVoucherDto,
  type CustomerVoucherPayload,
} from '@/features/admin/customers/api';
import { deleteVoucher } from '@/features/admin/vouchers/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { formatEuroCents, formatOptionalFrenchDate, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type VoucherFormState = {
  name: string;
  code: string;
  description: string;
  discountType: 'percent' | 'fixed_cents';
  discountValue: string;
  isActive: boolean;
  startsAt: string;
  endsAt: string;
  sendEmail: boolean;
};

const emptyVoucherForm: VoucherFormState = {
  name: '',
  code: '',
  description: '',
  discountType: 'fixed_cents',
  discountValue: '',
  isActive: true,
  startsAt: '',
  endsAt: '',
  sendEmail: true,
};

export const AdminCustomerVoucherPage = () => {
  const params = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const customerId = Number(params.customerId);
  const [customer, setCustomer] = useState<AdminCustomerDetailDto | null>(null);
  const [vouchers, setVouchers] = useState<AdminCustomerVoucherDto[]>([]);
  const [form, setForm] = useState<VoucherFormState>(emptyVoucherForm);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const confirm = useConfirm();

  useEffect(() => {
    if (!customerId) {
      setStatus('error');
      setError('Client invalide.');
      return;
    }

    setStatus('loading');
    setError(null);
    void fetchAdminCustomerById(customerId)
      .then((data) => {
        setCustomer(data.customer);
        setVouchers(data.vouchers);
        setForm((current) => ({
          ...current,
          name: current.name || `Offre client ${data.customer.lastName}`,
        }));
        setStatus('success');
      })
      .catch((e: unknown) => {
        setStatus('error');
        setError(e instanceof Error ? e.message : 'Impossible de charger ce client.');
      });
  }, [customerId]);

  const buildPayload = (): CustomerVoucherPayload => ({
    name: form.name.trim(),
    code: form.code.trim() || undefined,
    description: form.description.trim() || null,
    discountType: form.discountType,
    discountValue:
      form.discountType === 'fixed_cents'
        ? Math.max(0, Math.round((Number.parseFloat(form.discountValue.replace(',', '.')) || 0) * 100))
        : Math.max(0, Number.parseInt(form.discountValue, 10) || 0),
    isActive: form.isActive,
    startsAt: form.startsAt || null,
    endsAt: form.endsAt || null,
    sendEmail: form.sendEmail,
  });

  const handleSubmit = () => {
    if (!customer) return;

    setSaving(true);
    void createCustomerVoucher(customer.id, buildPayload())
      .then((result) => {
        setSaving(false);
        setForm(emptyVoucherForm);
        setVouchers((current) => [result.voucher, ...current]);
        toast.show(
          `Bon ${result.voucher.code} créé${result.emailSent ? ' et envoyé par e-mail.' : '.'}`,
          { variant: 'success' },
        );
      })
      .catch((e: unknown) => {
        setSaving(false);
        toast.show(e instanceof Error ? e.message : 'Impossible de créer le bon de réduction.', {
          variant: 'error',
        });
      });
  };

  const handleDelete = async (voucherId: number) => {
    const voucher = vouchers.find((item) => item.id === voucherId);
    const voucherLabel = voucher ? `"${voucher.name}" (${voucher.code})` : 'ce bon de réduction';

    const confirmed = await confirm({
      title: 'Supprimer le bon',
      description: `Supprimer ${voucherLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) return;

    try {
      await deleteVoucher(voucherId);
      setVouchers((current) => current.filter((item) => item.id !== voucherId));
      toast.show('Bon de réduction supprimé.', { variant: 'success' });
    } catch (e: unknown) {
      toast.show(e instanceof Error ? e.message : 'Impossible de supprimer le bon.', {
        variant: 'error',
      });
    }
  };

  return (
    <PageContainer size="admin"
      title={customer ? `Bon de réduction - ${customer.fullName}` : 'Bon de réduction client'}
      headerActions={
        <div className="flex items-center gap-4">
          {customer ? (
            <button
              type="button"
              className="underline text-sm"
              onClick={() => navigate(`/admin/customers/${customer.id}`)}
            >
              Retour à la fiche client
            </button>
          ) : null}
          <button type="button" className="underline text-sm" onClick={() => navigate('/admin/customers')}>
            Retour aux clients
          </button>
        </div>
      }
    >
      {status === 'loading' && <LoadingState>Chargement...</LoadingState>}
      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      {status === 'success' && customer ? (
        <div className="space-y-6">
          <section className="overflow-hidden rounded-xl border border-brand-100 bg-white shadow-sm">
            <div className="border-b border-brand-100 bg-brand-900 px-6 py-5 text-white">
              <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Bons de réduction</p>
                  <h2 className="mt-1 text-xl font-semibold">Créer et envoyer un bon client</h2>
                  <p className="mt-2 text-sm text-stone-500">
                    Offre dédiée à {customer.fullName} avec envoi d’e-mail optionnel.
                  </p>
                </div>
                <Link
                  className="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                  to="/admin/transactional-emails"
                >
                  Voir les modèles d’e-mail
                </Link>
              </div>
            </div>

            <div className="grid gap-6 px-6 py-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
              <div className="space-y-4 rounded-xl border border-brand-100 p-5">
                <div>
                  <div className="text-sm font-semibold text-brand-900">Configuration du bon</div>
                  <p className="mt-1 text-sm text-stone-500">
                    Définis le code, la valeur, la période de validité et l’envoi au client.
                  </p>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
              <label className="register-form__field">
                <span className="register-form__label">Nom</span>
                <input
                  className="register-form__input"
                  value={form.name}
                  onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
                />
              </label>
              <label className="register-form__field">
                <span className="register-form__label">Code</span>
                <input
                  className="register-form__input"
                  value={form.code}
                  onChange={(event) => setForm((prev) => ({ ...prev, code: event.target.value.toUpperCase() }))}
                  placeholder="Laisse vide pour génération auto"
                />
              </label>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
              <label className="register-form__field">
                <span className="register-form__label">Type</span>
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
                  onChange={(event) => setForm((prev) => ({ ...prev, discountValue: event.target.value }))}
                />
              </label>
                </div>
                <label className="register-form__field">
              <span className="register-form__label">Description</span>
              <input
                className="register-form__input"
                value={form.description}
                onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))}
                placeholder="Ex: Offre fidélité valable sur votre prochaine commande"
              />
                </label>
                <div className="grid gap-4 md:grid-cols-2">
              <label className="register-form__field">
                <span className="register-form__label">Début</span>
                <input
                  className="register-form__input"
                  type="datetime-local"
                  value={form.startsAt}
                  onChange={(event) => setForm((prev) => ({ ...prev, startsAt: event.target.value }))}
                />
              </label>
              <label className="register-form__field">
                <span className="register-form__label">Fin</span>
                <input
                  className="register-form__input"
                  type="datetime-local"
                  value={form.endsAt}
                  onChange={(event) => setForm((prev) => ({ ...prev, endsAt: event.target.value }))}
                />
              </label>
                </div>
                <div className="flex flex-wrap items-center gap-6 text-sm text-stone-700">
              <label className="booking__checkbox">
                <input
                  type="checkbox"
                  checked={form.isActive}
                  onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))}
                />
                Bon actif
              </label>
              <label className="booking__checkbox">
                <input
                  type="checkbox"
                  checked={form.sendEmail}
                  onChange={(event) => setForm((prev) => ({ ...prev, sendEmail: event.target.checked }))}
                />
                Envoyer l’email au client
              </label>
                </div>
                <div className="flex flex-wrap gap-3 pt-2">
              <button
                type="button"
                className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:opacity-60"
                onClick={handleSubmit}
                disabled={saving}
              >
                {saving ? 'Création...' : 'Créer le bon'}
              </button>
                </div>
              </div>

              <aside className="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <div className="text-sm font-semibold text-amber-900">Bonnes pratiques</div>
                <ul className="mt-3 space-y-3 text-sm text-amber-900/80">
                  <li>Donne un nom explicite pour retrouver facilement l’offre depuis l’admin.</li>
                  <li>Laisse le code vide si tu veux une génération automatique.</li>
                  <li>Active l’envoi d’e-mail pour notifier le client immédiatement.</li>
                  <li>Renseigne une date de fin pour les offres temporaires ou exceptionnelles.</li>
                </ul>
              </aside>
            </div>
          </section>

          <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
            <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h2 className="text-lg font-semibold text-brand-900">Historique des bons envoyés</h2>
                <p className="mt-1 text-sm text-stone-500">Retrouve les offres déjà créées pour ce client, leur statut et leur envoi.</p>
              </div>
              <div className="rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-stone-700">
                {vouchers.length} bon{vouchers.length > 1 ? 's' : ''}
              </div>
            </div>
            {vouchers.length === 0 ? (
              <p className="text-sm text-stone-500">Aucun bon de réduction créé pour ce client.</p>
            ) : (
              <div className="space-y-3">
                {vouchers.map((voucher) => (
                  <div key={voucher.id} className="rounded-xl border border-brand-100 bg-brand-50 p-4">
                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                      <div>
                        <div className="font-semibold text-brand-900">{voucher.name}</div>
                        <div className="text-sm text-stone-600">
                          Code {voucher.code} · {voucher.discountType === 'percent' ? `${voucher.discountValue}%` : formatEuroCents(voucher.discountValue)}
                        </div>
                        <div className="text-sm text-stone-500">
                          Créé le {formatOptionalFrenchDateTime(voucher.createdAt)}
                          {voucher.sentAt ? ` · envoyé le ${formatOptionalFrenchDateTime(voucher.sentAt)}` : ' · non envoyé'}
                        </div>
                        {voucher.description ? <div className="mt-1 text-sm text-stone-600">{voucher.description}</div> : null}
                      </div>
                      <div className="flex flex-wrap gap-2 text-xs">
                        <span className={`rounded-full px-3 py-1 ${voucher.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-100 text-stone-700'}`}>
                          {voucher.isActive ? 'Actif' : 'Inactif'}
                        </span>
                        {voucher.endsAt ? (
                          <span className="rounded-full bg-white px-3 py-1 text-stone-600">
                            Fin {formatOptionalFrenchDate(voucher.endsAt)}
                          </span>
                        ) : null}
                        <button
                          type="button"
                          className="rounded-full bg-red-100 px-3 py-1 text-red-700 transition hover:bg-red-200"
                          onClick={() => void handleDelete(voucher.id)}
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </section>
        </div>
      ) : null}
    </PageContainer>
  );
};

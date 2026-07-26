import { Link, useNavigate } from 'react-router-dom';

import { useAdminCustomerVouchers } from '../hooks/useAdminCustomerVouchers';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { AdminCustomerVoucherHistory } from '@/features/admin/customers/components/AdminCustomerVoucherHistory';
import { AdminCustomerVoucherConfiguration } from '@/features/admin/customers/components/AdminCustomerVoucherConfiguration';

export const AdminCustomerVoucherPage = () => {
  const navigate = useNavigate();
  const { customer, vouchers, form, setForm, status, error, saving, handleSubmit, handleDelete } =
    useAdminCustomerVouchers();

  return (
    <PageContainer
      size="admin"
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
          <button
            type="button"
            className="underline text-sm"
            onClick={() => navigate('/admin/customers')}
          >
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
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">
                    Bons de réduction
                  </p>
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

            <AdminCustomerVoucherConfiguration
              form={form}
              setForm={setForm}
              saving={saving}
              handleSubmit={handleSubmit}
            />
          </section>

          <AdminCustomerVoucherHistory vouchers={vouchers} onDelete={handleDelete} />
        </div>
      ) : null}
    </PageContainer>
  );
};

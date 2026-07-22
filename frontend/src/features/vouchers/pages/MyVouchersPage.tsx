import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';

import { fetchMyVouchers, type MyVoucherDto } from '@/features/vouchers/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { StableContent } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';

const isVoucherPast = (voucher: MyVoucherDto) => {
  if (!voucher.isActive) {
    return true;
  }

  if (!voucher.endsAt) {
    return false;
  }

  return new Date(voucher.endsAt).getTime() < Date.now();
};

export const MyVouchersPage = () => {
  useDocumentTitle('Mes bons de réduction');

  const navigate = useNavigate();
  const toast = useToast();
  const [vouchers, setVouchers] = useState<MyVoucherDto[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    void fetchMyVouchers()
      .then((items) => setVouchers(items))
      .catch((error: unknown) => {
        toast.show(error instanceof Error ? error.message : 'Impossible de charger vos bons de réduction.', {
          variant: 'error',
        });
      })
      .finally(() => setLoading(false));
  }, [toast]);

  const activeVouchers = useMemo(
    () => vouchers.filter((voucher) => !isVoucherPast(voucher)),
    [vouchers],
  );
  const pastVouchers = useMemo(
    () => vouchers.filter((voucher) => isVoucherPast(voucher)),
    [vouchers],
  );
  const hasLoadedVouchers = vouchers.length > 0 || !loading;

  return (
    <SiteLayout headerVariant="light">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8">
        <header className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <p className="text-sm uppercase tracking-[0.25em] text-stone-500">Mon espace</p>
            <h1 className="text-3xl font-semibold text-brand-900">Mes bons de réduction</h1>
            <p className="mt-2 max-w-2xl text-sm text-stone-600">
              Consultez vos bons actuellement utilisables et l’historique des bons déjà expirés ou inactifs.
            </p>
          </div>
          <button
            type="button"
            className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
            onClick={() => navigate('/profile')}
          >
            Retour au profil
          </button>
        </header>

        <section className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm" aria-busy={loading || undefined}>
          <div className="mb-4 flex items-center justify-between gap-3">
            <h2 className="text-xl font-semibold text-brand-900">Bons actifs</h2>
            <div className="text-sm text-stone-500">{activeVouchers.length} disponible{activeVouchers.length > 1 ? 's' : ''}</div>
          </div>
          <StableContent loading={loading} hasContent={hasLoadedVouchers} loadingLabel="Chargement des bons actifs...">
            {activeVouchers.length === 0 ? (
              <p className="text-sm text-stone-500">Aucun bon actif pour le moment.</p>
            ) : (
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {activeVouchers.map((voucher) => (
                  <article key={voucher.id} className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Actif</div>
                    <h3 className="mt-2 text-lg font-semibold text-brand-900">{voucher.name}</h3>
                    <div className="mt-1 text-sm text-stone-600">Code {voucher.code}</div>
                    <div className="mt-3 text-sm font-medium text-stone-800">
                      {voucher.discountType === 'percent' ? `${voucher.discountValue}%` : formatEuroCents(voucher.discountValue)}
                    </div>
                    {voucher.description ? <p className="mt-3 text-sm text-stone-600">{voucher.description}</p> : null}
                    <div className="mt-4 space-y-1 text-xs text-stone-500">
                      {voucher.startsAt ? <div>Début {formatOptionalFrenchDate(voucher.startsAt)}</div> : null}
                      {voucher.endsAt ? <div>Fin {formatOptionalFrenchDate(voucher.endsAt)}</div> : <div>Sans date de fin</div>}
                    </div>
                  </article>
                ))}
              </div>
            )}
          </StableContent>
        </section>

        <section className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm" aria-busy={loading || undefined}>
          <div className="mb-4 flex items-center justify-between gap-3">
            <h2 className="text-xl font-semibold text-brand-900">Bons passés</h2>
            <div className="text-sm text-stone-500">{pastVouchers.length} archivé{pastVouchers.length > 1 ? 's' : ''}</div>
          </div>
          <StableContent loading={loading} hasContent={hasLoadedVouchers} loadingLabel="Chargement des bons passés...">
            {pastVouchers.length === 0 ? (
              <p className="text-sm text-stone-500">Aucun bon passé.</p>
            ) : (
              <div className="space-y-3">
                {pastVouchers.map((voucher) => (
                  <article key={voucher.id} className="rounded-2xl bg-brand-50 p-4">
                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                      <div>
                        <h3 className="font-semibold text-brand-900">{voucher.name}</h3>
                        <div className="text-sm text-stone-600">
                          Code {voucher.code} · {voucher.discountType === 'percent' ? `${voucher.discountValue}%` : formatEuroCents(voucher.discountValue)}
                        </div>
                        {voucher.description ? <p className="mt-2 text-sm text-stone-600">{voucher.description}</p> : null}
                      </div>
                      <div className="text-xs text-stone-500">
                        {voucher.endsAt ? `Expiré le ${formatOptionalFrenchDate(voucher.endsAt)}` : 'Inactif'}
                      </div>
                    </div>
                  </article>
                ))}
              </div>
            )}
          </StableContent>
        </section>
      </div>
    </SiteLayout>
  );
};

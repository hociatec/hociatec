import { Link } from 'react-router';

import { useMyTrainingEnrollments } from '../hooks/useMyTrainingEnrollments';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { AdminTableShell } from '@/shared/components/admin/AdminDataView';
import {
  EmptyState,
  ErrorState,
  LoadingState,
  PrimaryLink,
} from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';

export const MyTrainingsPage = () => {
  useDocumentTitle('Mes formations');
  const { items, loading, error } = useMyTrainingEnrollments();

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-12">
        <header className="rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500">
              Espace client
            </p>
            <h1 className="mt-2 text-3xl font-semibold text-brand-900">Mes formations</h1>
            <p className="mt-3 text-stone-600">
              Suivez vos inscriptions, sessions et feuilles de route.
            </p>
          </div>
        </header>
        {loading ? (
          <LoadingState>Chargement...</LoadingState>
        ) : error ? (
          <ErrorState>{error}</ErrorState>
        ) : items.length === 0 ? (
          <EmptyState>
            <p>Aucune formation réservée.</p>
            <PrimaryLink to="/formations" className="mt-4">
              Voir les formations
            </PrimaryLink>
          </EmptyState>
        ) : (
          <section className="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
            <div className="border-b border-brand-100 px-5 py-4">
              <h2 className="text-lg font-semibold text-brand-900">Inscriptions</h2>
              <p className="text-sm text-stone-500">
                {items.length} formation{items.length > 1 ? 's' : ''} dans votre espace.
              </p>
            </div>
            <AdminTableShell className="rounded-none border-0 shadow-none">
              <table className="catalog-admin-table">
                <thead>
                  <tr>
                    <th>Formation</th>
                    <th>Créneau</th>
                    <th>Format</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((item) => (
                    <tr key={item.id}>
                      <td>
                        <strong>{item.session.training.title}</strong>
                        {item.session.training.shortDescription ? (
                          <p className="muted">{item.session.training.shortDescription}</p>
                        ) : null}
                      </td>
                      <td>{formatFrenchDateTime(item.scheduledStartsAt)}</td>
                      <td>{item.session.formatLabel}</td>
                      <td>{formatEuroCents(item.priceCents)}</td>
                      <td>{item.statusLabel}</td>
                      <td>
                        <Link
                          to={`/trainings/me/${item.id}`}
                          className="catalog-admin-actions__edit"
                        >
                          Détail
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </AdminTableShell>
          </section>
        )}
      </main>
    </SiteLayout>
  );
};

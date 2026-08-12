import { Link } from 'react-router';
import { useMyAudits } from '../hooks/useMyAudits';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';

export const MyAuditsPage = () => {
  useDocumentTitle('Mes audits');
  const { items, loading, error, retry, pagination, setPage } = useMyAudits();

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        title="Mes audits"
        description="Suivez les demandes d’audit envoyées à Hociatec et consultez leur avancement."
      >
        {loading ? <LoadingState>Chargement des audits...</LoadingState> : null}
        {error ? <ErrorState onAction={() => void retry()}>{error}</ErrorState> : null}
        {!loading && !error && items.length === 0 ? <EmptyState>Aucun audit trouvé.</EmptyState> : null}
        {items.length > 0 ? (
        <PublicPageSection>
        <ul className="divide-y divide-brand-100">
          {items.map((a) => (
            <li key={a.id} className="flex flex-col gap-4 py-4 md:flex-row md:items-center md:justify-between">
              <div>
                <div className="font-semibold text-brand-900">
                  {a.number} — {a.typeLabel}
                </div>
                <div className="mt-1 text-sm text-stone-600">{a.url}</div>
              </div>
              <div className="text-sm font-medium text-stone-700">{a.statusLabel}</div>
              <div>
                <Link
                  className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                  to={`/audits/me/${a.id}`}
                >
                  Détails
                </Link>
              </div>
            </li>
          ))}
        </ul>
        <PaginationControls
          page={pagination.page}
          total={pagination.total}
          totalLabel="audit"
          totalPages={pagination.totalPages}
          onPageChange={setPage}
        />
        </PublicPageSection>
        ) : null}
      </PublicPageShell>
    </SiteLayout>
  );
};

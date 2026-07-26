import { Link } from 'react-router-dom';
import { useMyAudits } from '../hooks/useMyAudits';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const MyAuditsPage = () => {
  useDocumentTitle('Mes audits');
  const { items, loading, error } = useMyAudits();

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-2xl font-semibold mb-4">Mes audits</h1>
        {loading && <LoadingState>Chargement des audits...</LoadingState>}
        {error && <ErrorState>{error}</ErrorState>}
        {!loading && !error && items.length === 0 && <p>Aucun audit trouvé.</p>}
        <ul className="divide-y">
          {items.map((a) => (
            <li key={a.id} className="py-3 flex items-center justify-between">
              <div>
                <div className="font-medium">
                  {a.number} — {a.typeLabel}
                </div>
                <div className="text-sm text-gray-600">{a.url}</div>
              </div>
              <div className="text-sm">{a.statusLabel}</div>
              <div>
                <Link className="underline" to={`/audits/me/${a.id}`}>
                  Détails
                </Link>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </SiteLayout>
  );
};

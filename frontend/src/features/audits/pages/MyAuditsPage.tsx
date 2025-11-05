import { Link } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { fetchMyAudits, type AuditListItemDto } from '../api';

const typeLabel = (t: string) => ({
  performance: 'Performance',
  security: 'Sécurité',
  ux: 'UX',
  seo: 'SEO',
  technical: 'Technique',
  accessibility: 'Accessibilité',
} as const)[t as keyof any] ?? t;

const statusLabel = (s: string) => ({
  new: 'non commencé',
  in_progress: 'en cours',
  review: 'en revue',
  done: 'finalisé',
} as const)[s as keyof any] ?? s;

export const MyAuditsPage = () => {
  useDocumentTitle('Mes audits');
  const [items, setItems] = useState<AuditListItemDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchMyAudits()
      .then(setItems)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
  }, []);

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-2xl font-semibold mb-4">Mes audits</h1>
        {loading && <p>Chargement…</p>}
        {error && <div className="text-red-600">{error}</div>}
        {!loading && items.length === 0 && <p>Aucun audit trouvé.</p>}
        <ul className="divide-y">
          {items.map((a) => (
            <li key={a.id} className="py-3 flex items-center justify-between">
              <div>
                <div className="font-medium">{a.number} — {typeLabel(a.type)}</div>
                <div className="text-sm text-gray-600">{a.url}</div>
              </div>
              <div className="text-sm capitalize">{statusLabel(a.status)}</div>
              <div>
                <Link className="underline" to={`/audits/me/${a.id}`}>Détails</Link>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </SiteLayout>
  );
};


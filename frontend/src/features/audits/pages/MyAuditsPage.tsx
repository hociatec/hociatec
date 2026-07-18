import { Link } from 'react-router-dom';
import { useEffect, useRef, useState } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { fetchMyAudits, type AuditListItemDto } from '../api';

const TYPE_LABELS: Record<AuditListItemDto['type'], string> = {
  performance: 'Performance',
  security: 'Sécurité',
  ux: 'UX',
  seo: 'SEO',
  technical: 'Technique',
  accessibility: 'Accessibilité',
};

const STATUS_LABELS: Record<AuditListItemDto['status'], string> = {
  new: 'Non commencé',
  in_progress: 'En cours',
  review: 'En revue',
  done: 'Finalisé',
};

const typeLabel = (t: string) => TYPE_LABELS[t as AuditListItemDto['type']] ?? t;
const statusLabel = (s: string) => STATUS_LABELS[s as AuditListItemDto['status']] ?? s;

export const MyAuditsPage = () => {
  useDocumentTitle('Mes audits');
  const [items, setItems] = useState<AuditListItemDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const pollTimer = useRef<number | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchMyAudits()
      .then(setItems)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
  }, []);

  // Lightweight polling to keep statuses up-to-date
  useEffect(() => {
    // Poll every 15s when tab is visible
    pollTimer.current = window.setInterval(() => {
      if (document.hidden) return;
      void fetchMyAudits()
        .then(setItems)
        .catch(() => {/* silent background error */});
    }, 15000);
    return () => {
      if (pollTimer.current) window.clearInterval(pollTimer.current);
    };
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
              <div className="text-sm">{statusLabel(a.status)}</div>
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

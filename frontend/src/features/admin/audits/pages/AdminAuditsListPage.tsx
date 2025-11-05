import { Link } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { PageContainer } from '@/shared/components/PageContainer';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/api';

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

export const AdminAuditsListPage = () => {
  useDocumentTitle('Admin - Audits');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [items, setItems] = useState<AuditListItemDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError(null);
    void adminFetchAudits()
      .then(setItems)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
  }, [isAdmin]);

  if (guardLoading) {
    return (
      <PageContainer title="Audits">
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }
  if (!isAdmin) {
    return (
      <PageContainer title="Audits">
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer title="Audits">
      {loading && <p>Chargement…</p>}
      {error && <div className="text-red-600">{error}</div>}
      <ul className="divide-y">
        {items.map((a) => (
          <li key={a.id} className="py-3 flex items-center justify-between">
            <div>
              <div className="font-medium">{a.number} — {typeLabel(a.type)}</div>
              <div className="text-sm text-gray-600">{a.url}</div>
            </div>
            <div className="text-sm capitalize">{statusLabel(a.status)}</div>
            <div>
              <Link className="underline" to={`/admin/audits/${a.id}`}>Ouvrir</Link>
            </div>
          </li>
        ))}
      </ul>
    </PageContainer>
  );
};


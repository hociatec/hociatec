import { Link } from 'react-router-dom';
import { useEffect, useMemo, useRef, useState } from 'react';
import { PageContainer } from '@/shared/components/PageContainer';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/api';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';

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
  const pollTimer = useRef<number | null>(null);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState<'all' | AuditListItemDto['status']>('all');
  const [filterType, setFilterType] = useState<'all' | AuditListItemDto['type']>('all');

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError(null);
    void adminFetchAudits()
      .then(setItems)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
  }, [isAdmin]);

  // Poll list every 15s (only when tab is visible and admin)
  useEffect(() => {
    if (!isAdmin) return;
    pollTimer.current = window.setInterval(() => {
      if (document.hidden) return;
      void adminFetchAudits()
        .then(setItems)
        .catch(() => {/* background refresh errors ignored */});
    }, 15000);
    return () => { if (pollTimer.current) window.clearInterval(pollTimer.current); };
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

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    return items.filter((a) => {
      const matchSearch = !q || a.number.toLowerCase().includes(q) || a.url.toLowerCase().includes(q);
      const matchStatus = filterStatus === 'all' || a.status === filterStatus;
      const matchType = filterType === 'all' || a.type === filterType;
      return matchSearch && matchStatus && matchType;
    });
  }, [items, search, filterStatus, filterType]);

  return (
    <PageContainer title="Audits">
      <FilterBar>
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher (numéro, URL)" />
        <SelectFilter
          value={filterType}
          onChange={(v) => setFilterType(v as any)}
          options={[
            { value: 'all', label: 'Tous les types' },
            { value: 'accessibility', label: 'Accessibilité' },
            { value: 'performance', label: 'Performance' },
            { value: 'security', label: 'Sécurité' },
            { value: 'ux', label: 'UX' },
            { value: 'seo', label: 'SEO' },
            { value: 'technical', label: 'Technique' },
          ]}
          ariaLabel="Type"
        />
        <SelectFilter
          value={filterStatus}
          onChange={(v) => setFilterStatus(v as any)}
          options={[
            { value: 'all', label: 'Tous les statuts' },
            { value: 'new', label: statusLabel('new') },
            { value: 'in_progress', label: statusLabel('in_progress') },
            { value: 'review', label: statusLabel('review') },
            { value: 'done', label: statusLabel('done') },
          ]}
          ariaLabel="Statut"
        />
      </FilterBar>

      {loading && <p>Chargement…</p>}
      {error && <div className="text-red-600">{error}</div>}
      <ul className="divide-y">
        {filtered.map((a) => (
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

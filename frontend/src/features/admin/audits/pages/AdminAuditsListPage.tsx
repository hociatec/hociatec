import { Link } from 'react-router-dom';
import { useEffect, useMemo, useRef, useState } from 'react';
import { PageContainer } from '@/shared/components/PageContainer';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/api';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { DateRangeFilter } from '@/shared/components/filters/DateRangeFilter';

const TYPE_LABELS: Record<AuditListItemDto['type'], string> = {
  performance: 'Performance',
  security: 'Sécurité',
  ux: 'UX',
  seo: 'SEO',
  technical: 'Technique',
  accessibility: 'Accessibilité',
};

const STATUS_LABELS: Record<AuditListItemDto['status'], string> = {
  new: 'non commencé',
  in_progress: 'en cours',
  review: 'en revue',
  done: 'finalisé',
};

const typeLabel = (t: string) => TYPE_LABELS[t as AuditListItemDto['type']] ?? t;
const statusLabel = (s: string) => STATUS_LABELS[s as AuditListItemDto['status']] ?? s;

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
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);
  const [sort, setSort] = useState<'date_desc' | 'date_asc' | 'number_asc' | 'number_desc' | 'status_asc' | 'status_desc'>('date_desc');

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
    const fromTs = fromDate ? new Date(fromDate).getTime() : null;
    const toTs = toDate ? new Date(toDate).getTime() : null;
    return items.filter((a) => {
      const matchSearch = !q || a.number.toLowerCase().includes(q) || a.url.toLowerCase().includes(q);
      const matchStatus = filterStatus === 'all' || a.status === filterStatus;
      const matchType = filterType === 'all' || a.type === filterType;
      const createdTs = a.createdAt ? new Date(a.createdAt).getTime() : null;
      const matchFrom = fromTs === null || (createdTs !== null && createdTs >= fromTs);
      const matchTo = toTs === null || (createdTs !== null && createdTs <= toTs);
      return matchSearch && matchStatus && matchType && matchFrom && matchTo;
    });
  }, [items, search, filterStatus, filterType, fromDate, toDate]);

  const view = useMemo(() => {
    const list = [...filtered];
    if (sort === 'date_desc' || sort === 'date_asc') {
      list.sort((a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime());
      if (sort === 'date_desc') list.reverse();
      return list;
    }
    if (sort === 'number_asc' || sort === 'number_desc') {
      list.sort((a, b) => a.number.localeCompare(b.number, 'fr'));
      if (sort === 'number_desc') list.reverse();
      return list;
    }
    if (sort === 'status_asc' || sort === 'status_desc') {
      const order: Record<AuditListItemDto['status'], number> = { new: 0, in_progress: 1, review: 2, done: 3 };
      list.sort((a, b) => order[a.status] - order[b.status]);
      if (sort === 'status_desc') list.reverse();
      return list;
    }
    return list;
  }, [filtered, sort]);

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
        <SelectFilter
          value={sort}
          onChange={(v) => setSort(v as any)}
          options={[
            { value: 'date_desc', label: 'Date: récent → ancien' },
            { value: 'date_asc', label: 'Date: ancien → récent' },
            { value: 'number_asc', label: 'Numéro: A → Z' },
            { value: 'number_desc', label: 'Numéro: Z → A' },
            { value: 'status_asc', label: 'Statut: progression' },
            { value: 'status_desc', label: 'Statut: régression' },
          ]}
          ariaLabel="Tri"
        />
        <DateRangeFilter from={fromDate} to={toDate} onChange={({ from, to }) => { setFromDate(from); setToDate(to); }} />
      </FilterBar>

      {loading && <p>Chargement…</p>}
      {error && <div className="text-red-600">{error}</div>}
      <ul className="divide-y">
        {view.map((a) => (
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

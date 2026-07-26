import { Link } from 'react-router-dom';
import { useEffect, useMemo, useRef, useState } from 'react';

import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/api/auditsApi';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { DateRangeFilter } from '@/shared/components/filters/DateRangeFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

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
const auditTypes = ['all', 'accessibility', 'performance', 'security', 'ux', 'seo', 'technical'] as const;
const auditStatuses = ['all', 'new', 'in_progress', 'review', 'done'] as const;
const sortValues = ['date_desc', 'date_asc', 'number_asc', 'number_desc', 'status_asc', 'status_desc'] as const;
type AuditTypeFilter = (typeof auditTypes)[number];
type AuditStatusFilter = (typeof auditStatuses)[number];
type AuditSort = (typeof sortValues)[number];
const isAuditTypeFilter = (value: string): value is AuditTypeFilter => auditTypes.includes(value as AuditTypeFilter);
const isAuditStatusFilter = (value: string): value is AuditStatusFilter => auditStatuses.includes(value as AuditStatusFilter);
const isAuditSort = (value: string): value is AuditSort => sortValues.includes(value as AuditSort);

export const AdminAuditsListPage = () => {
  useDocumentTitle('Admin - Audits');
  const [items, setItems] = useState<AuditListItemDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const pollTimer = useRef<number | null>(null);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState<AuditStatusFilter>('all');
  const [filterType, setFilterType] = useState<AuditTypeFilter>('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);
  const [sort, setSort] = useState<AuditSort>('date_desc');

  useEffect(() => {
    setLoading(true);
    setError(null);
    void adminFetchAudits()
      .then(setItems)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    pollTimer.current = window.setInterval(() => {
      if (document.hidden) return;
      void adminFetchAudits()
        .then(setItems)
        .catch(() => undefined);
    }, 15000);
    return () => {
      if (pollTimer.current) window.clearInterval(pollTimer.current);
    };
  }, []);

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
    <PageContainer size="admin" title="Audits">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {view.length} audit{view.length > 1 ? 's' : ''} affiché{view.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-stone-500">
          Filtrez par numéro, URL, type, statut et période.
        </p>
      </div>

      <FilterBar>
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher (numéro, URL)" />
        <SelectFilter
          value={filterType}
          onChange={(v) => {
            if (isAuditTypeFilter(v)) setFilterType(v);
          }}
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
          onChange={(v) => {
            if (isAuditStatusFilter(v)) setFilterStatus(v);
          }}
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
          onChange={(v) => {
            if (isAuditSort(v)) setSort(v);
          }}
          options={[
            { value: 'date_desc', label: 'Date : récent → ancien' },
            { value: 'date_asc', label: 'Date : ancien → récent' },
            { value: 'number_asc', label: 'Numéro : A → Z' },
            { value: 'number_desc', label: 'Numéro : Z → A' },
            { value: 'status_asc', label: 'Statut : progression' },
            { value: 'status_desc', label: 'Statut : régression' },
          ]}
          ariaLabel="Tri"
        />
        <DateRangeFilter
          from={fromDate}
          to={toDate}
          onChange={({ from, to }) => {
            setFromDate(from);
            setToDate(to);
          }}
        />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={!loading && view.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucun audit trouvé."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Numéro</th>
                <th scope="col">Type</th>
                <th scope="col">Statut</th>
                <th scope="col">URL</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {view.map((a) => (
                <tr key={a.id}>
                  <th scope="row">
                    <strong>{a.number}</strong>
                    <div className="muted">{formatOptionalFrenchDate(a.createdAt)}</div>
                  </th>
                  <td>{typeLabel(a.type)}</td>
                  <td>{statusLabel(a.status)}</td>
                  <td className="max-w-[320px] truncate">{a.url}</td>
                  <td>
                    <Link
                      className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                      to={`/admin/audits/${a.id}`}
                      aria-label={`Ouvrir l'audit ${a.number}`}
                    >
                      Ouvrir
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};

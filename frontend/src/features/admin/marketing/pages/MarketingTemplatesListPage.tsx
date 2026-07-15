import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { deleteMarketingTemplate, fetchMarketingSegments, fetchMarketingTemplates, type MarketingTemplate } from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const MarketingTemplatesListPage = () => {
  useDocumentTitle('Admin - Templates email');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, { label: string; description: string }>>({});
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState('');
  const [scenarioFilter, setScenarioFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError(null);
    void Promise.all([fetchMarketingTemplates(), fetchMarketingSegments()])
      .then(([templatesList, segmentsList]) => {
        setTemplates(templatesList);
        setSegments(segmentsList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les templates.'))
      .finally(() => setLoading(false));
  }, [isAdmin]);

  const filteredTemplates = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();

    return templates.filter((template) => {
      const matchesQuery = normalizedQuery.length === 0
        || template.name.toLowerCase().includes(normalizedQuery)
        || template.slug.toLowerCase().includes(normalizedQuery)
        || template.subjectTemplate.toLowerCase().includes(normalizedQuery);
      const matchesScenario = scenarioFilter === 'all' || template.scenarioKey === scenarioFilter;
      const matchesStatus = statusFilter === 'all'
        || (statusFilter === 'active' && template.isActive)
        || (statusFilter === 'inactive' && !template.isActive);

      return matchesQuery && matchesScenario && matchesStatus;
    });
  }, [query, scenarioFilter, statusFilter, templates]);

  const handleDelete = async (templateId: number) => {
    if (!window.confirm('Supprimer ce template ?')) return;
    setError(null);
    setMessage(null);
    try {
      await deleteMarketingTemplate(templateId);
      setTemplates((prev) => prev.filter((item) => item.id !== templateId));
      setMessage('Template supprimé.');
    } catch (err: any) {
      setError(err?.message ?? 'Suppression impossible.');
    }
  };

  if (guardLoading) {
    return <PageContainer title="Templates email"><p className="muted">Vérification des droits...</p></PageContainer>;
  }
  if (!isAdmin) {
    return <PageContainer title="Templates email"><div className="register-form__alert">Accès restreint aux administrateurs.</div></PageContainer>;
  }

  return (
    <PageContainer
      title="Templates email"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link
            to="/admin/marketing"
            className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
          >
            Retour aux campagnes
          </Link>
          <Link
            to="/admin/marketing/templates/new"
            className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            Nouveau template
          </Link>
        </div>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {templates.length} template{templates.length > 1 ? 's' : ''} enregistré{templates.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-slate-500">
          Filtrez votre bibliothèque par usage métier, statut ou recherche libre pour retrouver rapidement le bon message.
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      <div className="mb-6 grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.9fr)_minmax(0,0.9fr)]">
        <label className="register-form__field">
          <span className="register-form__label">Recherche</span>
          <input className="register-form__input" placeholder="Nom, slug ou objet..." value={query} onChange={(event) => setQuery(event.target.value)} />
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Scénario</span>
          <select className="register-form__input" value={scenarioFilter} onChange={(event) => setScenarioFilter(event.target.value)}>
            <option value="all">Tous les scénarios</option>
            {Object.entries(segments).map(([key, segment]) => (
              <option key={key} value={key}>
                {segment.label}
              </option>
            ))}
          </select>
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Statut</span>
          <select className="register-form__input" value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
            <option value="all">Tous</option>
            <option value="active">Actifs</option>
            <option value="inactive">Désactivés</option>
          </select>
        </label>
      </div>

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement des templates...
        </div>
      ) : filteredTemplates.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucun template ne correspond aux filtres actuels.
        </div>
      ) : (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Scénario</th>
                <th>Slug</th>
                <th>Statut</th>
                <th>Mise à jour</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredTemplates.map((template) => (
                <tr key={template.id}>
                  <td>
                    <strong>{template.name}</strong>
                    <div className="muted">{template.subjectTemplate}</div>
                  </td>
                  <td>{segments[template.scenarioKey]?.label ?? template.scenarioKey}</td>
                  <td>{template.slug}</td>
                  <td>{template.isActive ? 'Actif' : 'Désactivé'}</td>
                  <td>{template.updatedAt ? new Date(template.updatedAt).toLocaleDateString('fr-FR') : '-'}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link to={`/admin/marketing/templates/${template.id}`} className="catalog-admin-actions__edit">
                        Voir
                      </Link>
                      <Link to={`/admin/marketing/templates/${template.id}/edit`} className="catalog-admin-actions__edit">
                        Modifier
                      </Link>
                      <button type="button" className="catalog-admin-actions__delete" onClick={() => void handleDelete(template.id)}>
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </PageContainer>
  );
};

import { useEffect, useMemo, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';

import { deleteMarketingTemplate, fetchMarketingSegments, fetchMarketingTemplates, type MarketingSegmentDefinition, type MarketingTemplate } from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const MarketingTemplatesListPage = () => {
  const location = useLocation();
  const isTransactionalView = location.pathname.startsWith('/admin/transactional-emails');
  useDocumentTitle(isTransactionalView ? 'Admin - E-mails transactionnels' : 'Admin - Modèles d’e-mail');
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState('');
  const [scenarioFilter, setScenarioFilter] = useState('all');
  const [usageFilter, setUsageFilter] = useState(isTransactionalView ? 'transactional' : 'all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void Promise.all([fetchMarketingTemplates(), fetchMarketingSegments('templates')])
      .then(([templatesList, segmentsList]) => {
        setTemplates(templatesList);
        setSegments(segmentsList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les modèles.'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    setUsageFilter(isTransactionalView ? 'transactional' : 'all');
  }, [isTransactionalView]);

  const filteredTemplates = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();

    return templates.filter((template) => {
      const matchesQuery = normalizedQuery.length === 0
        || template.name.toLowerCase().includes(normalizedQuery)
        || template.slug.toLowerCase().includes(normalizedQuery)
        || template.subjectTemplate.toLowerCase().includes(normalizedQuery);
      const matchesScenario = scenarioFilter === 'all' || template.scenarioKey === scenarioFilter;
      const matchesUsage = usageFilter === 'all'
        || (usageFilter === 'transactional' && segments[template.scenarioKey]?.type === 'transactional')
        || (usageFilter === 'campaign' && segments[template.scenarioKey]?.type !== 'transactional');
      const matchesStatus = statusFilter === 'all'
        || (statusFilter === 'active' && template.isActive)
        || (statusFilter === 'inactive' && !template.isActive);

      return matchesQuery && matchesScenario && matchesUsage && matchesStatus;
    });
  }, [query, scenarioFilter, statusFilter, templates, usageFilter, segments]);

  const handleDelete = async (templateId: number) => {
    if (!window.confirm('Supprimer ce modèle ?')) return;
    setError(null);
    setMessage(null);
    try {
      await deleteMarketingTemplate(templateId);
      setTemplates((prev) => prev.filter((item) => item.id !== templateId));
      setMessage('Modèle supprimé.');
    } catch (err: any) {
      setError(err?.message ?? 'Suppression impossible.');
    }
  };

  return (
    <PageContainer
      title={isTransactionalView ? 'E-mails transactionnels' : 'Modèles d’e-mail'}
      headerActions={
        <div className="flex flex-wrap gap-3">
          {isTransactionalView ? (
            <Link
              to="/admin"
              className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
            >
              Retour au dashboard
            </Link>
          ) : (
            <Link
              to="/admin/marketing"
              className="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900"
            >
              Retour aux campagnes
            </Link>
          )}
          <Link
            to={isTransactionalView ? '/admin/transactional-emails/new' : '/admin/marketing/templates/new'}
            className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            Nouveau modèle
          </Link>
        </div>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {templates.length} modèle{templates.length > 1 ? 's' : ''} enregistré{templates.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-slate-500">
          {isTransactionalView
            ? 'Gérez ici les e-mails automatiques envoyés pour les commandes et les factures.'
            : 'Filtrez votre bibliothèque par usage métier, statut ou recherche libre pour retrouver rapidement le bon message.'}
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      <div className="mb-6 grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-4">
        <label className="register-form__field">
          <span className="register-form__label">Recherche</span>
          <input className="register-form__input" placeholder="Nom, slug ou objet..." value={query} onChange={(event) => setQuery(event.target.value)} />
        </label>
        {!isTransactionalView ? (
          <label className="register-form__field">
            <span className="register-form__label">Usage</span>
            <select className="register-form__input" value={usageFilter} onChange={(event) => setUsageFilter(event.target.value)}>
              <option value="all">Tous</option>
              <option value="transactional">Transactionnels</option>
              <option value="campaign">Marketing</option>
            </select>
          </label>
        ) : null}
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
          Chargement des modèles...
        </div>
      ) : filteredTemplates.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucun modèle ne correspond aux filtres actuels.
        </div>
      ) : (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Scénario</th>
                <th scope="col">Slug</th>
                <th scope="col">Statut</th>
                <th scope="col">Mise à jour</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredTemplates.map((template) => (
                <tr key={template.id}>
                  <th scope="row">
                    <strong>{template.name}</strong>
                    <div className="muted">{template.subjectTemplate}</div>
                  </th>
                  <td>
                    <div>{segments[template.scenarioKey]?.label ?? template.scenarioKey}</div>
                    <div className="muted">{segments[template.scenarioKey]?.type === 'transactional' ? 'Transactionnel' : 'Marketing'}</div>
                  </td>
                  <td>{template.slug}</td>
                  <td>{template.isActive ? 'Actif' : 'Désactivé'}</td>
                  <td>{template.updatedAt ? new Date(template.updatedAt).toLocaleDateString('fr-FR') : '-'}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={isTransactionalView ? `/admin/transactional-emails/${template.id}` : `/admin/marketing/templates/${template.id}`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Voir le modèle ${template.name}`}
                      >
                        Voir
                      </Link>
                      <Link
                        to={isTransactionalView ? `/admin/transactional-emails/${template.id}/edit` : `/admin/marketing/templates/${template.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier le modèle ${template.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(template.id)}
                        aria-label={`Supprimer le modèle ${template.name}`}
                      >
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

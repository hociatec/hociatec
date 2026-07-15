import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { deleteMarketingTemplate, fetchMarketingTemplates, type MarketingTemplate } from '@/features/admin/marketing/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const MarketingTemplatesListPage = () => {
  useDocumentTitle('Admin - Templates email');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError(null);
    void fetchMarketingTemplates()
      .then(setTemplates)
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les templates.'))
      .finally(() => setLoading(false));
  }, [isAdmin]);

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
        <Link
          to="/admin/marketing/templates/new"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Nouveau template
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {templates.length} template{templates.length > 1 ? 's' : ''} disponible{templates.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-slate-500">
          Préparez vos contenus réutilisables par situation métier.
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement des templates...
        </div>
      ) : templates.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucun template enregistré.
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
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {templates.map((template) => (
                <tr key={template.id}>
                  <td>
                    <strong>{template.name}</strong>
                    <div className="muted">{template.subjectTemplate}</div>
                  </td>
                  <td>{template.scenarioKey}</td>
                  <td>{template.slug}</td>
                  <td>{template.isActive ? 'Actif' : 'Désactivé'}</td>
                  <td>
                    <div className="catalog-admin-actions">
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

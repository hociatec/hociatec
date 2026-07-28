import { useEffect, useState } from 'react';
import { Link } from 'react-router';

import { deleteAdminNewsArticle, fetchAdminNewsArticles, sendAdminNewsArticleEmail, type NewsArticleDto } from '@/features/news/api/newsApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const AdminNewsListPage = () => {
  useDocumentTitle('Admin - Actualités');
  const [items, setItems] = useState<NewsArticleDto[]>([]);
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const reload = () => {
    setLoading(true);
    void fetchAdminNewsArticles({ q: query })
      .then((result) => {
        setItems(result.items);
        setError(null);
      })
      .catch((reason) => setError(reason instanceof Error ? reason.message : 'Erreur de chargement.'))
      .finally(() => setLoading(false));
  };

  useEffect(reload, [query]);

  const handleDelete = async (article: NewsArticleDto) => {
    if (!window.confirm(`Supprimer l’actualité « ${article.title} » ?`)) return;
    await deleteAdminNewsArticle(article.id);
    setMessage('Actualité supprimée.');
    reload();
  };

  const handleSendEmail = async (article: NewsArticleDto) => {
    if (!window.confirm(`Envoyer l’actualité « ${article.title} » par e-mail aux abonnés ?`)) return;
    await sendAdminNewsArticleEmail(article.id);
    setMessage('Envoi des e-mails d’actualité planifié.');
  };

  return (
    <PageContainer
      size="admin"
      title="Actualités"
      headerActions={<PrimaryLink to="/admin/news/new">Nouvelle actualité</PrimaryLink>}
    >
      {message ? <FeedbackMessage variant="success">{message}</FeedbackMessage> : null}
      {error ? <FeedbackMessage>{error}</FeedbackMessage> : null}
      <div className="mb-6 rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
        <SearchFilter value={query} onChange={setQuery} placeholder="Rechercher une actualité..." />
      </div>
      <AdminListState loading={loading} isEmpty={items.length === 0} loadingLabel="Chargement des actualités..." emptyLabel="Aucune actualité.">
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Titre</th>
                <th scope="col">Catégorie</th>
                <th scope="col">Publication</th>
                <th scope="col">Vues uniques</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {items.map((article) => (
                <tr key={article.id}>
                  <th scope="row">
                    <strong>{article.title}</strong>
                    <div className="muted">{article.slug}</div>
                  </th>
                  <td>{article.category ?? 'Non définie'}</td>
                  <td>{article.isPublished ? 'Publiée' : 'Brouillon'}</td>
                  <td>{article.viewsCount}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link to={`/actualites/${article.slug}`} className="catalog-admin-actions__edit">Voir</Link>
                      <Link to={`/admin/news/${article.id}/edit`} className="catalog-admin-actions__edit">Modifier</Link>
                      {article.isPublished ? (
                        <button type="button" className="catalog-admin-actions__edit" onClick={() => void handleSendEmail(article)}>Envoyer par e-mail</button>
                      ) : null}
                      <button type="button" className="catalog-admin-actions__delete" onClick={() => void handleDelete(article)}>Supprimer</button>
                    </div>
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

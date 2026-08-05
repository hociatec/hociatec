import { useState } from 'react';
import { Link } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { deleteAdminNewsArticle, fetchAdminNewsArticles, sendAdminNewsArticleEmail, type NewsArticleDto } from '@/features/news/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminNewsQueryKeys } from '@/shared/lib/queryKeys';

export const AdminNewsListPage = () => {
  useDocumentTitle('Admin - Actualités');
  const queryClient = useQueryClient();
  const confirm = useConfirm();
  const [query, setQuery] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const newsQuery = useQuery({
    queryKey: adminNewsQueryKeys.list(query),
    queryFn: () => fetchAdminNewsArticles({ q: query }),
  });
  const items: NewsArticleDto[] = newsQuery.data?.items ?? [];
  const deleteMutation = useMutation({
    mutationFn: deleteAdminNewsArticle,
    onSuccess: () => {
      setMessage('Actualité supprimée.');
      void queryClient.invalidateQueries({ queryKey: ['admin', 'news'] });
    },
  });
  const sendEmailMutation = useMutation({
    mutationFn: sendAdminNewsArticleEmail,
    onSuccess: () => setMessage('Envoi des e-mails d’actualité planifié.'),
  });

  const handleDelete = async (article: NewsArticleDto) => {
    if (
      !(await confirm({
        title: 'Supprimer l’actualité',
        description: `Supprimer définitivement l’actualité « ${article.title} » ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    ) {
      return;
    }

    deleteMutation.mutate(article.id);
  };

  const handleSendEmail = async (article: NewsArticleDto) => {
    if (
      !(await confirm({
        title: 'Envoyer l’actualité',
        description: `Planifier l’envoi de l’actualité « ${article.title} » aux abonnés ?`,
        confirmLabel: 'Envoyer',
        cancelLabel: 'Annuler',
      }))
    ) {
      return;
    }

    sendEmailMutation.mutate(article.id);
  };

  return (
    <PageContainer
      size="admin"
      title="Actualités"
      headerActions={<PrimaryLink to="/admin/news/new">Nouvelle actualité</PrimaryLink>}
    >
      {message ? <FeedbackMessage variant="success">{message}</FeedbackMessage> : null}
      {newsQuery.error || deleteMutation.error || sendEmailMutation.error ? (
        <FeedbackMessage>
          {(newsQuery.error ?? deleteMutation.error ?? sendEmailMutation.error) instanceof Error
            ? (newsQuery.error ?? deleteMutation.error ?? sendEmailMutation.error)?.message
            : 'Erreur de chargement.'}
        </FeedbackMessage>
      ) : null}
      <div className="mb-6 rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
        <SearchFilter value={query} onChange={setQuery} placeholder="Rechercher une actualité..." />
      </div>
      <AdminListState loading={newsQuery.isLoading} isEmpty={!newsQuery.error && items.length === 0} loadingLabel="Chargement des actualités..." emptyLabel="Aucune actualité.">
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

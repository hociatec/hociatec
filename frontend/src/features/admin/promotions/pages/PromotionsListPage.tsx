import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { deletePromotion, fetchPromotionAudiences, fetchPromotions, type Promotion } from '@/features/admin/promotions/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const formatDiscount = (promotion: Promotion) =>
  promotion.discountType === 'percent'
    ? `${promotion.discountValue}%`
    : new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(promotion.discountValue / 100);

export const PromotionsListPage = () => {
  useDocumentTitle('Admin - Promotions');
  const toast = useToast();
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [promotions, setPromotions] = useState<Promotion[]>([]);
  const [audiences, setAudiences] = useState<Record<string, { label: string; description: string }>>({});
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError(null);
    void Promise.all([fetchPromotions(), fetchPromotionAudiences()])
      .then(([promotionsList, audienceList]) => {
        setPromotions(promotionsList);
        setAudiences(audienceList);
      })
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les promotions.'))
      .finally(() => setLoading(false));
  }, [isAdmin]);

  const filteredPromotions = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();

    return promotions.filter((promotion) => {
      const matchesQuery = normalizedQuery.length === 0
        || promotion.name.toLowerCase().includes(normalizedQuery)
        || promotion.slug.toLowerCase().includes(normalizedQuery);
      const matchesStatus = statusFilter === 'all'
        || (statusFilter === 'active' && promotion.isActive)
        || (statusFilter === 'inactive' && !promotion.isActive);

      return matchesQuery && matchesStatus;
    });
  }, [promotions, query, statusFilter]);

  const handleDelete = async (promotionId: number) => {
    const promotion = promotions.find((item) => item.id === promotionId);
    const promotionLabel = promotion ? `"${promotion.name}" (${promotion.slug})` : 'cette promotion';

    if (!window.confirm(`Supprimer ${promotionLabel} ?`)) return;
    try {
      await deletePromotion(promotionId);
      setPromotions((prev) => prev.filter((item) => item.id !== promotionId));
      toast.show('Promotion supprimée.', { variant: 'success' });
    } catch (err: any) {
      const message = err?.message ?? 'Suppression impossible.';
      setError(message);
      toast.show(message, { variant: 'error' });
    }
  };

  if (guardLoading) {
    return <PageContainer title="Promotions"><p className="muted">Vérification des droits...</p></PageContainer>;
  }
  if (!isAdmin) {
    return <PageContainer title="Promotions"><div className="register-form__alert">Accès restreint aux administrateurs.</div></PageContainer>;
  }

  return (
    <PageContainer
      title="Promotions"
      headerActions={
        <div className="flex gap-3">
          <Link
            to="/admin/promotions/new"
            className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            Nouvelle promotion
          </Link>
        </div>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          Créez des remises automatiques.
        </p>
        <p className="text-sm text-slate-500">
          La meilleure promotion éligible est appliquée automatiquement dans le panier.
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}

      <div className="mb-6 grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2">
        <label className="register-form__field">
          <span className="register-form__label">Recherche</span>
          <input className="register-form__input" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Nom ou slug..." />
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Statut</span>
          <select className="register-form__input" value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
            <option value="all">Toutes</option>
            <option value="active">Actives</option>
            <option value="inactive">Inactives</option>
          </select>
        </label>
      </div>

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement...
        </div>
      ) : filteredPromotions.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucune promotion.
        </div>
      ) : (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Remise</th>
                <th scope="col">Audience</th>
                <th scope="col">Statut</th>
                <th scope="col">Validité</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredPromotions.map((promotion) => (
                <tr key={promotion.id}>
                  <th scope="row">
                    <strong>{promotion.name}</strong>
                    <div className="muted">{promotion.slug}</div>
                  </th>
                  <td>{formatDiscount(promotion)}</td>
                  <td>{audiences[promotion.audienceKey]?.label ?? promotion.audienceKey}</td>
                  <td>{promotion.isActive ? 'Active' : 'Inactive'}</td>
                  <td>
                    {promotion.startsAt ? new Date(promotion.startsAt).toLocaleDateString('fr-FR') : 'Immédiat'}
                    {' - '}
                    {promotion.endsAt ? new Date(promotion.endsAt).toLocaleDateString('fr-FR') : 'Sans fin'}
                  </td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/promotions/${promotion.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier la promotion ${promotion.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(promotion.id)}
                        aria-label={`Supprimer la promotion ${promotion.name}`}
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

import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';
import { fetchFavorites, removeFavorite, type FavoriteDto } from '../api';

const formatPrice = (cents: number, sellingType: 'sale' | 'rental') => {
  const value = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(cents / 100);

  return sellingType === 'rental' ? `${value} / mois` : value;
};

const formatDate = (value: string) => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

export const MyFavoritesPage = () => {
  useDocumentTitle('Mes favoris');
  const { show } = useToast();

  const [favorites, setFavorites] = useState<FavoriteDto[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [error, setError] = useState<string | null>(null);
  const [removingId, setRemovingId] = useState<number | null>(null);

  const loadFavorites = useCallback(() => {
    setStatus('loading');
    setError(null);

    void fetchFavorites()
      .then((items) => {
        setFavorites(items);
        setStatus('success');
      })
      .catch((err: unknown) => {
        const message =
          err instanceof Error ? err.message : 'Une erreur est survenue en chargeant vos favoris.';
        setError(message);
        setStatus('error');
      });
  }, []);

  useEffect(() => {
    loadFavorites();
  }, [loadFavorites]);

  const handleRemove = (productId: number) => {
    setRemovingId(productId);
    void removeFavorite(productId)
      .then(() => {
        setFavorites((prev) => prev.filter((favorite) => favorite.product.id !== productId));
        show('Produit retire de vos favoris.');
      })
      .catch((err: unknown) => {
        const message =
          err instanceof Error ? err.message : 'Impossible de retirer ce produit des favoris.';
        show(message, { variant: 'error' });
      })
      .finally(() => setRemovingId((current) => (current === productId ? null : current)));
  };

  const hasFavorites = favorites.length > 0;

  return (
    <SiteLayout>
      <div className="max-w-5xl mx-auto px-4 py-10 space-y-8">
        <header className="space-y-3 text-slate-800">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">
            Mon espace
          </p>
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <h1 className="text-3xl font-semibold">Mes favoris</h1>
              <p className="text-slate-600">
                Retrouvez rapidement les produits que vous avez mis de cote pour vos prochains
                projets.
              </p>
            </div>
            {hasFavorites && (
              <button
                type="button"
                className="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:border-slate-500"
                onClick={loadFavorites}
                disabled={status === 'loading'}
              >
                ↻ Actualiser
              </button>
            )}
          </div>
        </header>

        {status === 'loading' && (
          <div className="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-slate-600">
            Chargement de vos favoris...
          </div>
        )}

        {status === 'error' && (
          <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-800">
            <p className="font-semibold">Impossible de charger vos favoris.</p>
            {error && <p className="text-sm text-red-700 mt-2">{error}</p>}
            <button
              type="button"
              className="mt-4 inline-flex items-center rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
              onClick={loadFavorites}
            >
              Reessayer
            </button>
          </div>
        )}

        {status === 'success' && !hasFavorites && (
          <div className="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center shadow-sm">
            <h2 className="text-2xl font-semibold text-slate-800">Aucun favori pour le moment</h2>
            <p className="mt-3 text-slate-600">
              Explorez le catalogue et ajoutez vos produits preferes pour les retrouver facilement.
            </p>
            <Link
              to="/catalogue/vente"
              className="mt-5 inline-flex items-center justify-center rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
              Explorer le catalogue
            </Link>
          </div>
        )}

        {status === 'success' && hasFavorites && (
          <ul className="space-y-4">
            {favorites.map((favorite) => {
              const product = favorite.product;
              const unitPriceCents = product.effectivePriceCents ?? product.priceCents;
              return (
                <li
                  key={product.id}
                  className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center"
                >
                  <Link
                    to={`/catalogue/produits/${product.slug}`}
                    className="flex flex-1 items-center gap-4 text-left"
                  >
                    <div className="h-24 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-slate-100">
                      {product.imageUrl ? (
                        <img
                          src={product.imageUrl}
                          alt={product.imageAlt ?? product.name}
                          className="h-full w-full object-cover"
                        />
                      ) : (
                        <div className="flex h-full w-full items-center justify-center text-2xl font-semibold text-slate-400">
                          {product.name.charAt(0).toUpperCase()}
                        </div>
                      )}
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400">
                        {product.category.name}
                      </p>
                      <h3 className="text-xl font-semibold text-slate-900">{product.name}</h3>
                      {product.shortDescription && (
                        <p className="text-sm text-slate-600">{product.shortDescription}</p>
                      )}
                      <p className="text-xs text-slate-500">
                        Ajoute le {formatDate(favorite.addedAt) || 'inconnu'}
                      </p>
                    </div>
                  </Link>
                  <div className="flex flex-col items-start gap-3 md:items-end">
                    <p className="text-lg font-semibold text-slate-900">
                      {formatPrice(unitPriceCents, product.sellingType)}
                    </p>
                    <div className="flex flex-wrap items-center gap-3">
                      <Link
                        to={`/catalogue/produits/${product.slug}`}
                        className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-slate-500"
                      >
                        Voir le produit
                      </Link>
                      <button
                        type="button"
                        onClick={() => handleRemove(product.id)}
                        disabled={removingId === product.id}
                        className="inline-flex items-center rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:border-red-400 disabled:cursor-not-allowed disabled:opacity-60"
                      >
                        {removingId === product.id ? 'Retrait...' : 'Retirer'}
                      </button>
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </div>
    </SiteLayout>
  );
};

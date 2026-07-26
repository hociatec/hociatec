import { Link } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatEuroCents, formatFrenchDate } from '@/shared/lib/formatters';
import { useFavorites } from '../hooks/useFavorites';

const formatPrice = (cents: number, priceUnitLabel: string | null) => {
  const value = formatEuroCents(cents);

  return `${value}${priceUnitLabel ? ` ${priceUnitLabel}` : ''}`;
};

export const MyFavoritesPage = () => {
  useDocumentTitle('Mes favoris');
  const { show } = useToast();

  const { favorites, status, error, removingId, refresh: loadFavorites, remove } = useFavorites();

  const handleRemove = (productId: number) => {
    void remove(productId)
      .then(() => show('Produit retiré de vos favoris.'))
      .catch((err: unknown) => {
        const message =
          err instanceof Error ? err.message : 'Impossible de retirer ce produit des favoris.';
        show(message, { variant: 'error' });
      });
  };

  const hasFavorites = favorites.length > 0;

  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-10 space-y-8">
        <header className="space-y-3 text-stone-800">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-stone-500">
            Mon espace
          </p>
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <h1 className="text-3xl font-semibold text-brand-900">Mes favoris</h1>
              <p className="mt-2 max-w-2xl text-stone-600">
                Retrouvez rapidement les produits que vous avez mis de côté pour vos prochains
                projets.
              </p>
            </div>
            {hasFavorites && (
              <button
                type="button"
                className="inline-flex items-center gap-2 rounded-full border border-brand-200 px-5 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
                onClick={loadFavorites}
                disabled={status === 'loading'}
              >
                ↻ Actualiser
              </button>
            )}
          </div>
        </header>

        {status === 'loading' && <LoadingState>Chargement de vos favoris...</LoadingState>}

        {status === 'error' && (
          <ErrorState>
            <p className="font-semibold">Impossible de charger vos favoris.</p>
            {error && <p className="mt-2 text-sm text-red-700">{error}</p>}
            <button
              type="button"
              className="mt-4 inline-flex items-center rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
              onClick={loadFavorites}
            >
              Réessayer
            </button>
          </ErrorState>
        )}

        {status === 'success' && !hasFavorites && (
          <EmptyState>
            <h2 className="text-2xl font-semibold text-brand-900">Aucun favori pour le moment</h2>
            <p className="mt-3 text-stone-600">
              Explorez le catalogue et ajoutez vos produits préférés pour les retrouver facilement.
            </p>
            <Link
              to="/catalogue/vente"
              className="mt-5 inline-flex items-center justify-center rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800"
            >
              Explorer le catalogue
            </Link>
          </EmptyState>
        )}

        {status === 'success' && hasFavorites && (
          <ul className="space-y-4">
            {favorites.map((favorite) => {
              const product = favorite.product;
              const unitPriceCents = product.effectivePriceCents ?? product.priceCents;
              return (
                <li
                  key={product.id}
                  className="rounded-xl border border-brand-100 bg-white p-4 shadow-sm transition hover:border-brand-200"
                >
                  <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <Link
                      to={`/catalogue/produits/${product.slug}`}
                      className="flex flex-1 items-center gap-4 text-left"
                    >
                      <div className="h-24 w-24 flex-shrink-0 overflow-hidden rounded-2xl bg-brand-50">
                        {product.imageUrl ? (
                          <img
                            src={product.imageUrl}
                            alt={product.imageAlt ?? product.name}
                            className="h-full w-full object-cover"
                          />
                        ) : (
                          <div className="flex h-full w-full items-center justify-center text-2xl font-semibold text-stone-400">
                            {product.name.charAt(0).toUpperCase()}
                          </div>
                        )}
                      </div>
                      <div className="space-y-1">
                        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-stone-400">
                          {product.category.name}
                        </p>
                        <h2 className="text-xl font-semibold text-brand-900">{product.name}</h2>
                        {product.shortDescription && (
                          <p className="text-sm text-stone-600">{product.shortDescription}</p>
                        )}
                        <p className="text-xs text-stone-500">
                          Ajouté le {formatFrenchDate(favorite.addedAt) || 'inconnu'}
                        </p>
                      </div>
                    </Link>
                    <div className="flex flex-col items-start gap-3 lg:items-end">
                      <p className="text-lg font-semibold text-brand-900">
                        {formatPrice(unitPriceCents, product.priceUnitLabel)}
                      </p>
                      <div className="flex flex-wrap items-center gap-3">
                        <Link
                          to={`/catalogue/produits/${product.slug}`}
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                        >
                          Voir le produit
                        </Link>
                        <button
                          type="button"
                          onClick={() => handleRemove(product.id)}
                          disabled={removingId === product.id}
                          className="inline-flex items-center rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:border-red-400 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                          {removingId === product.id ? 'Retrait...' : 'Retirer'}
                        </button>
                      </div>
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

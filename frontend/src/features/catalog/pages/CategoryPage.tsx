import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { ProductCard } from '../components/ProductCard';
import { fetchPublicCategory, type CategoryWithProducts } from '../api';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
import { useCatalogMenu } from '@/features/catalog/hooks/useCatalogMenu';

import './CatalogPages.css';

export const CategoryPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const [data, setData] = useState<CategoryWithProducts | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const { categories: catalogCategories } = useCatalogMenu();
  const navigate = useNavigate();

  useDocumentTitle(
    data?.category ? `${data.category.name} - Catalogue` : 'Catalogue - Categorie',
  );

  useEffect(() => {
    if (!slug) return;
    setLoading(true);
    setError(null);

    void fetchPublicCategory(slug)
      .then((result) => setData(result))
      .catch((err: Error) => setError(err.message || 'Categorie introuvable.'))
      .finally(() => setLoading(false));
  }, [slug]);

  const products = useMemo(() => data?.products ?? [], [data]);

  return (
    <SiteLayout headerVariant="light">
      <div className="catalog-detail-layout">
        <Link to="/" className="catalog-page__breadcrumbs">
          Retour a l'accueil
        </Link>

        {catalogCategories.length > 0 && (
          <nav className="catalog-category-nav" aria-label="Autres categories">
            {catalogCategories.map((category) => (
              <button
                key={category.id}
                type="button"
                className={[
                  'catalog-category-nav__item',
                  category.slug === slug ? 'is-active' : '',
                ]
                  .filter(Boolean)
                  .join(' ')}
                onClick={() => navigate(`/catalogue/${category.slug}`)}
                aria-pressed={category.slug === slug}
              >
                {category.name}
              </button>
            ))}
          </nav>
        )}

        {loading && <p>Chargement de la categorie...</p>}
        {error && <div className="register-form__alert">{error}</div>}

        {!loading && !error && data && (
          <>
            <header className="catalog-detail-header">
              <span className="catalog-badge">Categorie</span>
              <h1>{data.category.name}</h1>
              <div className="catalog-detail-metadata">
                <span>
                  {products.length} solution{products.length > 1 ? 's' : ''} disponibles
                </span>
                <span>Actualise le {new Date(data.category.updatedAt).toLocaleDateString()}</span>
              </div>
              {data.category.description && (
                <p style={{ color: '#1e293b', maxWidth: 720 }}>{data.category.description}</p>
              )}
            </header>

            <section className="catalog-grid catalog-grid--products">
              {products.length === 0 ? (
                <div className="catalog-empty-state">
                  Aucun produit n&apos;est publie dans cette categorie pour le moment.
                </div>
              ) : (
                products.map((product) => (
                  <ProductCard
                    key={product.id}
                    product={product}
                    actionSlot={<ProductCartActions product={product} />}
                  />
                ))
              )}
            </section>
          </>
        )}
      </div>
    </SiteLayout>
  );
};



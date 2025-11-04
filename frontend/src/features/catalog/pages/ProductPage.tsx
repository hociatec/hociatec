import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

import { fetchPublicProduct, type CatalogProduct } from '../api';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { ProductCartActions } from '@/features/cart/components/ProductCartActions';

import './CatalogPages.css';

const formatPrice = (priceCents: number) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(priceCents / 100);

const formatDate = (value: string) => {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

export const ProductPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const [product, setProduct] = useState<CatalogProduct | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeSlide, setActiveSlide] = useState(0);

  useDocumentTitle(product ? `${product.name} - Catalogue` : 'Produit - Catalogue');

  useEffect(() => {
    if (!slug) return;
    setLoading(true);
    setError(null);

    void fetchPublicProduct(slug)
      .then((result) => setProduct(result))
      .catch((err: Error) => setError(err.message || 'Produit introuvable.'))
      .finally(() => setLoading(false));
  }, [slug]);

  useEffect(() => {
    setActiveSlide(0);
  }, [product?.id]);

  useEffect(() => {
    if (!product || product.gallery.length <= 1) {
      return;
    }

    const interval = window.setInterval(() => {
      setActiveSlide((previous) => (previous + 1) % product.gallery.length);
    }, 5000);

    return () => window.clearInterval(interval);
  }, [product]);

  const slides =
    product && product.gallery.length > 0
      ? product.gallery
      : product && product.imageUrl
        ? [
            {
              position: 0,
              url: product.imageUrl,
              alt: product.imageAlt ?? product.name,
              isPrimary: true,
            },
          ]
        : [];

  const productDates = useMemo(() => {
    if (!product) return null;

    return {
      created: formatDate(product.createdAt),
      updated: formatDate(product.updatedAt),
    };
  }, [product]);

  const handleNextSlide = () => {
    if (!product || slides.length <= 1) return;
    setActiveSlide((previous) => (previous + 1) % slides.length);
  };

  const handlePrevSlide = () => {
    if (!product || slides.length <= 1) return;
    setActiveSlide((previous) => (previous - 1 + slides.length) % slides.length);
  };

  return (
    <SiteLayout headerVariant="light">
      <div className="catalog-detail-layout">
        <Link
          to={product ? `/catalogue/${product.category.slug}` : '/'}
          className="catalog-page__breadcrumbs"
        >
          Retour
        </Link>

        {loading && <p>Chargement du produit...</p>}
        {error && <div className="register-form__alert">{error}</div>}

        {!loading && !error && product && (
          <>
            <div className="catalog-detail-hero">
              {slides.length > 0 ? (
                <div className="catalog-slider">
                  <div className="catalog-slider__viewport">
                    {slides.map((slide, index) => (
                      <img
                        key={slide.url + index}
                        src={slide.url}
                        alt={slide.alt}
                        className={`catalog-slider__image${index === activeSlide ? ' is-active' : ''}`}
                      />
                    ))}
                  </div>
                  {slides.length > 1 && (
                    <>
                      <button
                        type="button"
                        className="catalog-slider__control catalog-slider__control--prev"
                        onClick={handlePrevSlide}
                        aria-label="Image precedente"
                      >
                        ‹
                      </button>
                      <button
                        type="button"
                        className="catalog-slider__control catalog-slider__control--next"
                        onClick={handleNextSlide}
                        aria-label="Image suivante"
                      >
                        ›
                      </button>
                      <div className="catalog-slider__dots" role="tablist">
                        {slides.map((slide, index) => (
                          <button
                            key={slide.url + index}
                            type="button"
                            className={`catalog-slider__dot${index === activeSlide ? ' is-active' : ''}`}
                            onClick={() => setActiveSlide(index)}
                            aria-label={`Afficher l'image ${index + 1}`}
                            aria-pressed={index === activeSlide}
                          />
                        ))}
                      </div>
                    </>
                  )}
                </div>
              ) : (
                <div className="catalog-product-card__placeholder" style={{ height: 320 }}>
                  {product.name.charAt(0).toUpperCase()}
                </div>
              )}
            </div>

            <header className="catalog-detail-header">
              <span className="catalog-badge">{product.category.name}</span>
              <h1>{product.name}</h1>
              <p className="catalog-detail-summary">
                {product.shortDescription ??
                  'Une solution personnalisee pour accelerer vos projets numeriques.'}
              </p>
              <div className="catalog-detail-actions">
                <ProductCartActions product={product} variant="detail" />
              </div>
            </header>

            <section className="catalog-detail-highlight">
              <div className="catalog-highlight-card">
                <h2>Informations cles</h2>
                <dl>
                  <div>
                    <dt>Prix public</dt>
                    <dd>
                      {formatPrice(product.priceCents)}{product.sellingType === 'rental' ? ' / mois' : ''}
                    </dd>
                  </div>
                  <div>
                    <dt>Reference</dt>
                    <dd>{product.sku}</dd>
                  </div>
                  <div>
                    <dt>Disponibilite</dt>
                    <dd>
                      {product.stock > 0
                        ? `${product.stock} exemplaire${product.stock > 1 ? 's' : ''} en stock`
                        : 'Sur commande'}
                    </dd>
                  </div>
                  <div>
                    <dt>Mise a jour</dt>
                    <dd>{productDates?.updated ?? '-'}</dd>
                  </div>
                  <div>
                    <dt>Creation</dt>
                    <dd>{productDates?.created ?? '-'}</dd>
                  </div>
                  <div>
                    <dt>Categorie</dt>
                    <dd>{product.category.name}</dd>
                  </div>
                  <div>
                    <dt>Visibilite</dt>
                    <dd>{product.isPublished ? 'Publie' : 'Non publie'}</dd>
                  </div>
                  <div>
                    <dt>Mise en avant</dt>
                    <dd>{product.isFeaturedHome ? "Present sur l'accueil" : 'Classique'}</dd>
                  </div>
                </dl>
              </div>
              <div className="catalog-highlight-card">
                <h2>Contact et accompagnement</h2>
                <p>
                  Notre equipe peut adapter ce produit a votre contexte et vous accompagner dans sa
                  mise en oeuvre.
                </p>
                <div className="catalog-detail-actions--secondary">
                  <Link to="/register" className="hero__button hero__button--primary">
                    Demarrer votre projet
                  </Link>
                  <a
                    href="mailto:contact@hociatec.com"
                    className="hero__button hero__button--ghost"
                  >
                    Parler a un conseiller
                  </a>
                </div>
              </div>
            </section>

            <section className="catalog-detail-content">
              <h2>Ce que nous vous apportons</h2>
              <p>{product.description}</p>
            </section>
          </>
        )}
      </div>
    </SiteLayout>
  );
};



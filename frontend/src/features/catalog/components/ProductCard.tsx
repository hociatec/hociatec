import '../pages/CatalogPages.css';

import { Link } from 'react-router-dom';

import type { CatalogProduct } from '../api';
import { ProductMetaBadges } from './ProductMetaBadges';

interface ProductCardProps {
  product: CatalogProduct;
  actionSlot?: React.ReactNode;
}

const formatPrice = (priceCents: number) => (priceCents / 100).toFixed(2).replace('.', ',');

export const ProductCard = ({ product, actionSlot }: ProductCardProps) => {
  const productLink = `/catalogue/produits/${product.slug}`;
  const compactSpecs = [
    product.brand?.trim(),
    product.storageCapacity?.trim(),
    product.memoryRam?.trim(),
    product.color?.trim(),
  ]
    .filter((value): value is string => Boolean(value))
    .join(' • ');

  return (
    <article className="catalog-product-card">
      {product.imageUrl ? (
        <Link to={productLink} className="catalog-product-card__image-link">
          <img
            src={product.imageUrl}
            alt={product.imageAlt ?? product.name}
            className="catalog-product-card__image"
          />
        </Link>
      ) : (
        <Link to={productLink} className="catalog-product-card__image-link">
          <div className="catalog-product-card__placeholder" aria-hidden="true">
            <span>{product.name.charAt(0).toUpperCase()}</span>
          </div>
        </Link>
      )}
      <div className="catalog-product-card__content">
        <header className="catalog-product-card__header">
          <span className="catalog-product-card__sku">{product.sku}</span>
          <h3 className="catalog-product-card__title">
            <Link to={productLink} className="catalog-product-card__title-link">
              {product.name}
            </Link>
          </h3>
          {/* Suppression du badge "Accueil" */}
        </header>
        <ProductMetaBadges
          sellingType={product.sellingType}
          categoryName={product.category.name}
        />
        {compactSpecs.length > 0 && (
          <p className="catalog-product-card__spec-summary" aria-label="Caractéristiques principales">
            {compactSpecs}
          </p>
        )}
        {product.shortDescription && (
          <p className="catalog-product-card__excerpt">{product.shortDescription}</p>
        )}
        <footer className="catalog-product-card__footer">
          <span className="catalog-product-card__price">
            {formatPrice(product.priceCents)} EUR{product.sellingType === 'rental' ? ' / mois' : ''}
          </span>
          {actionSlot ? (
            <div className="catalog-product-card__actions">{actionSlot}</div>
          ) : null}
        </footer>
      </div>
    </article>
  );
};

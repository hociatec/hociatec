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
  const availableColors = product.variantColors ?? (product.color ? [product.color] : []);
  const availableStorages =
    product.variantStorages ?? (product.storageCapacity ? [product.storageCapacity] : []);
  const compactSpecs = [
    product.brand?.trim(),
    product.memoryRam?.trim(),
    (product.variantsCount ?? 1) > 1 ? null : product.storageCapacity?.trim(),
    (product.variantsCount ?? 1) > 1 ? null : product.color?.trim(),
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
        {(product.variantsCount ?? 1) > 1 && (
          <div className="catalog-product-card__variant-summary">
            {availableStorages.length > 0 && (
              <p className="catalog-product-card__variant-line">
                <strong>Stockages :</strong> {availableStorages.join(', ')}
              </p>
            )}
            {availableColors.length > 0 && (
              <p className="catalog-product-card__variant-line">
                <strong>Coloris :</strong> {availableColors.join(', ')}
              </p>
            )}
          </div>
        )}
        {product.shortDescription && (
          <p className="catalog-product-card__excerpt">{product.shortDescription}</p>
        )}
        <footer className="catalog-product-card__footer">
          <div className="catalog-product-card__footer-main">
            <span className="catalog-product-card__price">
              {formatPrice(product.priceCents)} EUR{product.sellingType === 'rental' ? ' / mois' : ''}
            </span>
          </div>
          {actionSlot ? (
            <div className="catalog-product-card__actions">{actionSlot}</div>
          ) : null}
        </footer>
      </div>
    </article>
  );
};

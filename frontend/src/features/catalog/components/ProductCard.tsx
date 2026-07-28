import '../pages/CatalogPages.css';

import { useState } from 'react';
import { Link } from 'react-router';

import type { CatalogProduct } from '../api';
import { ProductMetaBadges } from './ProductMetaBadges';
import { getCatalogProductDisplayName } from '../utils/productDisplay';

interface ProductCardProps {
  product: CatalogProduct;
  actionSlot?: React.ReactNode;
}

const formatPrice = (priceCents: number) => (priceCents / 100).toFixed(2).replace('.', ',');

export const ProductCard = ({ product, actionSlot }: ProductCardProps) => {
  const [imageFailed, setImageFailed] = useState(false);
  const productLink = `/catalogue/produits/${product.slug}`;
  const productDisplayName = getCatalogProductDisplayName(product);
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
      {product.imageUrl && !imageFailed ? (
        <Link to={productLink} className="catalog-product-card__image-link">
          <img
            src={product.imageUrl}
            alt={product.imageAlt ?? productDisplayName}
            className="catalog-product-card__image"
            onError={() => setImageFailed(true)}
          />
        </Link>
      ) : (
        <Link to={productLink} className="catalog-product-card__image-link">
          <div className="catalog-product-card__placeholder" aria-hidden="true">
            <span>Produit</span>
          </div>
        </Link>
      )}
      <div className="catalog-product-card__content">
        <header className="catalog-product-card__header">
          <span className="catalog-product-card__sku">{product.sku}</span>
          <h3 className="catalog-product-card__title">
            <Link to={productLink} className="catalog-product-card__title-link">
              {productDisplayName}
            </Link>
          </h3>
        </header>
        <ProductMetaBadges sellingType={product.sellingType} sellingTypeLabel={product.sellingTypeLabel} categoryName={product.category.name} />
        {compactSpecs.length > 0 && (
          <p
            className="catalog-product-card__spec-summary"
            aria-label="Caractéristiques principales"
          >
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
              {formatPrice(product.priceCents)} EUR
              {product.priceUnitLabel ?? ''}
            </span>
          </div>
          {actionSlot ? <div className="catalog-product-card__actions">{actionSlot}</div> : null}
        </footer>
      </div>
    </article>
  );
};

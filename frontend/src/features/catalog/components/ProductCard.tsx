import '../pages/CatalogPages.css';

import { useState } from 'react';
import { Link } from 'react-router';
import { Image as ImageIcon } from 'lucide-react';

import { formatEuroCents } from '@/shared/lib/formatters';
import type { CatalogProduct } from '../api';
import { getCatalogProductDisplayName } from '../utils/productDisplay';
import { resolveDisplayPriceCents } from '../utils/productPageDisplay';

interface ProductCardProps {
  product: CatalogProduct;
  actionSlot?: React.ReactNode;
}

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
  const sellingContext = `${product.category.name} (${product.sellingTypeLabel})`;
  const productPrice = resolveDisplayPriceCents(product);

  return (
    <article className="catalog-product-card">
      <div className="catalog-product-card__image-link">
        {product.imageUrl && !imageFailed ? (
          <img
            src={product.imageUrl}
            alt={product.imageAlt ?? productDisplayName}
            className="catalog-product-card__image"
            width={400}
            height={300}
            loading="lazy"
            decoding="async"
            onError={() => setImageFailed(true)}
          />
        ) : (
          <div className="catalog-product-card__placeholder" aria-hidden="true">
            <ImageIcon size={32} className="opacity-40" />
          </div>
        )}
      </div>
      <div className="catalog-product-card__content">
        <header className="catalog-product-card__header">
          <h3 className="catalog-product-card__title">
            <Link to={productLink} prefetch="intent" className="catalog-product-card__title-link">
              {productDisplayName}
            </Link>
          </h3>
        </header>
        <div className="catalog-product-card__facts" aria-label={`Informations clés pour ${productDisplayName}`}>
          <p className="catalog-product-card__fact">
            <span className="catalog-product-card__fact-label">Référence:</span> {product.sku}
          </p>
          <p className="catalog-product-card__fact">
            <span className="catalog-product-card__fact-label">Type:</span> {sellingContext}
          </p>
          {compactSpecs.length > 0 && (
            <p className="catalog-product-card__fact">
              <span className="catalog-product-card__fact-label">Configuration:</span> {compactSpecs}
            </p>
          )}
        </div>
        {(product.variantsCount ?? 1) > 1 && (
          <div className="catalog-product-card__variant-summary">
            {availableStorages.length > 0 && (
              <p className="catalog-product-card__variant-line">
                <span className="catalog-product-card__fact-label">Stockages:</span> {availableStorages.join(', ')}
              </p>
            )}
            {availableColors.length > 0 && (
              <p className="catalog-product-card__variant-line">
                <span className="catalog-product-card__fact-label">Coloris:</span> {availableColors.join(', ')}
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
              {formatEuroCents(productPrice)}
              {product.priceUnitLabel ?? ''}
            </span>
          </div>
          {actionSlot ? (
            <div className="catalog-product-card__actions-container">
              <div className="catalog-product-card__actions">{actionSlot}</div>
            </div>
          ) : null}
        </footer>
      </div>
    </article>
  );
};

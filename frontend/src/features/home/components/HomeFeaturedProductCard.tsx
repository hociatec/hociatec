import { useState } from 'react';
import { Link } from 'react-router';

import type { CatalogProduct } from '@/features/catalog/api';
import { ProductMetaBadges } from '@/features/catalog/components/ProductMetaBadges';
import { getCatalogProductDisplayName } from '@/features/catalog/utils/productDisplay';
import { formatEuroCents } from '@/shared/lib/formatters';

export const HomeFeaturedProductCard = ({ product }: { product: CatalogProduct }) => {
  const [imageFailed, setImageFailed] = useState(false);
  const productDisplayName = getCatalogProductDisplayName(product);
  const compactSpecs = [
    product.brand?.trim(),
    product.storageCapacity?.trim(),
    product.memoryRam?.trim(),
    product.color?.trim(),
  ]
    .filter(Boolean)
    .join(' • ');

  const productLink = `/catalogue/produits/${product.slug}`;

  return (
    <article className="home-product-card">
      <Link to={productLink} className="home-product-card__media">
        {product.imageUrl && !imageFailed ? (
          <img
            src={product.imageUrl}
            alt={product.imageAlt ?? productDisplayName}
            onError={() => setImageFailed(true)}
          />
        ) : (
          <div className="home-product-card__placeholder">Produit</div>
        )}
      </Link>

      <div className="home-product-card__content">
        <header className="flex flex-col gap-2">
          <h3>
            <Link to={productLink} className="home-product-card__title-link">{productDisplayName}</Link>
          </h3>
          <p className="home-product-card__sku">
            Référence: <span className="font-semibold">{product.sku}</span>
          </p>
          <ProductMetaBadges
            sellingType={product.sellingType}
            sellingTypeLabel={product.sellingTypeLabel}
            categoryName={product.category.name}
          />
          {compactSpecs.length > 0 && (
            <p
              className="catalog-product-card__spec-summary"
              aria-label="Caractéristiques principales"
            >
              {compactSpecs}
            </p>
          )}
        </header>

        {product.shortDescription && (
          <p className="home-product-card__description">{product.shortDescription}</p>
        )}

        <div className="home-product-card__footer">
          <div className="home-product-card__footer-main">
            <span className="home-product-card__price">{formatEuroCents(product.priceCents)}</span>
          </div>
        </div>
      </div>
    </article>
  );
};

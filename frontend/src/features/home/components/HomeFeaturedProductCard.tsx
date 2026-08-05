import { useState } from 'react';
import { Link } from 'react-router';
import { Image as ImageIcon } from 'lucide-react';

import type { CatalogProduct } from '@/features/catalog/publicApi';
import { getCatalogProductDisplayName } from '@/features/catalog/publicApi';
import { ProductActionToolbar, ProductMetaBadges } from '@/features/catalog/uiApi';
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
      <div className="home-product-card__media">
        {product.imageUrl && !imageFailed ? (
          <img
            src={product.imageUrl}
            alt={product.imageAlt ?? productDisplayName}
            loading="lazy"
            decoding="async"
            onError={() => setImageFailed(true)}
          />
        ) : (
          <div className="home-product-card__placeholder">
            <ImageIcon size={32} className="opacity-40" />
          </div>
        )}
      </div>

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
          <div className="home-product-card__actions-container">
            <ProductActionToolbar product={product} />
          </div>
        </div>
      </div>
    </article>
  );
};

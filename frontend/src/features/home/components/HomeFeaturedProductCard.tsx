import { useState } from 'react';
import { Link } from 'react-router';
import { Image as ImageIcon } from 'lucide-react';

import {
  getCatalogProductDisplayName,
  resolveDisplayPriceCents,
  type CatalogProduct,
} from '@/features/catalog/publicApi';
import { ProductActionToolbar } from '@/features/catalog/uiApi';
import { formatEuroCents } from '@/shared/lib/formatters';

export const HomeFeaturedProductCard = ({
  product,
  imageLoading = 'lazy',
}: {
  product: CatalogProduct;
  imageLoading?: 'eager' | 'lazy';
}) => {
  const [imageFailed, setImageFailed] = useState(false);
  const productDisplayName = getCatalogProductDisplayName(product);
  const compactSpecs = [
    product.brand?.trim(),
    product.memoryRam?.trim(),
    (product.variantsCount ?? 1) > 1 ? null : product.storageCapacity?.trim(),
    (product.variantsCount ?? 1) > 1 ? null : product.color?.trim(),
  ]
    .filter(Boolean)
    .join(' • ');
  const sellingContext = `${product.category.name} (${product.sellingTypeLabel})`;
  const productPrice = resolveDisplayPriceCents(product);

  const productLink = `/catalogue/produits/${product.slug}`;

  return (
    <article className="home-product-card">
      <div className="home-product-card__media">
        {product.imageUrl && !imageFailed ? (
          <img
            src={product.imageUrl}
            alt={product.imageAlt ?? productDisplayName}
            width={400}
            height={300}
            loading={imageLoading}
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
            <Link to={productLink} prefetch="intent" className="home-product-card__title-link">
              {productDisplayName}
            </Link>
          </h3>
          <div className="home-product-card__facts" aria-label={`Informations clés pour ${productDisplayName}`}>
            <p className="home-product-card__fact">
              <span className="home-product-card__fact-label">Référence:</span> {product.sku}
            </p>
            <p className="home-product-card__fact">
              <span className="home-product-card__fact-label">Type:</span> {sellingContext}
            </p>
            {compactSpecs.length > 0 && (
              <p className="home-product-card__fact">
                <span className="home-product-card__fact-label">Configuration:</span> {compactSpecs}
              </p>
            )}
          </div>
        </header>

        {product.shortDescription && (
          <p className="home-product-card__description">{product.shortDescription}</p>
        )}

        <div className="home-product-card__footer">
          <div className="home-product-card__footer-main">
            <span className="home-product-card__price">{formatEuroCents(productPrice)}</span>
          </div>
          <div className="home-product-card__actions-container">
            <ProductActionToolbar product={product} />
          </div>
        </div>
      </div>
    </article>
  );
};

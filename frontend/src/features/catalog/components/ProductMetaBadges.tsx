import '../pages/CatalogPages.css';

import clsx from 'clsx';

import type { CatalogProduct } from '../api';

type ProductMetaVariant = 'card' | 'detail';

interface ProductMetaBadgesProps {
  sellingType: CatalogProduct['sellingType'];
  categoryName: string;
  className?: string;
  variant?: ProductMetaVariant;
}

const variantClassName: Record<ProductMetaVariant, string> = {
  card: 'product-meta--card',
  detail: 'product-meta--detail',
};

const sellingTypeLabel: Record<CatalogProduct['sellingType'], string> = {
  sale: 'Vente',
  rental: 'Location',
};

export const ProductMetaBadges = ({
  sellingType,
  categoryName,
  className,
  variant = 'card',
}: ProductMetaBadgesProps) => {
  const sellingTypeClass =
    sellingType === 'rental' ? 'product-meta__item--rental' : 'product-meta__item--sale';
  const sellingTypeText = sellingTypeLabel[sellingType];
  const accessibleLabel = `${categoryName} (${sellingTypeText.toLowerCase()})`;

  return (
    <div className={clsx('product-meta', variantClassName[variant], className)}>
      <div
        className={clsx('product-meta__item', sellingTypeClass)}
        aria-label={accessibleLabel}
      >
        <span className="product-meta__text" aria-hidden="true">
          {categoryName} ({sellingTypeText})
        </span>
      </div>
    </div>
  );
};

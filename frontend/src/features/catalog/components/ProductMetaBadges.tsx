import '../pages/CatalogPages.css';

import clsx from 'clsx';

type ProductMetaVariant = 'card' | 'detail';

interface ProductMetaBadgesProps {
  sellingType: 'sale' | 'rental';
  sellingTypeLabel: string;
  categoryName: string;
  className?: string;
  variant?: ProductMetaVariant;
}

const variantClassName: Record<ProductMetaVariant, string> = {
  card: 'product-meta--card',
  detail: 'product-meta--detail',
};

export const ProductMetaBadges = ({
  sellingType,
  sellingTypeLabel,
  categoryName,
  className,
  variant = 'card',
}: ProductMetaBadgesProps) => {
  const sellingTypeClass =
    sellingType === 'rental' ? 'product-meta__item--rental' : 'product-meta__item--sale';
  const accessibleLabel = `${categoryName} (${sellingTypeLabel.toLowerCase()})`;

  return (
    <div className={clsx('product-meta', variantClassName[variant], className)}>
      <div className={clsx('product-meta__item', sellingTypeClass)} aria-label={accessibleLabel}>
        <span className="product-meta__text" aria-hidden="true">
          {categoryName} ({sellingTypeLabel})
        </span>
      </div>
    </div>
  );
};

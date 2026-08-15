import { formatProductPrice, resolveDisplayPriceCents } from '../utils/productPageDisplay';
import type { CatalogProduct } from '../api';

interface ProductInfoHighlightProps {
  product: CatalogProduct;
  productDates: { created: string | null; updated: string | null } | null;
}

const ProductInfoRow = ({ label, value }: { label: string; value: string }) => (
  <p>{`${label}: ${value}.`}</p>
);

export const ProductInfoHighlight = ({ product, productDates }: ProductInfoHighlightProps) => {
  const visibleAttributes = (product.attributes ?? []).filter(
    (attribute) => attribute.value.trim() !== '',
  );

  return (
    <section className="catalog-detail-highlight" aria-labelledby="product-information-title">
      <div className="catalog-highlight-card">
        <h2 id="product-information-title">Informations</h2>
        <div className="catalog-highlight-card__info-list">
          <ProductInfoRow
            label="Prix public"
            value={`${formatProductPrice(resolveDisplayPriceCents(product))}${product.priceUnitLabel ?? ''}`}
          />
          <ProductInfoRow label="Référence" value={product.sku} />
          <ProductInfoRow label="Marque" value={product.brand ?? '-'} />
          {visibleAttributes.map((attribute) => (
            <ProductInfoRow key={attribute.code} label={attribute.label} value={attribute.value} />
          ))}
          <ProductInfoRow label="Année du modèle" value={String(product.releaseYear ?? '-')} />
          <ProductInfoRow
            label="Disponibilité"
            value={
              product.stock > 0
                ? `${product.stock} exemplaire${product.stock > 1 ? 's' : ''} en stock`
                : 'Sur commande'
            }
          />
          <ProductInfoRow label="Mise en ligne le" value={productDates?.created ?? '-'} />
          <ProductInfoRow label="Catégorie" value={product.category.name} />
        </div>
      </div>
    </section>
  );
};

import type { CatalogProduct } from '@/features/catalog/apiTypes';
import { ProductActionToolbar } from '@/features/catalog/components/ProductActionToolbar';
import { ProductCard } from '@/features/catalog/components/ProductCard';

export const CategoryProductGrid = ({ products }: { products: CatalogProduct[] }) => (
  <section className="catalog-grid catalog-grid--products">
    {products.length === 0 ? <div className="catalog-empty-state">Aucun produit ne correspond à ces filtres dans cette catégorie. Essayez une autre marque, une autre capacité ou retirez le filtre stock.</div> : products.map((product) => <ProductCard key={product.id} product={product} actionSlot={<ProductActionToolbar product={product} />} />)}
  </section>
);

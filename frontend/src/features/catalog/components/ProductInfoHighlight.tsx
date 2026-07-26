import { ProductMetaBadges } from '@/features/catalog/components/ProductMetaBadges';
import { formatProductPrice } from '../utils/productPageDisplay';
import type { CatalogProduct } from '../api';

interface ProductInfoHighlightProps {
  product: CatalogProduct;
  productDates: { created: string | null; updated: string | null } | null;
}

export const ProductInfoHighlight = ({ product, productDates }: ProductInfoHighlightProps) => (
  <section className="catalog-detail-highlight">
    <div className="catalog-highlight-card">
      <h2>Informations clés</h2>
      <dl>
        <div>
          <dt>Prix public</dt>
          <dd>
            {formatProductPrice(product.priceCents)}
            {product.sellingType === 'rental' ? ' / mois' : ''}
          </dd>
        </div>
        <div>
          <dt>Référence</dt>
          <dd>
            {product.sku}
            <ProductMetaBadges
              sellingType={product.sellingType}
              categoryName={product.category.name}
              variant="detail"
            />
          </dd>
        </div>
        <div>
          <dt>Marque</dt>
          <dd>{product.brand ?? '-'}</dd>
        </div>
        <div>
          <dt>Couleur</dt>
          <dd>{product.color ?? 'Par défaut'}</dd>
        </div>
        <div>
          <dt>Stockage</dt>
          <dd>{product.storageCapacity ?? '-'}</dd>
        </div>
        <div>
          <dt>Mémoire RAM</dt>
          <dd>{product.memoryRam ?? '-'}</dd>
        </div>
        <div>
          <dt>Année du modèle</dt>
          <dd>{product.releaseYear ?? '-'}</dd>
        </div>
        <div>
          <dt>Disponibilité</dt>
          <dd>
            {product.stock > 0
              ? `${product.stock} exemplaire${product.stock > 1 ? 's' : ''} en stock`
              : 'Sur commande'}
          </dd>
        </div>
        <div>
          <dt>Mise à jour</dt>
          <dd>{productDates?.updated ?? '-'}</dd>
        </div>
        <div>
          <dt>Création</dt>
          <dd>{productDates?.created ?? '-'}</dd>
        </div>
        <div>
          <dt>Catégorie</dt>
          <dd>{product.category.name}</dd>
        </div>
        <div>
          <dt>Visibilité</dt>
          <dd>{product.isPublished ? 'Publié' : 'Non publié'}</dd>
        </div>
        <div>
          <dt>Mise en avant</dt>
          <dd>{product.isFeaturedHome ? 'Présent sur l’accueil' : 'Classique'}</dd>
        </div>
      </dl>
    </div>
  </section>
);

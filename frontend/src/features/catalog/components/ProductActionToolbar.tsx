import { useState } from 'react';
import { Facebook, Mail } from 'lucide-react';

import { ProductCartActions } from '@/features/cart/publicApi';
import { FavoriteToggleButton } from '@/features/favorites/publicApi';
import type { CatalogProduct } from '@/features/catalog/api';
import { SITE_URL } from '@/shared/config/seoConfig';
import { openTrustedExternalUrl } from '@/shared/lib/externalUrls';
import { ProductShareDialog } from './ProductShareDialog';

interface ProductActionToolbarProps {
  product: CatalogProduct;
}

export const ProductActionToolbar = ({ product }: ProductActionToolbarProps) => {
  const [isShareDialogOpen, setIsShareDialogOpen] = useState(false);

  const absoluteUrl = `${SITE_URL}/catalogue/produits/${product.slug}`;
  const facebookShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(absoluteUrl)}`;

  return (
    <>
      <div className="product-action-toolbar" aria-label="Actions du produit">
        <ProductCartActions product={product} />
        <FavoriteToggleButton category="product" targetId={product.id} />
        <button
          type="button"
          onClick={() => {
            setIsShareDialogOpen(true);
          }}
          className="product-action-toolbar__button"
          title="Partager par e-mail"
          aria-label="Partager par e-mail"
          aria-haspopup="dialog"
        >
          <Mail size={16} />
          <span>Email</span>
        </button>
        <button
          type="button"
          onClick={() => openTrustedExternalUrl(facebookShareUrl)}
          className="product-action-toolbar__button"
          title="Partager sur Facebook"
          aria-label="Partager sur Facebook"
        >
          <Facebook size={16} />
          <span>Facebook</span>
        </button>
      </div>
      <ProductShareDialog
        product={product}
        open={isShareDialogOpen}
        onClose={() => setIsShareDialogOpen(false)}
      />
    </>
  );
};

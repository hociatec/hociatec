import { useState } from 'react';
import { Facebook, Mail } from 'lucide-react';

import { ProductCartActions } from '@/features/cart/publicApi';
import type { CatalogProduct } from '@/features/catalog/api';
import { SITE_URL } from '@/shared/config/seoConfig';
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
        <button
          type="button"
          onClick={() => window.open(facebookShareUrl, '_blank', 'noopener,noreferrer')}
          className="inline-flex items-center gap-1 rounded-full border border-brand-100 px-3 py-1.5 text-sm text-stone-700 hover:bg-brand-50"
          title="Partager sur Facebook"
          aria-label="Partager sur Facebook"
        >
          <Facebook size={16} />
          <span>Facebook</span>
        </button>
        <button
          type="button"
          onClick={() => {
            setIsShareDialogOpen(true);
          }}
          className="inline-flex items-center gap-1 rounded-full border border-brand-100 px-3 py-1.5 text-sm text-stone-700 hover:bg-brand-50"
          title="Partager par e-mail"
          aria-label="Partager par e-mail"
          aria-haspopup="dialog"
        >
          <Mail size={16} />
          <span>Email</span>
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
